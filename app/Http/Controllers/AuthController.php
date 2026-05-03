<?php

// File guide: Handles route logic and page data for app/Http/Controllers/AuthController.php.

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        $barangays = Barangay::orderBy('barangay_name')->get();

        return view('auth.register', compact('barangays'));
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordReset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:email,phone'],
            'email' => ['nullable', 'required_if:method,email', 'email'],
            'phone' => ['nullable', 'required_if:method,phone', 'string', 'max:30'],
        ]);

        if ($validated['method'] === 'email') {
            return $this->sendEmailPasswordReset($validated['email']);
        }

        return $this->sendPhonePasswordReset($validated['phone']);
    }

    public function showResetPassword(string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ]);

        $reset = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (! $reset || ! Hash::check($validated['token'], $reset->token) || now()->subMinutes(15)->greaterThan($reset->created_at)) {
            return back()->withErrors(['email' => 'This reset link is invalid or expired.'])->withInput();
        }

        User::where('email', $validated['email'])->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return redirect()->route('login')->with('verified', 'Your password has been reset. You can now log in.');
    }

    public function verifyPhoneReset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ]);

        $phone = session('reset_phone');
        $userId = session('reset_user_id');

        if (! $phone || ! $userId) {
            return redirect()->route('password.request')->withErrors(['phone' => 'Please request a reset code first.']);
        }

        $response = $this->twilioRequest('VerificationCheck', [
            'To' => $phone,
            'Code' => $validated['code'],
        ]);

        if (! ($response['ok'] ?? false) || ($response['json']['status'] ?? null) !== 'approved') {
            return back()->withErrors(['code' => 'Invalid or expired reset code.'])->withInput();
        }

        User::where('user_id', $userId)->update([
            'password' => Hash::make($validated['password']),
        ]);

        session()->forget(['reset_phone', 'reset_user_id']);

        return redirect()->route('login')->with('verified', 'Your password has been reset. You can now log in.');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone_number' => ['required', 'regex:/^\d{10,11}$/'],
            'barangay_id' => ['required', 'exists:barangays,barangay_id'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ],
        ], [
            'email.unique' => 'Email is already registered.',
            'phone_number.regex' => 'Phone number must be 10–11 digits.',
            'password.confirmed' => 'Passwords do not match.',
            'barangay_id.exists' => 'Selected barangay is invalid.',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => trim($request->first_name),
                'last_name' => trim($request->last_name),
                'email' => trim($request->email),
                'phone_number' => trim($request->phone_number),
                'password' => $request->password,
                'barangay_id' => $request->barangay_id,
                'role' => 'youth',
                'is_verified' => 0,
                'status' => 'inactive',
            ]);

            $verificationCode = (string) random_int(100000, 999999);

            EmailVerification::where('user_id', $user->user_id)->delete();

            EmailVerification::create([
                'user_id' => $user->user_id,
                'verification_code' => $verificationCode,
                'expires_at' => now()->addHour(),
                'created_at' => now(),
            ]);

            DB::commit();

            session([
                'user_id' => $user->user_id,
                'verify_email' => $user->email,
            ]);

            Mail::send('email.verification-code', [
                'first_name' => $user->first_name,
                'verification_code' => $verificationCode,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->first_name . ' ' . $user->last_name)
                    ->subject('SK360 Verification Code');
            });

            return redirect()->route('verify.notice');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Registration error: ' . $e->getMessage());

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'email' => 'Registration failed. Please try again.',
                ]);
        }
    }

    public function showVerify()
    {
        if (!session()->has('user_id')) {
            return redirect()->route('register');
        }

        return view('auth.verify', [
            'email' => session('verify_email'),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        if (!session()->has('user_id')) {
            return redirect()->route('register');
        }

        $request->validate([
            'code' => ['required', 'array', 'size:6'],
            'code.*' => ['required', 'digits:1'],
        ], [
            'code.required' => 'Please enter the complete 6-digit code.',
            'code.size' => 'Please enter the complete 6-digit code.',
            'code.*.digits' => 'Each code field must contain exactly 1 digit.',
        ]);

        $code = implode('', $request->code);
        $userId = session('user_id');

        $verification = EmailVerification::where('user_id', $userId)
            ->where('verification_code', $code)
            ->where('expires_at', '>', now())
            ->latest('verification_id')
            ->first();

        if (! $verification) {
            return back()->withErrors([
                'code' => 'Invalid or expired code.',
            ]);
        }

        User::where('user_id', $userId)->update([
            'is_verified' => 1,
            'status' => 'active',
        ]);

        EmailVerification::where('user_id', $userId)->delete();

        session()->forget(['user_id', 'verify_email']);

        return redirect()->route('login')->with('verified', 'Your account has been verified. You can now log in.');
    }

    public function resendVerificationCode(): RedirectResponse
    {
        $userId = session('user_id');

        if (! $userId) {
            return redirect()->route('register');
        }

        $user = User::where('user_id', $userId)->first();

        if (! $user) {
            return redirect()->route('register')->withErrors([
                'email' => 'User not found.',
            ]);
        }

        $newCode = (string) random_int(100000, 999999);

        try {
            EmailVerification::updateOrCreate(
                ['user_id' => $user->user_id],
                [
                    'verification_code' => $newCode,
                    'expires_at' => now()->addHour(),
                    'created_at' => now(),
                ]
            );

            Mail::send('email.verification-code', [
                'first_name' => $user->first_name,
                'verification_code' => $newCode,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->first_name . ' ' . $user->last_name)
                    ->subject('SK360 Verification Code');
            });

            return back()->with('success', 'A new code has been sent to your email.');

        } catch (\Exception $e) {
            \Log::error('Resend verification error: ' . $e->getMessage());

            return back()->withErrors([
                'email' => 'Failed to send verification email.',
            ]);
        }
    }

    protected function sendEmailPasswordReset(string $email): RedirectResponse
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors(['email' => 'No account found with this email.'])->withInput();
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        Mail::send('email.password-reset', [
            'first_name' => $user->first_name,
            'reset_url' => $resetUrl,
        ], function ($message) use ($user) {
            $message->to($user->email, trim($user->first_name.' '.$user->last_name))
                ->subject('SK360 Password Reset');
        });

        return back()->with('reset_success', 'Reset link sent. Please check your email.');
    }

    protected function sendPhonePasswordReset(string $phone): RedirectResponse
    {
        $user = $this->findUserByPhone($phone);

        if (! $user) {
            return back()->withErrors(['phone' => 'No account found with this phone number.'])->withInput();
        }

        $e164Phone = $this->toE164Phone($phone);

        if (! $e164Phone) {
            return back()->withErrors(['phone' => 'Use a valid Philippine phone number like +639123456789.'])->withInput();
        }

        $response = $this->twilioRequest('Verification', [
            'To' => $e164Phone,
            'Channel' => 'sms',
        ]);

        if (! ($response['ok'] ?? false)) {
            return back()->withErrors([
                'phone' => $response['message'] ?? 'Failed to send SMS reset code.',
            ])->withInput();
        }

        session([
            'reset_phone' => $e164Phone,
            'reset_user_id' => $user->user_id,
        ]);

        return back()
            ->with('reset_success', 'Reset code sent. Please check your phone.')
            ->with('show_phone_verify', true)
            ->with('reset_target', $e164Phone);
    }

    protected function twilioRequest(string $type, array $payload): array
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $serviceSid = config('services.twilio.verify_service_sid');

        if (! filled($sid) || ! filled($token) || ! filled($serviceSid)) {
            return [
                'ok' => false,
                'message' => 'Twilio is not configured. Add TWILIO_SID, TWILIO_AUTH_TOKEN, and TWILIO_VERIFY_SERVICE_SID to .env.',
            ];
        }

        $endpoint = $type === 'VerificationCheck'
            ? "https://verify.twilio.com/v2/Services/{$serviceSid}/VerificationCheck"
            : "https://verify.twilio.com/v2/Services/{$serviceSid}/Verifications";

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post($endpoint, $payload);

            return [
                'ok' => $response->successful(),
                'json' => $response->json() ?: [],
                'message' => $response->json('message') ?: 'Twilio request failed.',
            ];
        } catch (\Throwable $exception) {
            \Log::error('Twilio reset error: '.$exception->getMessage());

            return [
                'ok' => false,
                'message' => 'Failed to connect to Twilio. Please try again.',
            ];
        }
    }

    protected function findUserByPhone(string $phone): ?User
    {
        $target = $this->phoneDigits($phone);

        return User::whereNotNull('phone_number')->get()
            ->first(fn (User $user) => $this->phoneNumbersMatch($target, $this->phoneDigits((string) $user->phone_number)));
    }

    protected function phoneNumbersMatch(string $target, string $stored): bool
    {
        if ($target === '' || $stored === '') {
            return false;
        }

        return $target === $stored || substr($target, -10) === substr($stored, -10);
    }

    protected function phoneDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: '';
    }

    protected function toE164Phone(string $phone): ?string
    {
        $digits = $this->phoneDigits($phone);

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63'.$digits;
        }

        return null;
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            return back()
                ->withErrors([
                    'email' => 'No account found with this email.',
                ])
                ->onlyInput('email');
        }

        if ($user->status !== 'active') {
            return back()
                ->withErrors([
                    'email' => 'Your account is inactive. Contact admin.',
                ])
                ->onlyInput('email');
        }

        if (! $user->is_verified) {
            return back()
                ->withErrors([
                    'email' => 'Email not verified. Please check your inbox.',
                ])
                ->onlyInput('email');
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors([
                    'email' => 'Incorrect password.',
                ])
                ->onlyInput('email');
        }

        Auth::login($user, false);
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPathForRole($user->role));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function redirectPathForRole(?string $role): string
    {
        return match ($role) {
            'youth' => route('youth.home'),
            'sk_president' => route('sk_pres.home'),
            'sk_chairman' => route('sk_chairman.home'),
            'sk_secretary' => route('sk_secretary.home'),
            default => '/',
        };
    }
}

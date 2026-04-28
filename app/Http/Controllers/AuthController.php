<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

            Mail::send('emails.verification-code', [
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

            Mail::send('emails.verification-code', [
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

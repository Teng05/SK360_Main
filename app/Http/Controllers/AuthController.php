<?php

// File guide: Handles route logic and page data for app/Http/Controllers/AuthController.php.

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    private const OFFICIAL_ROLES=[
        'sk_president',
        'sk_chairman',
        'sk_secretary',
    ];

    public function showLogin()
    {
        if(Auth::check()){
            return redirect()->to(
                $this->redirectPathForRole(Auth::user()->role)
            );
        }

        return view('auth.login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordReset(Request $request): RedirectResponse
    {
        $validated=$request->validate([
            'method'=>['required','in:email,phone'],
            'email'=>['nullable','required_if:method,email','email'],
            'phone'=>['nullable','required_if:method,phone','string','max:30'],
        ]);

        if($validated['method'] === 'email'){
            return $this->sendEmailPasswordReset($validated['email']);
        }

        return $this->sendPhonePasswordReset($validated['phone']);
    }

    public function showResetPassword(string $token)
    {
        return view('auth.reset-password',[
            'token'=>$token,
            'email'=>request('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated=$request->validate([
            'email'=>['required','email'],
            'token'=>['required','string'],
            'password'=>['required','confirmed','min:8','regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/'],
        ]);

        $reset=DB::table('password_reset_tokens')
            ->where('email',$validated['email'])
            ->first();

        if(!$reset || !Hash::check($validated['token'],$reset->token) || now()->subMinutes(15)->greaterThan($reset->created_at)){
            return back()
                ->withErrors(['email'=>'This reset link is invalid or expired.'])
                ->withInput();
        }

        $user=User::where('email',$validated['email'])
            ->whereIn('role',self::OFFICIAL_ROLES)
            ->whereNull('archived_at')
            ->first();

        if(!$user){
            return back()
                ->withErrors(['email'=>'Official account not found.'])
                ->withInput();
        }

        $user->update([
            'password'=>Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')
            ->where('email',$validated['email'])
            ->delete();

        return redirect()
            ->route('login')
            ->with('verified','Your password has been reset. You can now log in.');
    }

    /*
    |--------------------------------------------------------------------------
    | INITIAL PASSWORD SETUP
    |--------------------------------------------------------------------------
    */
    public function showSetPassword(string $token)
    {
        $email=request('email');

        if(!$email){
            return redirect()->route('login')->withErrors([
                'email'=>'Invalid password setup link.',
            ]);
        }

        $user=User::where('email',$email)
            ->whereIn('role',self::OFFICIAL_ROLES)
            ->first();

        if(!$user){
            return redirect()->route('login')->withErrors([
                'email'=>'Invalid password setup link.',
            ]);
        }

        if((int)$user->is_verified === 1 && $user->status === 'active'){
            return redirect()
                ->route('login')
                ->with('verified','Your account is already activated. You may log in.');
        }

        $reset=DB::table('password_reset_tokens')
            ->where('email',$email)
            ->first();

        $invalid=!$reset || !Hash::check($token,$reset->token);
        $expired=$reset && now()->subHours(24)->greaterThan($reset->created_at);

        if($invalid || $expired){
            if($expired){
                DB::table('password_reset_tokens')
                    ->where('email',$email)
                    ->delete();
            }

            return view('auth.set-password',[
                'token'=>$token,
                'email'=>$email,
                'linkExpired'=>true,
                'accountRole'=>$user->role,
            ]);
        }

        return view('auth.set-password',[
            'token'=>$token,
            'email'=>$email,
            'linkExpired'=>false,
            'accountRole'=>$user->role,
        ]);
    }

    public function setPassword(Request $request): RedirectResponse
    {
        $validated=$request->validate([
            'email'=>['required','email'],
            'token'=>['required','string'],
            'password'=>['required','confirmed','min:8','regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/'],
        ],[
            'password.confirmed'=>'Passwords do not match.',
            'password.min'=>'Password must be at least 8 characters.',
            'password.regex'=>'Password must contain uppercase, lowercase, and a number.',
        ]);

        $reset=DB::table('password_reset_tokens')
            ->where('email',$validated['email'])
            ->first();

        if(!$reset || !Hash::check($validated['token'],$reset->token) || now()->subHours(24)->greaterThan($reset->created_at)){
            return back()->withErrors([
                'email'=>'This password setup link is invalid or expired.',
            ]);
        }

        $user=User::where('email',$validated['email'])
            ->whereIn('role',self::OFFICIAL_ROLES)
            ->first();

        if(!$user){
            return back()->withErrors([
                'email'=>'Official account not found.',
            ]);
        }

        $pendingAssignment=DB::table('official_terms')
            ->where('user_id',$user->user_id)
            ->where('status','pending')
            ->orderByDesc('official_term_id')
            ->first();

        $endedPresidentUserIds=[];

        DB::transaction(function() use($user,$validated,$pendingAssignment,&$endedPresidentUserIds){
            $user->update([
                'password'=>Hash::make($validated['password']),
                'is_verified'=>1,
                'status'=>'active',
                'archived_at'=>null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | PRESIDENT SUCCESSION
            |--------------------------------------------------------------------------
            */
            if($user->role === 'sk_president' && $pendingAssignment && $pendingAssignment->role === 'sk_president'){
                $caretakerTerms=DB::table('official_terms')
                    ->where('term_id',$pendingAssignment->term_id)
                    ->where('role','sk_president')
                    ->where('status','current')
                    ->where('user_id','!=',$user->user_id)
                    ->get();

                $endedPresidentUserIds=$caretakerTerms
                    ->pluck('user_id')
                    ->unique()
                    ->values()
                    ->all();

                if($caretakerTerms->isNotEmpty()){
                    DB::table('official_terms')
                        ->whereIn('official_term_id',$caretakerTerms->pluck('official_term_id')->all())
                        ->update([
                            'status'=>'completed',
                            'completed_at'=>now(),
                        ]);
                }

                if(!empty($endedPresidentUserIds)){
                    DB::table('users')
                        ->whereIn('user_id',$endedPresidentUserIds)
                        ->where('role','sk_president')
                        ->update([
                            'status'=>'inactive',
                            'archived_at'=>now(),
                        ]);
                }

                DB::table('official_terms')
                    ->where('official_term_id',$pendingAssignment->official_term_id)
                    ->update([
                        'status'=>'current',
                        'started_at'=>now(),
                        'completed_at'=>null,
                    ]);
            }else{
                DB::table('official_terms')
                    ->where('user_id',$user->user_id)
                    ->where('status','pending')
                    ->update([
                        'status'=>'current',
                        'started_at'=>now(),
                    ]);
            }

            DB::table('password_reset_tokens')
                ->where('email',$validated['email'])
                ->delete();
        });

        if(!empty($endedPresidentUserIds)){
            $this->invalidateDatabaseSessions($endedPresidentUserIds);
        }

        if($user->role === 'sk_president' && $pendingAssignment){
            return redirect()
                ->route('login')
                ->with('verified','Your SK President account has been activated successfully. The President handover is complete and you may now log in.');
        }

        return redirect()
            ->route('login')
            ->with('verified','Your password has been set successfully. You can now log in.');
    }

    /*
    |--------------------------------------------------------------------------
    | PHONE PASSWORD RESET
    |--------------------------------------------------------------------------
    */
    public function verifyPhoneReset(Request $request): RedirectResponse
    {
        $validated=$request->validate([
            'code'=>['required','digits:6'],
            'password'=>['required','confirmed','min:8','regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/'],
        ]);

        $phone=session('reset_phone');
        $userId=session('reset_user_id');

        if(!$phone || !$userId){
            return redirect()
                ->route('password.request')
                ->withErrors(['phone'=>'Please request a reset code first.']);
        }

        $response=$this->twilioRequest('VerificationCheck',[
            'To'=>$phone,
            'Code'=>$validated['code'],
        ]);

        if(!($response['ok'] ?? false) || ($response['json']['status'] ?? null) !== 'approved'){
            return back()
                ->withErrors(['code'=>'Invalid or expired reset code.'])
                ->withInput();
        }

        $user=User::where('user_id',$userId)
            ->whereIn('role',self::OFFICIAL_ROLES)
            ->whereNull('archived_at')
            ->first();

        if(!$user){
            session()->forget(['reset_phone','reset_user_id']);

            return redirect()
                ->route('password.request')
                ->withErrors(['phone'=>'Official account not found.']);
        }

        $user->update([
            'password'=>Hash::make($validated['password']),
        ]);

        session()->forget(['reset_phone','reset_user_id']);

        return redirect()
            ->route('login')
            ->with('verified','Your password has been reset. You can now log in.');
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL PASSWORD RESET
    |--------------------------------------------------------------------------
    */
    public function verifyEmailReset(Request $request): RedirectResponse
    {
        $validated=$request->validate([
            'code'=>['required','digits:6'],
            'password'=>['required','confirmed','min:8','regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/'],
        ]);

        $email=session('reset_email');
        $userId=session('reset_user_id');

        if(!$email || !$userId){
            return redirect()
                ->route('password.request')
                ->withErrors(['email'=>'Please request a reset code first.']);
        }

        $reset=DB::table('password_reset_tokens')
            ->where('email',$email)
            ->first();

        if(!$reset || !Hash::check($validated['code'],$reset->token) || now()->subMinutes(15)->greaterThan($reset->created_at)){
            return back()
                ->withErrors(['code'=>'Invalid or expired reset code.'])
                ->withInput();
        }

        $user=User::where('user_id',$userId)
            ->whereIn('role',self::OFFICIAL_ROLES)
            ->whereNull('archived_at')
            ->first();

        if(!$user){
            DB::table('password_reset_tokens')
                ->where('email',$email)
                ->delete();

            session()->forget(['reset_email','reset_user_id']);

            return redirect()
                ->route('password.request')
                ->withErrors(['email'=>'Official account not found.']);
        }

        $user->update([
            'password'=>Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')
            ->where('email',$email)
            ->delete();

        session()->forget(['reset_email','reset_user_id']);

        return redirect()
            ->route('login')
            ->with('verified','Your password has been reset. You can now log in.');
    }

    protected function sendEmailPasswordReset(string $email): RedirectResponse
    {
        $user=User::where('email',$email)
            ->whereIn('role',self::OFFICIAL_ROLES)
            ->whereNull('archived_at')
            ->first();

        if(!$user){
            return back()
                ->withErrors(['email'=>'No official account found with this email.'])
                ->withInput();
        }

        $code=(string)random_int(100000,999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email'=>$user->email],
            [
                'token'=>Hash::make($code),
                'created_at'=>now(),
            ]
        );

        Mail::send('email.password-reset',[
            'first_name'=>$user->first_name,
            'reset_code'=>$code,
        ],function($message) use($user){
            $message->to(
                $user->email,
                trim($user->first_name.' '.$user->last_name)
            )->subject('SK360 Password Reset');
        });

        session([
            'reset_email'=>$user->email,
            'reset_user_id'=>$user->user_id,
        ]);

        return back()
            ->with('reset_success','Reset code sent. Please check your email.')
            ->with('reset_method','email')
            ->with('show_email_verify',true)
            ->with('reset_target',$user->email);
    }

    protected function sendPhonePasswordReset(string $phone): RedirectResponse
    {
        $user=$this->findUserByPhone($phone);

        if(!$user){
            return back()
                ->withErrors(['phone'=>'No official account found with this phone number.'])
                ->withInput();
        }

        $e164Phone=$this->toE164Phone($phone);

        if(!$e164Phone){
            return back()
                ->withErrors(['phone'=>'Use a valid Philippine phone number like +639123456789.'])
                ->withInput();
        }

        $response=$this->twilioRequest('Verification',[
            'To'=>$e164Phone,
            'Channel'=>'sms',
        ]);

        if(!($response['ok'] ?? false)){
            return back()
                ->withErrors(['phone'=>$response['message'] ?? 'Failed to send SMS reset code.'])
                ->withInput();
        }

        session([
            'reset_phone'=>$e164Phone,
            'reset_user_id'=>$user->user_id,
        ]);

        return back()
            ->with('reset_success','Reset code sent. Please check your phone.')
            ->with('reset_method','phone')
            ->with('show_phone_verify',true)
            ->with('reset_target',$e164Phone);
    }

    /*
    |--------------------------------------------------------------------------
    | TWILIO
    |--------------------------------------------------------------------------
    */
    protected function twilioRequest(string $type,array $payload): array
    {
        $sid=config('services.twilio.sid');
        $token=config('services.twilio.token');
        $serviceSid=config('services.twilio.verify_service_sid');

        if(!filled($sid) || !filled($token) || !filled($serviceSid)){
            return [
                'ok'=>false,
                'message'=>'Twilio is not configured. Add TWILIO_SID, TWILIO_AUTH_TOKEN, and TWILIO_VERIFY_SERVICE_SID to .env.',
            ];
        }

        $endpoint=$type === 'VerificationCheck'
            ? "https://verify.twilio.com/v2/Services/{$serviceSid}/VerificationCheck"
            : "https://verify.twilio.com/v2/Services/{$serviceSid}/Verifications";

        try{
            $response=Http::asForm()
                ->withBasicAuth($sid,$token)
                ->post($endpoint,$payload);

            return [
                'ok'=>$response->successful(),
                'json'=>$response->json() ?: [],
                'message'=>$response->json('message') ?: 'Twilio request failed.',
            ];
        }catch(\Throwable $exception){
            \Log::error('Twilio reset error: '.$exception->getMessage());

            return [
                'ok'=>false,
                'message'=>'Failed to connect to Twilio. Please try again.',
            ];
        }
    }

    protected function findUserByPhone(string $phone): ?User
    {
        $target=$this->phoneDigits($phone);

        return User::whereIn('role',self::OFFICIAL_ROLES)
            ->whereNull('archived_at')
            ->whereNotNull('phone_number')
            ->get()
            ->first(fn(User $user)=>
                $this->phoneNumbersMatch(
                    $target,
                    $this->phoneDigits((string)$user->phone_number)
                )
            );
    }

    protected function phoneNumbersMatch(string $target,string $stored): bool
    {
        if($target === '' || $stored === ''){
            return false;
        }

        return $target === $stored ||
            substr($target,-10) === substr($stored,-10);
    }

    protected function phoneDigits(string $phone): string
    {
        return preg_replace('/\D+/','',$phone) ?: '';
    }

    protected function toE164Phone(string $phone): ?string
    {
        $digits=$this->phoneDigits($phone);

        if(str_starts_with($digits,'63') && strlen($digits) === 12){
            return '+'.$digits;
        }

        if(str_starts_with($digits,'09') && strlen($digits) === 11){
            return '+63'.substr($digits,1);
        }

        if(str_starts_with($digits,'9') && strlen($digits) === 10){
            return '+63'.$digits;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | INVALIDATE OUTGOING PRESIDENT SESSIONS
    |--------------------------------------------------------------------------
    */
    protected function invalidateDatabaseSessions(array $userIds): void
    {
        if(empty($userIds) || config('session.driver') !== 'database'){
            return;
        }

        try{
            DB::table(config('session.table','sessions'))
                ->whereIn('user_id',$userIds)
                ->delete();
        }catch(\Throwable $e){
            \Log::warning(
                'Unable to remove outgoing President database sessions: '.$e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN / LOGOUT
    |--------------------------------------------------------------------------
    */
    public function login(Request $request): RedirectResponse
    {
        $credentials=$request->validate([
            'email'=>['required','email'],
            'password'=>['required'],
        ]);

        $user=User::where('email',$credentials['email'])
            ->whereIn('role',self::OFFICIAL_ROLES)
            ->first();

        if(!$user){
            return back()
                ->withErrors(['email'=>'No official account found with this email.'])
                ->onlyInput('email');
        }

        if($user->status !== 'active'){
            return back()
                ->withErrors(['email'=>'Your account is inactive. Contact admin.'])
                ->onlyInput('email');
        }

        if(!$user->is_verified){
            return back()
                ->withErrors(['email'=>'Email not verified. Please check your inbox.'])
                ->onlyInput('email');
        }

        if(!Hash::check($credentials['password'],$user->password)){
            return back()
                ->withErrors(['email'=>'Incorrect password.'])
                ->onlyInput('email');
        }

        Auth::login($user,false);
        $request->session()->regenerate();

        return redirect()->intended(
            $this->redirectPathForRole($user->role)
        );
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
        return match($role){
            'sk_president'=>route('sk_pres.home'),
            'sk_chairman'=>route('sk_chairman.home'),
            'sk_secretary'=>route('sk_secretary.home'),
            default=>'/',
        };
    }
}
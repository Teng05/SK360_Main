<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Hash, Http, Mail, RateLimiter};
use Illuminate\Validation\ValidationException;

class ProfileContactController extends ProfileSettingsController
{
    public function send(Request $request,string $role)
    {
        $config=$this->authorizeRole($role);
        $user=auth()->user();
        $data=$request->validateWithBag('contact',[
            'contact_channel'=>['required','in:email,phone_number'],
            'current_password'=>['required','string'],
        ]);
        $key='profile-contact:'.$user->user_id;
        if(RateLimiter::tooManyAttempts($key.':send',5)) $this->fail('Please wait before requesting another code.');
        RateLimiter::hit($key.':send',600);
        if(!Hash::check($data['current_password'],$user->password)) $this->fail('Current password is incorrect.');
        $channel=$data['contact_channel'];
        $value=$this->contactValue($request,$channel);
        $this->ensureAvailable($channel,$value);
        if($value===$user->{$channel}) $this->fail('Enter a different email address or phone number.');

        return Cache::lock($key.':lock',30)->block(5,function() use($key,$channel,$value,$user,$config){
            if(Cache::has($key.':cooldown')) $this->fail('Please wait 60 seconds before requesting another code.');
            Cache::forget($key);
            $pending=['channel'=>$channel,'value'=>$value,'original'=>$user->{$channel},'expires'=>now()->addMinutes(10)->timestamp,'attempts'=>0];
            try{
                if($channel==='email'){
                    // A log/array mailer cannot prove ownership of an email address.
                    if(in_array(config('mail.default'),['log','array'],true)) throw new \RuntimeException('Email delivery is not configured.');
                    $code=(string)random_int(100000,999999);
                    $pending['hash']=Hash::make($code);
                    Mail::raw("Your SK360 contact change code is: {$code}\nThis code expires in 10 minutes. If you did not request this change, ignore this message.",function($mail) use($value){
                        $mail->to($value)->subject('Verify your new SK360 email address');
                    });
                }else{
                    $response=$this->twilio('Verifications',['To'=>'+63'.substr($value,1),'Channel'=>'sms']);
                    if(!$response->successful() || $response->json('status')!=='pending' || !$response->json('sid')) throw new \RuntimeException('SMS delivery failed.');
                    $pending['sid']=$response->json('sid');
                }
            }catch(\Throwable $exception){
                $this->fail('Unable to send a verification code. Please try again or contact the administrator to check email/SMS delivery.');
            }
            Cache::put($key,$pending,600);
            Cache::put($key.':cooldown',true,60);
            return redirect()->route($config['prefix'].'.profile')->with('status','Verification code sent to '.$value.'. Your contact details have not changed yet.');
        });
    }

    public function verify(Request $request,string $role)
    {
        $config=$this->authorizeRole($role);
        $code=$request->validateWithBag('contact',['contact_code'=>['required','digits:6']])['contact_code'];
        $user=auth()->user();
        $key='profile-contact:'.$user->user_id;
        if(RateLimiter::tooManyAttempts($key.':verify',10)) $this->fail('Too many verification attempts. Please wait 10 minutes.');
        RateLimiter::hit($key.':verify',600);
        return Cache::lock($key.':lock',30)->block(5,function() use($key,$code,$user,$config){
            $pending=Cache::get($key);
            if(!$pending || $pending['expires']<=now()->timestamp) $this->fail('The code has expired. Request a new code.');
            if($pending['attempts']>=5){ Cache::forget($key); $this->fail('Too many incorrect codes. Request a new code.'); }
            $pending['attempts']++;
            Cache::put($key,$pending,max(1,$pending['expires']-now()->timestamp));
            $valid=false;
            if($pending['channel']==='email'){
                $valid=Hash::check($code,$pending['hash']);
            }else{
                try{
                    $response=$this->twilio('VerificationCheck',['VerificationSid'=>$pending['sid'],'Code'=>$code]);
                    $valid=$response->successful() && $response->json('status')==='approved';
                }catch(\Throwable $exception){ $this->fail('SMS verification is unavailable. Please try again.'); }
            }
            if(!$valid) $this->fail('Incorrect or expired verification code.');
            $this->ensureAvailable($pending['channel'],$pending['value']);
            Cache::forget($key);
            $updated=User::where('user_id',$user->user_id)->where($pending['channel'],$pending['original'])->update([$pending['channel']=>$pending['value']]);
            if(!$updated) $this->fail('Your contact details changed since this request. Request a new code.');
            return redirect()->route($config['prefix'].'.profile')->with('status','Contact detail verified and updated successfully.');
        });
    }

    protected function contactValue(Request $request,string $channel): string
    {
        if($channel==='email') return strtolower(trim($request->validateWithBag('contact',['email'=>['required','email','max:255']])['email']));
        $phone=$request->validateWithBag('contact',['phone_number'=>['required','string','max:25']])['phone_number'];
        $digits=preg_replace('/[\s()+-]/','',trim($phone));
        if(preg_match('/^639\d{9}$/',$digits)) $digits='0'.substr($digits,2);
        if(preg_match('/^9\d{9}$/',$digits)) $digits='0'.$digits;
        if(!preg_match('/^09\d{9}$/',$digits)) $this->fail('Enter a valid Philippine mobile number (09XXXXXXXXX).');
        return $digits;
    }

    protected function ensureAvailable(string $channel,string $value): void
    {
        $others=User::where('user_id','!=',auth()->id());
        $exists=$channel==='email'
            ? $others->whereRaw('LOWER(email) = ?',[$value])->exists()
            : $others->whereNotNull('phone_number')->pluck('phone_number')->contains(fn($phone)=>substr(preg_replace('/\D/','',$phone),-10)===substr($value,-10));
        if($exists) $this->fail('That email address or phone number is already in use.');
    }

    protected function twilio(string $endpoint,array $payload)
    {
        $sid=config('services.twilio.sid'); $token=config('services.twilio.token'); $service=config('services.twilio.verify_service_sid');
        if(!$sid || !$token || !$service) throw new \RuntimeException('SMS verification is not configured.');
        return Http::asForm()->withBasicAuth($sid,$token)->timeout(15)->post("https://verify.twilio.com/v2/Services/{$service}/{$endpoint}",$payload);
    }

    protected function fail(string $message): never
    {
        throw ValidationException::withMessages(['contact'=>$message])->errorBag('contact');
    }
}

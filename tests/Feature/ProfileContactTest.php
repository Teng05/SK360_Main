<?php

namespace Tests\Feature;

use App\Http\Controllers\{ProfileContactController, ProfileSettingsController};
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB, Hash, Http, Mail, Schema};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProfileContactTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','mail.default'=>'smtp',
            'services.twilio.sid'=>'test','services.twilio.token'=>'test','services.twilio.verify_service_sid'=>'test']);
        DB::purge('sqlite');
        Schema::create('users',function(Blueprint $table){
            $table->increments('user_id');
            foreach(['first_name','last_name','email','phone_number','password','role'] as $field) $table->string($field);
        });
        $this->actingAs(User::create(['first_name'=>'Original','last_name'=>'Name','email'=>'old@example.test','phone_number'=>'09171234567','password'=>'password123','role'=>'sk_president']));
    }

    private function rejected(callable $action): void
    {
        try{ $action(); $this->fail('Expected validation failure.'); }
        catch(ValidationException $exception){ $this->assertNotEmpty($exception->errors()); }
    }

    public function test_names_and_unverified_contacts_cannot_be_changed_through_profile_update(): void
    {
        foreach(['first_name'=>'Changed','last_name'=>'Changed','email'=>'new@example.test','phone_number'=>'09991234567'] as $field=>$value){
            $this->rejected(fn()=>(new ProfileSettingsController())->update(Request::create('/','POST',[$field=>$value]),'sk_president'));
        }
        $this->assertSame('Original',auth()->user()->fresh()->first_name);
        $this->assertSame('old@example.test',auth()->user()->fresh()->email);
    }

    public function test_email_changes_only_after_correct_code_and_cannot_be_replayed(): void
    {
        $code=null;
        Mail::shouldReceive('raw')->once()->andReturnUsing(function($text,$callback) use(&$code){
            preg_match('/code is: (\d{6})/',$text,$matches); $code=$matches[1];
            $message=\Mockery::mock(\Illuminate\Mail\Message::class);
            $message->shouldReceive('to')->once()->with('new@example.test')->andReturnSelf();
            $message->shouldReceive('subject')->once()->andReturnSelf(); $callback($message);
        });
        $controller=new ProfileContactController();
        $controller->send(Request::create('/','POST',['contact_channel'=>'email','email'=>'new@example.test','current_password'=>'password123']),'sk_president');
        $this->assertSame('old@example.test',auth()->user()->fresh()->email);
        $pending=Cache::get('profile-contact:1');
        $this->assertTrue(Hash::check($code,$pending['hash']));
        $wrong=$code==='111111'?'222222':'111111';
        $this->rejected(fn()=>$controller->verify(Request::create('/','POST',['contact_code'=>$wrong]),'sk_president'));
        $this->assertSame('old@example.test',auth()->user()->fresh()->email);
        $controller->verify(Request::create('/','POST',['contact_code'=>$code]),'sk_president');
        $this->assertSame('new@example.test',auth()->user()->fresh()->email);
        $this->rejected(fn()=>$controller->verify(Request::create('/','POST',['contact_code'=>$code]),'sk_president'));
    }

    public function test_sms_uses_new_number_and_verification_sid_before_saving(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*Verifications'=>Http::response(['sid'=>'VEtest','status'=>'pending']),
            '*VerificationCheck'=>Http::response(['status'=>'approved']),
        ]);
        $controller=new ProfileContactController();
        $controller->send(Request::create('/','POST',['contact_channel'=>'phone_number','phone_number'=>'+63 999 123 4567','current_password'=>'password123']),'sk_president');
        $this->assertSame('09171234567',auth()->user()->fresh()->phone_number);
        Http::assertSent(fn($request)=>$request['To']==='+639991234567' && $request['Channel']==='sms');
        $controller->verify(Request::create('/','POST',['contact_code'=>'123456']),'sk_president');
        Http::assertSent(fn($request)=>($request['VerificationSid'] ?? null)==='VEtest' && $request['Code']==='123456');
        $this->assertSame('09991234567',auth()->user()->fresh()->phone_number);
    }

    public function test_expired_codes_attempt_limit_and_wrong_password_leave_contacts_unchanged(): void
    {
        $controller=new ProfileContactController();
        $pending=['channel'=>'email','value'=>'new@example.test','original'=>'old@example.test','hash'=>Hash::make('123456'),'attempts'=>0,'expires'=>now()->subSecond()->timestamp];
        Cache::put('profile-contact:1',$pending,600);
        $this->rejected(fn()=>$controller->verify(Request::create('/','POST',['contact_code'=>'123456']),'sk_president'));
        $pending['expires']=now()->addMinutes(10)->timestamp; $pending['attempts']=5;
        Cache::put('profile-contact:1',$pending,600);
        $this->rejected(fn()=>$controller->verify(Request::create('/','POST',['contact_code'=>'123456']),'sk_president'));
        $this->rejected(fn()=>$controller->send(Request::create('/','POST',['contact_channel'=>'email','email'=>'new@example.test','current_password'=>'wrong']),'sk_president'));
        $this->assertSame('old@example.test',auth()->user()->fresh()->email);
    }

    public function test_equivalent_phone_numbers_and_duplicate_emails_are_rejected(): void
    {
        User::create(['first_name'=>'Other','last_name'=>'User','email'=>'taken@example.test','phone_number'=>'+639991234567','password'=>'password123','role'=>'sk_chairman']);
        foreach(['email'=>'taken@example.test','phone_number'=>'09991234567'] as $channel=>$value){
            $this->rejected(fn()=>(new ProfileContactController())->send(Request::create('/','POST',['contact_channel'=>$channel,$channel=>$value,'current_password'=>'password123']),'sk_president'));
        }
        $this->assertNull(Cache::get('profile-contact:1'));
    }

    public function test_delivery_failure_leaves_no_pending_change(): void
    {
        Http::fake(['*'=>Http::response(['message'=>'Unavailable'],503)]);
        $this->rejected(fn()=>(new ProfileContactController())->send(Request::create('/','POST',[
            'contact_channel'=>'phone_number','phone_number'=>'09991234567','current_password'=>'password123',
        ]),'sk_president'));
        $this->assertNull(Cache::get('profile-contact:1'));
        $this->assertSame('09171234567',auth()->user()->fresh()->phone_number);
    }

    public function test_contact_routes_exist_for_each_role_and_reject_role_mismatch(): void
    {
        foreach(['sk_pres','sk_chairman','sk_secretary'] as $prefix){
            foreach(['send','verify'] as $action){
                $route=app('router')->getRoutes()->getByName($prefix.'.profile.contact.'.$action);
                $this->assertSame(['POST'],$route->methods());
            }
        }
        try{
            (new ProfileContactController())->send(Request::create('/','POST'),'sk_chairman');
            $this->fail('A mismatched role was allowed.');
        }catch(\Symfony\Component\HttpKernel\Exception\HttpException $exception){
            $this->assertSame(403,$exception->getStatusCode());
        }
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\ProfileContactController;
use App\Rules\PhilippineMobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhilippineMobileNumberTest extends TestCase
{
    public function test_only_complete_local_or_international_ph_mobile_formats_are_accepted(): void
    {
        foreach (['09123456789', '+639123456789', '09987654321', '+639987654321'] as $phone) {
            $this->assertTrue(Validator::make(['phone'=>$phone], ['phone'=>['required', new PhilippineMobileNumber()]])->passes(), $phone);
        }
        foreach (['123', '08123456789', '+631234567890', '639123456789', '9123456789', '+12025550123', '0912345678', '091234567890', '+63912345678', '+6391234567890', '09abcdefghi', '09 123456789', '0912-345-6789', "09123456789\n", ['09123456789']] as $phone) {
            $this->assertTrue(Validator::make(['phone'=>$phone], ['phone'=>['required', new PhilippineMobileNumber()]])->fails());
        }
        foreach ([null, ''] as $phone) {
            $this->assertTrue(Validator::make(['phone'=>$phone], ['phone'=>['nullable', new PhilippineMobileNumber()]])->passes());
        }
    }

    public function test_invalid_password_reset_number_is_rejected_before_account_lookup_or_sms(): void
    {
        Http::fake();
        $this->post(action([\App\Http\Controllers\AuthController::class, 'sendPasswordReset']), ['method'=>'phone', 'phone'=>'+12025550123'])
            ->assertSessionHasErrors('phone');
        Http::assertNothingSent();
    }

    public function test_profile_accepts_both_formats_and_retains_existing_local_storage_format(): void
    {
        $controller = new class extends ProfileContactController {
            public function phoneValue(Request $request): string { return $this->contactValue($request, 'phone_number'); }
        };
        foreach (['09123456789', '+639123456789'] as $phone) {
            $this->assertSame('09123456789', $controller->phoneValue(Request::create('/', 'POST', ['phone_number'=>$phone])));
        }
    }
}

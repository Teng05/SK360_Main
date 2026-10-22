<?php

namespace App\Rules;

use App\Services\EmailDomainService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

class DeliverableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || Validator::make(['email' => $value], ['email' => 'email:rfc,filter'])->fails()) {
            $fail('Enter a complete, valid email address.');
            return;
        }

        $domain = strtolower(substr($value, strrpos($value, '@') + 1));
        if (!app(EmailDomainService::class)->acceptsMail($domain)) {
            $fail('The email domain could not be verified as accepting mail. Check the address or try again later.');
        }
    }
}

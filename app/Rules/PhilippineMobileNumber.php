<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhilippineMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validate the complete number, not merely the country/local prefix.
        // This checks syntax; ownership is established by the existing SMS flow.
        if (!is_string($value) || !preg_match('/\A(?:09[0-9]{9}|\+639[0-9]{9})\z/', $value)) {
            $fail('Enter a Philippine mobile number as 09XXXXXXXXX or +639XXXXXXXXX (digits only after the prefix).');
        }
    }
}

@include('email.verification-code', [
    'heading' => 'Confirm your password change',
    'purpose' => 'Use the code below to confirm your SK360 password change.',
    'code' => $verification_code,
    'expiresMinutes' => 15,
    'notice' => 'If you did not request this change, secure your account immediately.',
])

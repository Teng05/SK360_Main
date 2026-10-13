@include('email.verification-code', [
    'heading' => 'Reset your password',
    'purpose' => 'Use the code below to reset your SK360 password.',
    'code' => $reset_code,
    'expiresMinutes' => 15,
])

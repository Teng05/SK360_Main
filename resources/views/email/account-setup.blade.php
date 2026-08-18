<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SK360 Account Setup</title>
</head>
<body>
    <h2>Welcome to SK360, {{ $user->first_name }}!</h2>

    <p>Your SK360 account has been created.</p>

    <p>
        Please click the button below to create your password and activate your account.
    </p>

    <p>
        <a href="{{ $setupLink }}">
            Set Your Password
        </a>
    </p>

    <p>This password setup link will expire after 24 hours.</p>

    <p>If you did not expect this account, you may ignore this email.</p>

    <p>SK360</p>
</body>
</html>
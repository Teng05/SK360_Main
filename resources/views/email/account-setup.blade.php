@extends('email.layout')
@section('title', 'Set up your account')
@section('preview', 'Your SK360 account is ready. Set your password to get started.')
@section('content')
<p style="margin:0 0 12px;color:#ce1935;font-size:11px;font-weight:700;letter-spacing:1.5px;">WELCOME TO SK360</p>
<h1 style="margin:0 0 22px;font-size:26px;line-height:1.3;letter-spacing:-0.5px;color:#18233a;">Let&rsquo;s set up your account.</h1>
<p style="margin:0 0 10px;font-size:14px;line-height:1.8;color:#526179;">Hello {{ $user->first_name }},</p>
<p style="margin:0 0 24px;font-size:14px;line-height:1.8;color:#526179;">Your SK360 account has been created. Create a password to activate your account and get started.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:25px 16px;background:#f7f8fb;border:1px solid #e8ecf3;border-radius:12px;">
<p style="margin:0 0 18px;font-size:14px;font-weight:700;line-height:1.6;color:#344258;">Your next step: create your password</p>
<table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" bgcolor="#ce1935" style="border-radius:9px;"><a href="{{ $setupLink }}" style="display:inline-block;padding:15px 27px;border:1px solid #ce1935;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;color:#ffffff;">Set Up My Account &rarr;</a></td></tr></table>
<p style="margin:16px 0 0;font-size:12px;line-height:1.6;color:#68758b;">This link expires in <strong>24 hours</strong>.</p>
</td></tr></table>
<p style="margin:24px 0 8px;font-size:12px;line-height:1.8;color:#68758b;">Button not working? Copy and paste this link into your browser:</p>
<p style="margin:0;font-size:12px;line-height:1.8;word-break:break-all;overflow-wrap:anywhere;"><a href="{{ $setupLink }}" style="color:#bd1730;text-decoration:underline;word-break:break-all;">{{ $setupLink }}</a></p>
<p style="margin:22px 0 0;font-size:12px;line-height:1.8;color:#8590a2;">If you did not expect this account, you can safely ignore this email.</p>
@endsection

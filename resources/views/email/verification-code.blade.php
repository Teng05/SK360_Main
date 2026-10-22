@extends('email.layout')
@section('title', $heading)
@section('preview', $heading.' ? your verification code expires in '.$expiresMinutes.' minutes.')
@section('content')
<p style="margin:0 0 12px;color:#ce1935;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">Account security</p>
<h1 style="margin:0 0 22px;font-size:26px;line-height:1.3;letter-spacing:-0.5px;color:#18233a;">{{ $heading }}</h1>
<p style="margin:0 0 10px;font-size:14px;line-height:1.8;color:#526179;">Hello {{ $first_name ?? 'there' }},</p>
<p style="margin:0 0 24px;font-size:14px;line-height:1.8;color:#526179;">{{ $purpose }}</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:25px 10px;background:#fff5f6;border:1px solid #f6dce1;border-radius:12px;">
<p style="margin:0 0 12px;color:#788399;font-size:10px;font-weight:700;letter-spacing:1.4px;">YOUR VERIFICATION CODE</p>
<p style="margin:0;font-family:'Courier New',monospace;font-size:34px;font-weight:700;line-height:1.4;letter-spacing:6px;color:#bd1730;">{{ $code }}</p>
<p style="margin:12px 0 0;font-size:12px;line-height:1.6;color:#68758b;">Valid for <strong>{{ $expiresMinutes }} minutes</strong></p>
</td></tr></table>
<p style="margin:24px 0 8px;font-size:13px;font-weight:700;line-height:1.7;color:#344258;">Keep this code to yourself.</p>
<p style="margin:0;font-size:13px;line-height:1.7;color:#68758b;">SK360 staff will never ask you for your verification code.</p>
<p style="margin:20px 0 0;font-size:12px;line-height:1.8;color:#8590a2;">{{ $notice ?? 'If you did not request this code, you can safely ignore this email.' }}</p>
@endsection

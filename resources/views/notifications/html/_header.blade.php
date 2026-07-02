{{-- Plain-HTML tenant notification header (no mail:: components, to avoid the markdown
     component × Livewire first-render conflict). Email-safe inline styles. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:24px 0;">
<tr><td align="center">
<table role="presentation" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:600px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
    <tr><td style="background:#2f3542;padding:16px 24px;color:#ffffff;font-size:18px;font-weight:bold;">{{ config('app.name') }}</td></tr>
    <tr><td style="padding:24px;font-size:14px;line-height:1.55;">
        <h2 style="margin:0 0 14px;font-size:18px;color:#2f3542;">{{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}</h2>

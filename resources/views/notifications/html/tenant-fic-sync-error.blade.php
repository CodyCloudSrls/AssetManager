@include('notifications.html._header')
<p>{{ trans('mail.tenant_fic_sync_error_intro') }}</p>
<p><strong>{{ trans('mail.tenant_fic_sync_error_when') }}:</strong> {{ $failedAt }}</p>
<p style="background:#fdf0f0;padding:10px 12px;border-left:3px solid #c0392b;margin:12px 0;"><strong>{{ trans('mail.tenant_fic_sync_error_detail') }}:</strong> {{ $errorMessage }}</p>
<p>{{ trans('mail.tenant_fic_sync_error_hint') }}</p>
@include('notifications.html._footer')

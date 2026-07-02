{{-- Global "renewals due" banner: warns whenever assets have a renewal/expiry within 30
     days (or already overdue). Only queried for authenticated users who can see assets,
     so login/guest pages skip it entirely. --}}
@can('index', \App\Models\Asset::class)
    @php($ccRenewalCount = \App\Models\Asset::expiringRenewal(30)->count())
    @if ($ccRenewalCount > 0)
        <div class="alert alert-warning hidden-print" style="margin-bottom:14px;">
            <i class="fa-solid fa-triangle-exclamation fa-fw" aria-hidden="true"></i>
            {{ trans_choice('admin/hardware/general.renewal_banner', $ccRenewalCount, ['count' => $ccRenewalCount]) }}
            <a href="{{ route('hardware.index', ['expiring_renewal' => 1]) }}" class="alert-link">{{ trans('admin/hardware/general.renewal_banner_link') }} <i class="fa fa-arrow-circle-right" aria-hidden="true"></i></a>
        </div>
    @endif
@endcan

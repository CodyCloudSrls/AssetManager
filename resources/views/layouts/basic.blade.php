<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ Helper::determineLanguageDirection() }}" data-theme="dark">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($snipeSettings) && ($snipeSettings->site_name) ? $snipeSettings->site_name : config('app.name') }}</title>

    <link rel="shortcut icon" type="image/ico" href="{{ ($snipeSettings) && ($snipeSettings->favicon!='') ?  Storage::disk('public')->url(e($snipeSettings->favicon)) : config('app.url').'/favicon.ico' }}">
    {{-- CodyCloud SVG favicon (logo-1 brand mark); modern browsers prefer this,
         the .ico above stays as a legacy fallback --}}
    <link rel="icon" type="image/svg+xml" href="{{ url('img/asset-logos/codycloud-soc-mark.svg') }}">
    {{-- stylesheets --}}
    <link rel="stylesheet" href="{{ url(mix('css/dist/all.css')) }}">

    <script nonce="{{ csrf_token() }}">
        window.snipeit = {
            settings: {
                "per_page": 50
            }
        };
    </script>


    @if (($snipeSettings) && ($snipeSettings->header_color))
        <style>
        .main-header .navbar, .main-header .logo {
        background-color: {{ $snipeSettings->header_color }};
        background: -webkit-linear-gradient(top,  {{ $snipeSettings->header_color }} 0%,{{ $snipeSettings->header_color }} 100%);
        background: linear-gradient(to bottom, {{ $snipeSettings->header_color }} 0%,{{ $snipeSettings->header_color }} 100%);
        border-color: {{ $snipeSettings->header_color }};
        }
        .skin-blue .sidebar-menu > li:hover > a, .skin-blue .sidebar-menu > li.active > a {
        border-left-color: {{ $snipeSettings->header_color }};
        }
        </style>
    @endif

    {{-- CodyCloud theme overlay (Cloudflare-style dark skin) --}}
    <link rel="stylesheet" href="{{ url('css/codycloud-theme.css') }}?v={{ @filemtime(public_path('css/codycloud-theme.css')) ?: '10' }}">

    @if (($snipeSettings) && ($snipeSettings->custom_css))
        <style>
            {!! $snipeSettings->show_custom_css() !!}
        </style>
    @endif

</head>

<body class="hold-transition login-page">

    <div class="text-center cc-login-logo">
        <a href="{{ config('app.url') }}" aria-label="{{ $snipeSettings->site_name ?? 'CodyCloud' }}">
            <img src="{{ url('img/asset-logos/codycloud-soc-logo.svg') }}" alt="{{ $snipeSettings->site_name ?? 'CodyCloud' }}">
        </a>
    </div>
  <!-- Content -->
  @yield('content')

    <div class="text-center" style="padding-top: 100px;">
        @if (($snipeSettings) && ($snipeSettings->privacy_policy_link!=''))
        <a target="_blank" rel="noopener" href="{{  $snipeSettings->privacy_policy_link }}" target="_new">{{ trans('admin/settings/general.privacy_policy') }}</a>
    @endif
    </div>

    {{-- Javascript files --}}
    <script src="{{ url(mix('js/dist/all.js')) }}" nonce="{{ csrf_token() }}"></script>


    @stack('js')
</body>

</html>

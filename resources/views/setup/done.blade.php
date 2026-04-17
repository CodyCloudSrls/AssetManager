@extends('layouts/setup')
{{-- Page title --}}
@section('title')
{{ trans('general.create_admin_user') }}
@parent
@stop

{{-- Page content --}}
@section('content')

    <style>
        .well-warning {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border-color: #faebcc;
        }
    </style>
    <!-- Notifications -->
    <div class="col-md-12">

        <p>
            Setup completato. Puoi iniziare subito andando <strong><a href="{{ config('app.url') }}">direttamente alla dashboard</a></strong>.
        </p>

        <div class="well well-sm well-warning">

            <p>
                <x-icon type="tip" /> <strong>Important Note Syncing Users via SCIM or LDAP</strong>
            </p>

            <p>
                If you plan on using SCIM or LDAP syncing to keep your user lists up to date with your directory services,
                make sure the username format for any users imported via CSV matches your directory service username format to avoid duplicating users in the platform.
            </p>
        </div>

    </div>

@stop

@section('button')
    <a class="btn btn-primary" href="{{ config('app.url') }}">{{ trans('admin/settings/general.create_admin_redirect') }}
        <i class="fa-solid fa-angles-right"></i>
    </a>
    @parent
@stop

<script>
    var duration = 2000;
    var animationEnd = Date.now() + duration;
    var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    var interval = setInterval(function() {
        var timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        var particleCount = 50 * (timeLeft / duration);
        // since particles fall down, start a bit higher than random
        confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } });
        confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } });
    }, 250);

</script>

@extends('layouts/default')

@php($f = fn ($v) => \App\Helpers\Helper::formatCurrencyOutput($v))

@section('title'){{ trans('erp/bilancio.title') }} @parent @stop

@section('content')
<div class="row"><div class="col-md-8 col-md-offset-2">
    <div class="box box-default">
        <div class="box-header with-border">
            <h2 class="box-title">{{ trans('erp/bilancio.title') }} — {{ $year }} @if($isCurrent)<small>{{ trans('erp/bilancio.ad_oggi') }}</small>@endif</h2>
            <div class="box-tools pull-right">
                <form method="GET" action="{{ route('erp.bilancio') }}" class="form-inline">
                    <select name="year" class="form-control input-sm" onchange="this.form.submit()">
                        @foreach ($years as $y)<option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>@endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="box-body">
            <p class="help-block">{{ trans('erp/bilancio.intro') }}</p>
            <table class="table">
                <tbody>
                    <tr style="background:#f9f9f9;"><th colspan="2">{{ trans('erp/bilancio.a_valore') }}</th></tr>
                    <tr><td style="padding-left:24px;">{{ trans('erp/bilancio.a1') }}</td><td class="text-right">{{ $f($rows['valoreProduzione']) }}</td></tr>
                    <tr style="font-weight:bold;"><td>{{ trans('erp/bilancio.tot_a') }}</td><td class="text-right">{{ $f($rows['valoreProduzione']) }}</td></tr>

                    <tr style="background:#f9f9f9;"><th colspan="2">{{ trans('erp/bilancio.b_costi') }}</th></tr>
                    <tr><td style="padding-left:24px;">{{ trans('erp/bilancio.b6') }}</td><td class="text-right">{{ $f($rows['b6']) }}</td></tr>
                    <tr><td style="padding-left:24px;">{{ trans('erp/bilancio.b7') }}</td><td class="text-right">{{ $f($rows['b7']) }}</td></tr>
                    <tr><td style="padding-left:24px;">{{ trans('erp/bilancio.b9') }}</td><td class="text-right">{{ $f($rows['b9']) }}</td></tr>
                    <tr><td style="padding-left:24px;">{{ trans('erp/bilancio.b10') }}</td><td class="text-right">{{ $f($rows['b10']) }}</td></tr>
                    <tr style="font-weight:bold;"><td>{{ trans('erp/bilancio.tot_b') }}</td><td class="text-right">{{ $f($rows['costiProduzione']) }}</td></tr>

                    <tr style="font-weight:bold; border-top:2px solid #ddd;"><td>{{ trans('erp/bilancio.diff_ab') }}</td><td class="text-right {{ $rows['diffAB'] < 0 ? 'text-danger' : '' }}">{{ $f($rows['diffAB']) }}</td></tr>
                    <tr style="font-weight:bold;"><td>{{ trans('erp/bilancio.risultato_ante') }}</td><td class="text-right">{{ $f($rows['diffAB']) }}</td></tr>
                    <tr><td>{{ trans('erp/bilancio.imposte') }} @if($impSource==='stima')<span class="label label-warning">{{ trans('erp/bilancio.stima') }}</span>@else<span class="label label-success">{{ trans('erp/bilancio.reale') }}</span>@endif</td><td class="text-right">{{ $f($rows['imposte']) }}</td></tr>
                    <tr style="font-weight:bold; border-top:2px solid #333; font-size:16px;"><td>{{ trans('erp/bilancio.utile') }}</td><td class="text-right {{ $rows['utile'] < 0 ? 'text-danger' : 'text-success' }}">{{ $f($rows['utile']) }}</td></tr>
                </tbody>
            </table>
            <div class="callout callout-warning"><p>{{ trans('erp/bilancio.note') }}</p></div>
            @unless ($hasBilancio)
                <p class="text-muted">{{ trans('erp/bilancio.no_official') }} <a href="{{ route('erp.bilanci.index') }}">{{ trans('erp/bilanci.nav') }}</a>.</p>
            @endunless
        </div>
    </div>
</div></div>
@stop

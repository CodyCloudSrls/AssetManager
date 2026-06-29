@extends('layouts/default')

@php($f = fn ($v) => \App\Helpers\Helper::formatCurrencyOutput($v))

@section('title')
    {{ trans('erp/controllo.title') }}
    @parent
@stop

@section('content')
    @unless ($hasData)
        <div class="callout callout-warning"><p>{{ trans('erp/controllo.no_data') }}</p></div>
    @endunless

    {{-- ===== Conto Economico riclassificato (multi-anno) ===== --}}
    <div class="row"><div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border"><h2 class="box-title">{{ trans('erp/controllo.ce_title') }}</h2></div>
            <div class="box-body">
                <p class="help-block">{{ trans('erp/controllo.ce_help') }}</p>
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th>{{ trans('erp/controllo.voce') }}</th>
                            @foreach ($years as $y)<th class="text-right">{{ $y }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php($lines = [
                            ['key' => 'ricavi', 'label' => trans('erp/controllo.ricavi'), 'strong' => false],
                            ['key' => 'cogs', 'label' => trans('erp/controllo.cogs'), 'strong' => false, 'neg' => true],
                            ['key' => 'margine_lordo', 'label' => trans('erp/controllo.margine_lordo'), 'strong' => true],
                            ['key' => 'opex', 'label' => trans('erp/controllo.opex'), 'strong' => false, 'neg' => true],
                            ['key' => 'personale', 'label' => trans('erp/controllo.personale'), 'strong' => false, 'neg' => true],
                            ['key' => 'ebit', 'label' => trans('erp/controllo.ebit'), 'strong' => true],
                        ])
                        @foreach ($lines as $line)
                            <tr @if($line['strong'])style="font-weight:bold; border-top:2px solid #ddd;"@endif>
                                <td>{{ $line['label'] }}</td>
                                @foreach ($years as $y)
                                    @php($val = $ce[$y][$line['key']] ?? 0)
                                    <td class="text-right {{ ($line['key']==='ebit' && $val < 0) ? 'text-danger' : '' }}">
                                        {{ (!empty($line['neg']) && $val != 0) ? '-' : '' }}{{ $f(abs($val)) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <td class="text-muted">{{ trans('erp/controllo.margine_pct') }}</td>
                            @foreach ($years as $y)<td class="text-right text-muted">{{ is_null($ce[$y]['margine_pct'] ?? null) ? '—' : $ce[$y]['margine_pct'].'%' }}</td>@endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div></div>

    <div class="row">
        {{-- ===== IVA ===== --}}
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">{{ trans('erp/controllo.iva_title') }}</h3></div>
                <div class="box-body">
                    <table class="table table-striped">
                        <thead><tr><th>{{ trans('erp/controllo.year') }}</th><th class="text-right">{{ trans('erp/controllo.iva_debito') }}</th><th class="text-right">{{ trans('erp/controllo.iva_credito') }}</th><th class="text-right">{{ trans('erp/controllo.iva_saldo') }}</th></tr></thead>
                        <tbody>
                            @foreach ($years as $y)
                                <tr>
                                    <td>{{ $y }}</td>
                                    <td class="text-right">{{ $f($iva[$y]['debito']) }}</td>
                                    <td class="text-right">{{ $f($iva[$y]['credito']) }}</td>
                                    <td class="text-right {{ $iva[$y]['saldo'] > 0 ? 'text-danger' : 'text-success' }}"><strong>{{ $f($iva[$y]['saldo']) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="help-block">{{ trans('erp/controllo.iva_help') }}</p>
                </div>
            </div>
        </div>

        {{-- ===== Flussi di cassa (anno corrente) ===== --}}
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">{{ trans('erp/controllo.cassa_title') }} {{ $cassa['year'] }}</h3></div>
                <div class="box-body">
                    <table class="table table-striped">
                        <thead><tr><th>{{ trans('erp/controllo.month') }}</th><th class="text-right">{{ trans('erp/controllo.incassi') }}</th><th class="text-right">{{ trans('erp/controllo.pagamenti') }}</th><th class="text-right">{{ trans('erp/controllo.cumulato') }}</th></tr></thead>
                        <tbody>
                            @foreach ($cassa['months'] as $mo)
                                @continue($mo['incassi'] == 0 && $mo['pagamenti'] == 0)
                                <tr>
                                    <td>{{ ucfirst($mo['label']) }}</td>
                                    <td class="text-right text-success">{{ $f($mo['incassi']) }}</td>
                                    <td class="text-right text-danger">{{ $f($mo['pagamenti']) }}</td>
                                    <td class="text-right">{{ $f($mo['cumulato']) }}</td>
                                </tr>
                            @endforeach
                            <tr style="font-weight:bold; border-top:2px solid #ddd;">
                                <td>{{ trans('erp/controllo.totale') }}</td>
                                <td class="text-right">{{ $f($cassa['incassi']) }}</td>
                                <td class="text-right">{{ $f($cassa['pagamenti']) }}</td>
                                <td class="text-right {{ $cassa['netto'] < 0 ? 'text-danger' : 'text-success' }}">{{ $f($cassa['netto']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ===== Crediti ===== --}}
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('erp/controllo.crediti_title') }}</h3>
                    <span class="pull-right"><strong class="text-success">{{ $f($creditiDebiti['tot_crediti']) }}</strong></span>
                </div>
                <div class="box-body" style="max-height:340px; overflow:auto;">
                    <table class="table table-condensed">
                        <thead><tr><th>{{ trans('erp/controllo.counterparty') }}</th><th class="text-right">{{ trans('erp/controllo.amount') }}</th></tr></thead>
                        <tbody>
                            @forelse ($creditiDebiti['crediti'] as $c)
                                <tr><td>{{ $c->entity_name ?? '—' }}</td><td class="text-right">{{ $f($c->aperto) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">{{ trans('erp/controllo.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== Debiti ===== --}}
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('erp/controllo.debiti_title') }}</h3>
                    <span class="pull-right"><strong class="text-danger">{{ $f($creditiDebiti['tot_debiti']) }}</strong></span>
                </div>
                <div class="box-body" style="max-height:340px; overflow:auto;">
                    <table class="table table-condensed">
                        <thead><tr><th>{{ trans('erp/controllo.counterparty') }}</th><th class="text-right">{{ trans('erp/controllo.amount') }}</th></tr></thead>
                        <tbody>
                            @forelse ($creditiDebiti['debiti'] as $d)
                                <tr><td>{{ $d->entity_name ?? '—' }}</td><td class="text-right">{{ $f($d->aperto) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">{{ trans('erp/controllo.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

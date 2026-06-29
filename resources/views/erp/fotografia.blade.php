@extends('layouts/default')

@php($currency = \App\Models\Setting::getSettings()->default_currency ?? 'EUR')
@php($fc = fn ($v) => $currency.' '.\App\Helpers\Helper::formatCurrencyOutput($v))
@php($f = fn ($v) => \App\Helpers\Helper::formatCurrencyOutput($v))

@section('title'){{ trans('erp/fotografia.title') }} @parent @stop

@section('content')
    <div class="row"><div class="col-md-12">
        <h2 style="margin-top:0;">{{ trans('erp/fotografia.title') }} <small>{{ $year }}</small></h2>
        <p class="help-block">{{ trans('erp/fotografia.intro') }}</p>
    </div></div>

    {{-- KPI ribbon --}}
    <div class="row">
        @php($cards = [
            ['l' => trans('erp/fotografia.kpi_ricavi'), 'v' => $fc($kpi['ricavi']), 'bg' => 'bg-aqua'],
            ['l' => trans('erp/fotografia.kpi_ebit'), 'v' => $fc($kpi['ebit']), 'bg' => $kpi['ebit'] < 0 ? 'bg-red' : 'bg-green'],
            ['l' => trans('erp/fotografia.kpi_cassa'), 'v' => $fc($kpi['cassa_netta_ytd']), 'bg' => 'bg-light-blue'],
            ['l' => trans('erp/fotografia.kpi_iva'), 'v' => $fc($kpi['saldo_iva']), 'bg' => 'bg-yellow'],
            ['l' => trans('erp/fotografia.kpi_personale'), 'v' => $fc($kpi['personale']), 'bg' => 'bg-purple'],
        ])
        @foreach ($cards as $c)
            <div class="col-md-4 col-sm-6"><div class="small-box {{ $c['bg'] }}"><div class="inner"><h4 style="margin:0;font-size:22px;">{{ $c['v'] }}</h4><p>{{ $c['l'] }}</p></div></div></div>
        @endforeach
    </div>

    <div class="row">
        {{-- Section 1: Risultati ufficiali (bilanci depositati) --}}
        <div class="col-md-7">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">{{ trans('erp/fotografia.sec1') }}</h3>
                    <a href="{{ route('erp.bilanci.index') }}" class="btn btn-default btn-xs pull-right">{{ trans('erp/bilanci.nav') }}</a></div>
                <div class="box-body">
                    @if ($bilanci->isNotEmpty())
                        <table class="table table-condensed">
                            <thead><tr><th>{{ trans('erp/bilanci.anno') }}</th><th class="text-right">{{ trans('erp/bilanci.ricavi') }}</th><th class="text-right">{{ trans('erp/bilanci.utile') }}</th><th class="text-right">{{ trans('erp/bilanci.imposte') }}</th></tr></thead>
                            <tbody>
                                @foreach ($bilanci as $b)
                                    <tr><td>{{ $b->anno }} @unless($b->is_deposited)<span class="label label-warning">{{ trans('erp/bilanci.stima') }}</span>@endunless</td>
                                        <td class="text-right">{{ $f($b->ricavi) }}</td>
                                        <td class="text-right {{ $b->utile < 0 ? 'text-danger' : 'text-success' }}">{{ $f($b->utile) }}</td>
                                        <td class="text-right">{{ $f($b->imposte) }}</td></tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr style="font-weight:bold;"><td colspan="2">{{ trans('erp/bilanci.utile_cumulato') }}</td><td class="text-right {{ $utileCumulato < 0 ? 'text-danger' : 'text-success' }}" colspan="2">{{ $f($utileCumulato) }}</td></tr></tfoot>
                        </table>
                    @else
                        <p class="text-muted">{{ trans('erp/bilanci.empty') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Section 5: Posizione e PFN --}}
        <div class="col-md-5">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">{{ trans('erp/fotografia.sec5') }}</h3></div>
                <div class="box-body">
                    <table class="table table-condensed">
                        <tr><td>{{ trans('erp/fotografia.crediti') }}</td><td class="text-right text-success">{{ $fc($posizione['crediti']) }}</td></tr>
                        <tr><td>{{ trans('erp/fotografia.debiti_commerciali') }}</td><td class="text-right text-danger">{{ $fc($posizione['debiti_commerciali']) }}</td></tr>
                        <tr style="font-weight:bold;"><td>{{ trans('erp/fotografia.saldo_commerciale') }}</td><td class="text-right {{ $posizione['saldo_commerciale'] < 0 ? 'text-danger' : 'text-success' }}">{{ $fc($posizione['saldo_commerciale']) }}</td></tr>
                        <tr><td>{{ trans('erp/fotografia.debito_finanziario') }}</td><td class="text-right text-danger">{{ $fc($posizione['debito_finanziario']) }}</td></tr>
                        <tr><td>{{ trans('erp/fotografia.cassa') }}</td><td class="text-right">{{ is_null($posizione['cassa']) ? '—' : $fc($posizione['cassa']) }}</td></tr>
                        <tr style="font-weight:bold; border-top:2px solid #ddd;"><td>{{ trans('erp/fotografia.pfn') }}</td><td class="text-right">{{ is_null($posizione['pfn']) ? trans('erp/fotografia.pfn_missing') : $fc($posizione['pfn']) }}</td></tr>
                    </table>
                    @can('update', \App\Models\CustomerContract::class)
                        <form method="POST" action="{{ route('erp.fotografia.input') }}" class="form-inline" style="margin-top:8px;">
                            @csrf
                            <label style="font-size:12px;">{{ trans('erp/fotografia.cassa_input') }}</label>
                            <div class="input-group" style="width:160px;"><span class="input-group-addon">€</span>
                                <input type="number" step="0.01" name="cassa_attuale" class="form-control input-sm" value="{{ old('cassa_attuale', $posizione['cassa']) }}">
                            </div>
                            <button class="btn btn-sm btn-primary">{{ trans('general.save') }}</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Section 2: Esposizione fornitori e professionisti --}}
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">{{ trans('erp/fotografia.sec2') }}</h3></div>
                <div class="box-body">
                    <table class="table table-condensed">
                        <tr><td>{{ trans('erp/fotografia.debiti_fic') }}</td><td class="text-right">{{ $f($esposizione['debiti_fic']) }}</td></tr>
                        <tr><td>{{ trans('erp/fotografia.notule') }} (<a href="{{ route('erp.notule.index') }}">{{ trans('erp/notule.title') }}</a>)</td><td class="text-right">{{ $f($esposizione['notule']) }}</td></tr>
                        <tr style="font-weight:bold;"><td>{{ trans('erp/fotografia.tot_debiti_commerciali') }}</td><td class="text-right text-danger">{{ $f($esposizione['totale']) }}</td></tr>
                    </table>
                    @if ($esposizione['top']->isNotEmpty())
                        <p class="text-muted" style="font-size:12px; margin-bottom:4px;">{{ trans('erp/fotografia.top_debiti') }}</p>
                        <table class="table table-condensed">
                            @foreach ($esposizione['top'] as $d)
                                <tr><td>{{ $d->entity_name ?? '—' }}</td><td class="text-right">{{ $f($d->aperto) }}</td></tr>
                            @endforeach
                        </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- Section 3: Indebitamento finanziario --}}
        <div class="col-md-6">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">{{ trans('erp/fotografia.sec3') }}</h3>
                    <a href="{{ route('erp.finanziamenti.index') }}" class="btn btn-default btn-xs pull-right">{{ trans('erp/finanziamenti.nav') }}</a></div>
                <div class="box-body">
                    @forelse ($finanziario['finanziamenti'] as $fin)
                        <div style="display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid #f0f0f0;">
                            <span>{{ $fin->nome }} @if($fin->stato==='da_confermare')<span class="label label-warning">{{ trans('erp/finanziamenti.da_confermare') }}</span>@endif <small class="text-muted">({{ $fin->rate_pagate }}/{{ $fin->rate_totali }} × {{ $f($fin->rata_mensile) }})</small></span>
                            <strong class="text-danger">{{ $f($fin->residuo) }}</strong>
                        </div>
                    @empty
                        <p class="text-muted">{{ trans('erp/finanziamenti.empty') }}</p>
                    @endforelse
                    <p style="margin-top:8px; font-weight:bold;">{{ trans('erp/finanziamenti.tot_residuo') }}: <span class="text-danger pull-right">{{ $f($finanziario['debito']) }}</span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="callout callout-info"><p>{{ trans('erp/fotografia.note') }}</p></div>
@stop

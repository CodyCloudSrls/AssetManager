@extends('layouts/default')

@php($currency = \App\Models\Setting::getSettings()->default_currency ?? 'EUR')
@php($fmt = fn ($v) => $currency.' '.\App\Helpers\Helper::formatCurrencyOutput($v))

@section('title')
    {{ trans('erp/general.title') }}
    @parent
@stop

@section('content')

    {{-- ════════ PREVISIONALE — CONTRATTI (valori inseriti a mano) ════════ --}}
    <div class="row">
        <div class="col-md-12">
            <h2 style="border-bottom:2px solid #3c8dbc; padding-bottom:6px; margin-top:0;">
                <x-icon type="long-arrow-right" class="fa-fw" /> {{ trans('erp/general.sections.previsionale') }}
            </h2>
            <p class="help-block">{{ trans('erp/general.sections.previsionale_help') }}</p>
        </div>
    </div>
    <div class="row">
        @php($previsionaleCards = [
            ['label' => trans('erp/general.kpi.mrr'), 'value' => $fmt($kpis['mrr']), 'bg' => 'bg-teal', 'icon' => 'long-arrow-right'],
            ['label' => trans('erp/general.kpi.arr'), 'value' => $fmt($kpis['arr']), 'bg' => 'bg-aqua', 'icon' => 'erp'],
            ['label' => trans('erp/general.kpi.active_contracts'), 'value' => $kpis['active_contracts'], 'bg' => 'bg-light-blue', 'icon' => 'long-arrow-right'],
            ['label' => trans('erp/general.kpi.expiring_contracts'), 'value' => $kpis['expiring_contracts'], 'bg' => 'bg-orange', 'icon' => 'warning'],
        ])
        @foreach ($previsionaleCards as $card)
            <div class="col-md-3 col-sm-6">
                <div class="small-box {{ $card['bg'] }}">
                    <div class="inner"><h3 style="font-size:24px;">{{ $card['value'] }}</h3><p>{{ $card['label'] }}</p></div>
                    <div class="icon"><x-icon :type="$card['icon']" /></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('erp/general.financials.title') }}</h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('reports.contract-forecast') }}" class="btn btn-default btn-sm">{{ trans('erp/general.financials.full_forecast') }}</a>
                    </div>
                </div>
                <div class="box-body">
                    <p class="help-block">{{ trans('erp/general.financials.help', ['from' => $report['from']->isoFormat('MMM YYYY'), 'to' => $report['to']->isoFormat('MMM YYYY')]) }}</p>
                    <table class="table table-striped snipe-table">
                        <thead>
                            <tr>
                                <th>{{ trans('general.currency') }}</th>
                                <th class="text-right">{{ trans('erp/general.financials.revenue') }}</th>
                                <th class="text-right">{{ trans('erp/general.financials.cost') }}</th>
                                <th class="text-right">{{ trans('erp/general.financials.net') }}</th>
                                <th class="text-right">{{ trans('erp/general.financials.margin') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['summaryRows'] as $row)
                                @php($margin = $row['revenue'] > 0 ? round($row['net'] / $row['revenue'] * 100, 1) : null)
                                <tr>
                                    <td><strong>{{ $row['currency'] }}</strong></td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($row['revenue']) }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($row['cost']) }}</td>
                                    <td class="text-right {{ $row['net'] < 0 ? 'text-danger' : 'text-success' }}">{{ \App\Helpers\Helper::formatCurrencyOutput($row['net']) }}</td>
                                    <td class="text-right">{{ is_null($margin) ? '—' : $margin.'%' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">{{ trans('erp/general.financials.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════ OPERATIVO / EFFETTIVO — FATTURE IN CLOUD (dati reali) ════════ --}}
    <div class="row">
        <div class="col-md-12">
            <h2 style="border-bottom:2px solid #00a65a; padding-bottom:6px;">
                <x-icon type="erp" class="fa-fw" /> {{ trans('erp/general.sections.operativo') }}
            </h2>
            <p class="help-block">{{ trans('erp/general.sections.operativo_help') }}</p>
        </div>
    </div>
    @if ($fic['enabled'])
        <div class="row">
            @php($operativoCards = [
                ['label' => trans('erp/general.fic.revenue_year'), 'value' => $fmt($fic['revenue_year']), 'bg' => 'bg-green', 'icon' => 'erp'],
                ['label' => trans('erp/general.fic.receivables'), 'value' => $fmt($fic['receivables']), 'bg' => 'bg-light-blue', 'icon' => 'long-arrow-right'],
                ['label' => trans('erp/general.fic.payables'), 'value' => $fmt($fic['payables']), 'bg' => 'bg-red', 'icon' => 'long-arrow-right'],
                ['label' => trans('erp/general.fic.vat_balance'), 'value' => $fmt($fic['vat_balance']), 'bg' => 'bg-purple', 'icon' => 'records'],
            ])
            @foreach ($operativoCards as $card)
                <div class="col-md-3 col-sm-6">
                    <div class="small-box {{ $card['bg'] }}">
                        <div class="inner"><h3 style="font-size:24px;">{{ $card['value'] }}</h3><p>{{ $card['label'] }}</p></div>
                        <div class="icon"><x-icon :type="$card['icon']" /></div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($fic['overdue_receivables'] > 0)
            <div class="row"><div class="col-md-12">
                <div class="callout callout-warning"><p>{{ trans('erp/general.fic.overdue') }}: <strong>{{ $fmt($fic['overdue_receivables']) }}</strong></p></div>
            </div></div>
        @endif
        @if ($fic['deadlines']->isNotEmpty())
            <div class="row"><div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ trans('erp/general.scadenzario.title') }}</h3>
                        <span class="pull-right text-muted" style="font-size:11px;">{{ trans('erp/general.fic.last_sync') }}: {{ $fic['last_sync'] ? \Illuminate\Support\Carbon::parse($fic['last_sync'])->diffForHumans() : '—' }}</span>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped snipe-table">
                            <thead>
                                <tr>
                                    <th>{{ trans('erp/general.scadenzario.due_date') }}</th>
                                    <th>{{ trans('erp/general.scadenzario.type') }}</th>
                                    <th>{{ trans('erp/general.scadenzario.entity') }}</th>
                                    <th>{{ trans('erp/general.scadenzario.document') }}</th>
                                    <th class="text-right">{{ trans('erp/general.scadenzario.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($fic['deadlines'] as $doc)
                                    @php($overdue = $doc->due_on && $doc->due_on->isPast())
                                    <tr>
                                        <td>{{ optional($doc->due_on)->format('d/m/Y') }} @if ($overdue)<span class="label label-danger">{{ trans('erp/general.scadenzario.overdue') }}</span>@endif</td>
                                        <td>
                                            @if ($doc->direction === \App\Models\FicDocument::DIRECTION_ISSUED)
                                                <span class="label label-success">{{ trans('erp/general.scadenzario.collect') }}</span>
                                            @else
                                                <span class="label label-default">{{ trans('erp/general.scadenzario.pay') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $doc->entity_name ?? '—' }}</td>
                                        <td>{{ $doc->number ?? '—' }}</td>
                                        <td class="text-right {{ $overdue ? 'text-danger' : '' }}">{{ \App\Helpers\Helper::formatCurrencyOutput($doc->outstanding) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div></div>
        @endif
    @else
        <div class="row"><div class="col-md-12">
            <div class="box box-default"><div class="box-body">
                @if ($ficConfigured)
                    <p><span class="label label-success">{{ trans('erp/general.fic.configured') }}</span></p>
                    <p class="text-muted">{{ trans('erp/general.fic.configured_help') }}</p>
                @else
                    <p><span class="label label-default">{{ trans('erp/general.fic.not_configured') }}</span></p>
                    <p class="text-muted">{{ trans('erp/general.fic.not_configured_help') }}</p>
                @endif
            </div></div>
        </div></div>
    @endif

    {{-- ════════ ANAGRAFICHE + roadmap ════════ --}}
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-gray">
                <div class="inner"><h3 style="font-size:24px;">{{ $kpis['customers'] }}</h3><p>{{ trans('erp/general.kpi.customers') }}</p></div>
                <div class="icon"><x-icon type="users" /></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-gray">
                <div class="inner"><h3 style="font-size:24px;">{{ $kpis['suppliers'] }}</h3><p>{{ trans('erp/general.kpi.suppliers') }}</p></div>
                <div class="icon"><x-icon type="records" /></div>
            </div>
        </div>
        @can('view', \App\Models\CustomerContract::class)
            <div class="col-md-6">
                <a href="{{ route('contracts.index') }}" class="box box-default" style="display:block; padding:16px; text-decoration:none;">
                    <h4 style="margin-top:0;"><x-icon type="long-arrow-right" class="fa-fw" /> {{ trans('erp/general.modules.contracts') }}
                        <span class="label label-success pull-right">{{ trans('erp/general.status_active') }}</span></h4>
                    <p class="text-muted">{{ trans('erp/general.modules.contracts_help') }}</p>
                </a>
            </div>
        @endcan
    </div>

    {{-- ===== Planned management-control modules (PDF roadmap) ===== --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">{{ trans('erp/general.roadmap_title') }}</h3></div>
                <div class="box-body">
                    <div class="row">
                        @foreach (['pnl' => 'chart-line', 'cashflow' => 'chart-line', 'deadlines' => 'warning', 'reconciliation' => 'long-arrow-right', 'cockpit' => 'chart-line', 'payroll' => 'users'] as $moduleKey => $moduleIcon)
                            <div class="col-md-4 col-sm-6" style="margin-bottom:16px;">
                                <div class="box box-default" style="height:100%; padding:14px; opacity:.7;">
                                    <h4 style="margin-top:0;"><x-icon :type="$moduleIcon" class="fa-fw" /> {{ trans('erp/general.modules.'.$moduleKey) }}
                                        <span class="label label-default pull-right">{{ trans('erp/general.status_planned') }}</span></h4>
                                    <p class="text-muted">{{ trans('erp/general.modules.'.$moduleKey.'_help') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="callout callout-info" style="margin-top:8px;"><p>{{ trans('erp/general.connectors_note') }}</p></div>
                </div>
            </div>
        </div>
    </div>
@stop

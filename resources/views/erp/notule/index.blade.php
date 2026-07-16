@extends('layouts/default')

@section('title')
    {{ trans('erp/notule.title') }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('erp/notule.title') }}</h2>
                    @can('update', \App\Models\CustomerContract::class)
                        <div class="box-tools pull-right">
                            <a href="{{ route('erp.notule.create') }}" class="btn btn-primary btn-sm">{{ trans('erp/notule.new') }}</a>
                        </div>
                    @endcan
                </div>
                <div class="box-body">
                    <p class="help-block">{{ trans('erp/notule.intro') }}</p>

                    <div class="row" style="margin-bottom:14px;">
                        <div class="col-md-4 col-sm-6"><div class="small-box bg-yellow"><div class="inner"><h4 style="margin:0;">EUR {{ \App\Helpers\Helper::formatCurrencyOutput($totals['pending']) }}</h4><p>{{ trans('erp/notule.tot_pending') }}</p></div></div></div>
                        <div class="col-md-4 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h4 style="margin:0;">EUR {{ \App\Helpers\Helper::formatCurrencyOutput($totals['all']) }}</h4><p>{{ trans('erp/notule.tot_all') }}</p></div></div></div>
                    </div>

                    <table id="notuleListingTable" data-cookie-id-table="notuleListingTable" data-id-table="notuleListingTable" data-pagination="true" data-search="true" data-show-columns="true" data-show-export="true" data-export-options='{"fileName": "notule-{{ date('Y-m-d') }}"}' class="table table-striped snipe-table">
                        <thead>
                            <tr>
                                <th data-sortable="true" data-sorter="notuleDateSorter">{{ trans('erp/notule.competence_date') }}</th>
                                <th data-sortable="true">{{ trans('erp/notule.professional') }}</th>
                                <th data-sortable="true">{{ trans('erp/notule.description') }}</th>
                                <th class="text-right" data-sortable="true" data-sorter="notuleCurrencySorter">{{ trans('erp/notule.amount') }}</th>
                                <th class="text-right" data-sortable="true" data-sorter="notuleCurrencySorter">{{ trans('erp/notule.paid') }}</th>
                                <th class="text-right" data-sortable="true" data-sorter="notuleCurrencySorter">{{ trans('erp/notule.residuo') }}</th>
                                <th data-sortable="true" data-sorter="notuleTextSorter">{{ trans('erp/notule.status') }}</th>
                                <th data-sortable="false" data-searchable="false" data-switchable="false"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($notule as $n)
                                <tr>
                                    <td>{{ optional($n->competence_date)->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $n->display_name }}</td>
                                    <td>{{ $n->description }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($n->amount) }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($n->paid_amount) }}</td>
                                    <td class="text-right {{ $n->residuo > 0 ? 'text-danger' : '' }}">{{ \App\Helpers\Helper::formatCurrencyOutput($n->residuo) }}</td>
                                    <td>
                                        @php($cls = ['unpaid' => 'label-warning', 'paid' => 'label-success'][$n->status] ?? 'label-default')
                                        <span class="label {{ $cls }}">{{ $n->status_label }}</span>
                                        @if ($n->invoice_received)
                                            <span class="label label-info" title="{{ trans('erp/notule.invoice_received') }}"><i class="fa-solid fa-file-invoice"></i></span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @can('update', \App\Models\CustomerContract::class)
                                            <a href="{{ route('erp.notule.edit', $n) }}" class="btn btn-sm btn-default"><x-icon type="edit"/></a>
                                            <form method="POST" action="{{ route('erp.notule.destroy', $n) }}" style="display:inline" onsubmit="return confirm('{{ trans('general.sure_to_delete') }}')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger"><x-icon type="delete"/></button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="help-block">{{ trans('erp/notule.dedup_note') }}</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('moar_scripts')
@include('partials.bootstrap-table')
<script nonce="{{ csrf_token() }}">
    // Currency cells render as "1,234.56" (en) OR "1.234,56" (it) depending on the
    // digit_separator setting (Helper::formatCurrencyOutput), so parse BOTH: whichever of
    // comma/dot appears LAST is the decimal separator.
    window.notuleParseCurrency = function (v) {
        var s = String(v == null ? '' : v).replace(/<[^>]*>/g, '').replace(/[^0-9.,-]/g, '');
        if (s === '' || s === '-') { return 0; }
        var lc = s.lastIndexOf(','), ld = s.lastIndexOf('.');
        if (lc > ld) { s = s.replace(/\./g, '').replace(',', '.'); } else { s = s.replace(/,/g, ''); }
        var n = parseFloat(s);
        return isNaN(n) ? 0 : n;
    };
    window.notuleCurrencySorter = function (a, b) {
        return window.notuleParseCurrency(a) - window.notuleParseCurrency(b);
    };
    // d/m/Y cells; the em-dash placeholder for a null date sorts as 0 (earliest).
    window.notuleParseDate = function (v) {
        var m = String(v == null ? '' : v).replace(/<[^>]*>/g, '').match(/(\d{2})\/(\d{2})\/(\d{4})/);
        return m ? new Date(+m[3], +m[2] - 1, +m[1]).getTime() : 0;
    };
    window.notuleDateSorter = function (a, b) {
        return window.notuleParseDate(a) - window.notuleParseDate(b);
    };
    // Status column: sort by the visible label text (strip the <span>/icon markup).
    window.notuleTextSorter = function (a, b) {
        var ta = String(a == null ? '' : a).replace(/<[^>]*>/g, '').trim().toLowerCase(),
            tb = String(b == null ? '' : b).replace(/<[^>]*>/g, '').trim().toLowerCase();
        return ta < tb ? -1 : (ta > tb ? 1 : 0);
    };
</script>
@stop

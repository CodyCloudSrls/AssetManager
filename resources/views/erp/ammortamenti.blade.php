@extends('layouts/default')

@section('title')
    {{ trans('erp/general.ammortamenti.title') }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('erp/general.ammortamenti.title') }} — {{ $year }}</h2>
                    <div class="box-tools pull-right">
                        <form method="GET" action="{{ route('erp.ammortamenti') }}" class="form-inline">
                            <label for="year">{{ trans('erp/general.ammortamenti.year') }}</label>
                            <select name="year" id="year" class="form-control input-sm" onchange="this.form.submit()">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="box-body">
                    <p class="help-block">{{ trans('erp/general.ammortamenti.intro') }}</p>

                    {{-- Totals --}}
                    <div class="row" style="margin-bottom:14px;">
                        <div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h4 style="margin:0;">{{ \App\Helpers\Helper::formatCurrencyOutput($totals['cost']) }}</h4><p>{{ trans('erp/general.ammortamenti.tot_cost') }}</p></div></div></div>
                        <div class="col-md-3 col-sm-6"><div class="small-box bg-yellow"><div class="inner"><h4 style="margin:0;">{{ \App\Helpers\Helper::formatCurrencyOutput($totals['quota_year']) }}</h4><p>{{ trans('erp/general.ammortamenti.tot_quota') }}</p></div></div></div>
                        <div class="col-md-3 col-sm-6"><div class="small-box bg-orange"><div class="inner"><h4 style="margin:0;">{{ \App\Helpers\Helper::formatCurrencyOutput($totals['fondo']) }}</h4><p>{{ trans('erp/general.ammortamenti.tot_fondo') }}</p></div></div></div>
                        <div class="col-md-3 col-sm-6"><div class="small-box bg-green"><div class="inner"><h4 style="margin:0;">{{ \App\Helpers\Helper::formatCurrencyOutput($totals['residuo']) }}</h4><p>{{ trans('erp/general.ammortamenti.tot_residuo') }}</p></div></div></div>
                    </div>

                    <table class="table table-striped snipe-table">
                        <thead>
                            <tr>
                                <th>{{ trans('erp/general.ammortamenti.asset') }}</th>
                                <th>{{ trans('erp/general.ammortamenti.category') }}</th>
                                <th class="text-center">{{ trans('erp/general.ammortamenti.purchase_year') }}</th>
                                <th class="text-right">{{ trans('erp/general.ammortamenti.cost') }}</th>
                                <th class="text-center">{{ trans('erp/general.ammortamenti.coefficiente') }}</th>
                                <th class="text-right">{{ trans('erp/general.ammortamenti.quota_year') }}</th>
                                <th class="text-right">{{ trans('erp/general.ammortamenti.fondo') }}</th>
                                <th class="text-right">{{ trans('erp/general.ammortamenti.residuo') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td><a href="{{ route('hardware.show', $row['asset']->id) }}">{{ $row['asset']->asset_tag }}</a> {{ $row['asset']->name }}</td>
                                    <td>{{ $row['category'] ?? '—' }}</td>
                                    <td class="text-center">{{ $row['purchase_year'] }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($row['cost']) }}</td>
                                    <td class="text-center">{{ $row['coefficiente'] }}%</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($row['quota_year']) }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($row['fondo']) }}</td>
                                    <td class="text-right">
                                        {{ \App\Helpers\Helper::formatCurrencyOutput($row['residuo']) }}
                                        @if ($row['fully_depreciated'])<span class="label label-default">{{ trans('erp/general.ammortamenti.fully') }}</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">{{ trans('erp/general.ammortamenti.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                        @if ($rows->isNotEmpty())
                            <tfoot>
                                <tr style="font-weight:bold; border-top:2px solid #ddd;">
                                    <td colspan="3">{{ trans('erp/general.ammortamenti.total') }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($totals['cost']) }}</td>
                                    <td></td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($totals['quota_year']) }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($totals['fondo']) }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($totals['residuo']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                    <p class="help-block">{{ trans('erp/general.ammortamenti.note') }}</p>
                </div>
            </div>
        </div>
    </div>
@stop

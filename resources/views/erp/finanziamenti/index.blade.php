@extends('layouts/default')
@php($f = fn ($v) => \App\Helpers\Helper::formatCurrencyOutput($v))
@section('title'){{ trans('erp/finanziamenti.title') }} @parent @stop
@section('content')
<div class="row"><div class="col-md-12">
    <div class="box box-default">
        <div class="box-header with-border">
            <h2 class="box-title">{{ trans('erp/finanziamenti.title') }}</h2>
            <div class="box-tools pull-right">
                <span style="margin-right:12px;">{{ trans('erp/finanziamenti.tot_residuo') }}: <strong class="text-danger">{{ $f($totResiduo) }}</strong></span>
                @can('update', \App\Models\CustomerContract::class)<a href="{{ route('erp.finanziamenti.create') }}" class="btn btn-primary btn-sm">{{ trans('erp/finanziamenti.new') }}</a>@endcan
            </div>
        </div>
        <div class="box-body">
            <table class="table table-striped snipe-table">
                <thead><tr>
                    <th>{{ trans('erp/finanziamenti.nome') }}</th>
                    <th class="text-right">{{ trans('erp/finanziamenti.rata') }}</th>
                    <th class="text-center">{{ trans('erp/finanziamenti.rate') }}</th>
                    <th class="text-right">{{ trans('erp/finanziamenti.pagato') }}</th>
                    <th class="text-right">{{ trans('erp/finanziamenti.residuo') }}</th>
                    <th></th><th></th>
                </tr></thead>
                <tbody>
                    @forelse ($finanziamenti as $fin)
                        <tr>
                            <td>{{ $fin->nome }} @if($fin->stato === 'da_confermare')<span class="label label-warning">{{ trans('erp/finanziamenti.da_confermare') }}</span>@endif</td>
                            <td class="text-right">{{ $f($fin->rata_mensile) }}</td>
                            <td class="text-center">{{ $fin->rate_pagate }}/{{ $fin->rate_totali }}</td>
                            <td class="text-right">{{ $f($fin->pagato) }}</td>
                            <td class="text-right text-danger"><strong>{{ $f($fin->residuo) }}</strong></td>
                            <td class="text-right">@can('update', \App\Models\CustomerContract::class)<a href="{{ route('erp.finanziamenti.edit', $fin) }}" class="btn btn-xs btn-default"><x-icon type="edit"/></a>@endcan</td>
                            <td class="text-right">@can('update', \App\Models\CustomerContract::class)<form method="POST" action="{{ route('erp.finanziamenti.destroy', $fin) }}" style="display:inline" onsubmit="return confirm('{{ trans('general.sure_to_delete') }}')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger"><x-icon type="delete"/></button></form>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ trans('erp/finanziamenti.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div></div>
@stop

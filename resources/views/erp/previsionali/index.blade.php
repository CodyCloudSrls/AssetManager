@extends('layouts/default')
@php($f = fn ($v) => \App\Helpers\Helper::formatCurrencyOutput($v))
@section('title'){{ trans('erp/previsionali.title') }} @parent @stop
@section('content')
<div class="row"><div class="col-md-12">
    <div class="box box-default">
        <div class="box-header with-border">
            <h2 class="box-title">{{ trans('erp/previsionali.title') }}</h2>
            @can('update', \App\Models\CustomerContract::class)<div class="box-tools pull-right"><a href="{{ route('erp.previsionali.create') }}" class="btn btn-primary btn-sm">{{ trans('erp/previsionali.new') }}</a></div>@endcan
        </div>
        <div class="box-body">
            <p class="help-block">{{ trans('erp/previsionali.intro') }}</p>
            <table class="table table-striped snipe-table">
                <thead><tr>
                    <th>{{ trans('erp/previsionali.voce') }}</th>
                    @foreach ($previsionali as $p)<th class="text-right">{{ $p->anno }}</th>@endforeach
                    <th></th>
                </tr></thead>
                <tbody>
                    @if ($previsionali->isEmpty())
                        <tr><td colspan="2" class="text-center text-muted">{{ trans('erp/previsionali.empty') }}</td></tr>
                    @else
                        @php($rows = [
                            ['k' => 'ricavi', 'l' => trans('erp/previsionali.ricavi')],
                            ['k' => 'ricavi_ricorrente', 'l' => trans('erp/previsionali.ricavi_ricorrente'), 'muted' => true],
                            ['k' => 'cogs', 'l' => trans('erp/previsionali.cogs')],
                            ['k' => 'opex', 'l' => trans('erp/previsionali.opex')],
                            ['k' => 'personale', 'l' => trans('erp/previsionali.personale')],
                        ])
                        @foreach ($rows as $r)
                            <tr @if(!empty($r['muted']))class="text-muted"@endif>
                                <td>{{ $r['l'] }}</td>
                                @foreach ($previsionali as $p)<td class="text-right">{{ $f($p->{$r['k']}) }}</td>@endforeach
                                <td></td>
                            </tr>
                        @endforeach
                        <tr style="font-weight:bold; border-top:2px solid #ddd;">
                            <td>{{ trans('erp/previsionali.ebit') }}</td>
                            @foreach ($previsionali as $p)<td class="text-right {{ $p->ebit < 0 ? 'text-danger' : 'text-success' }}">{{ $f($p->ebit) }}</td>@endforeach
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            @foreach ($previsionali as $p)
                                <td class="text-right">
                                    @can('update', \App\Models\CustomerContract::class)
                                        <a href="{{ route('erp.previsionali.edit', $p) }}" class="btn btn-xs btn-default"><x-icon type="edit"/></a>
                                        <form method="POST" action="{{ route('erp.previsionali.destroy', $p) }}" style="display:inline" onsubmit="return confirm('{{ trans('general.sure_to_delete') }}')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger"><x-icon type="delete"/></button></form>
                                    @endcan
                                </td>
                            @endforeach
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div></div>
@stop

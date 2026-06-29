@extends('layouts/default')

@php($f = fn ($v) => \App\Helpers\Helper::formatCurrencyOutput($v))

@section('title'){{ trans('erp/bilanci.title') }} @parent @stop

@section('content')
<div class="row"><div class="col-md-12">
    <div class="box box-default">
        <div class="box-header with-border">
            <h2 class="box-title">{{ trans('erp/bilanci.title') }}</h2>
            @can('update', \App\Models\CustomerContract::class)
                <div class="box-tools pull-right"><a href="{{ route('erp.bilanci.create') }}" class="btn btn-primary btn-sm">{{ trans('erp/bilanci.new') }}</a></div>
            @endcan
        </div>
        <div class="box-body">
            <p class="help-block">{{ trans('erp/bilanci.intro') }}</p>
            <table class="table table-striped snipe-table">
                <thead>
                    <tr>
                        <th>{{ trans('erp/bilanci.anno') }}</th>
                        <th class="text-right">{{ trans('erp/bilanci.ricavi') }}</th>
                        <th class="text-right">{{ trans('erp/bilanci.costi') }}</th>
                        <th class="text-right">{{ trans('erp/bilanci.costo_personale') }}</th>
                        <th class="text-right">{{ trans('erp/bilanci.ammortamenti') }}</th>
                        <th class="text-right">{{ trans('erp/bilanci.utile') }}</th>
                        <th class="text-right">{{ trans('erp/bilanci.imposte') }}</th>
                        <th></th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bilanci as $b)
                        <tr>
                            <td><strong>{{ $b->anno }}</strong> @unless($b->is_deposited)<span class="label label-warning">{{ trans('erp/bilanci.stima') }}</span>@endunless</td>
                            <td class="text-right">{{ $f($b->ricavi) }}</td>
                            <td class="text-right">{{ $f($b->costi) }}</td>
                            <td class="text-right">{{ $f($b->costo_personale) }}</td>
                            <td class="text-right">{{ $f($b->ammortamenti) }}</td>
                            <td class="text-right {{ $b->utile < 0 ? 'text-danger' : 'text-success' }}">{{ $f($b->utile) }}</td>
                            <td class="text-right">{{ $f($b->imposte) }}</td>
                            <td class="text-right">
                                @can('update', \App\Models\CustomerContract::class)
                                    <a href="{{ route('erp.bilanci.edit', $b) }}" class="btn btn-xs btn-default"><x-icon type="edit"/></a>
                                @endcan
                            </td>
                            <td class="text-right">
                                @can('update', \App\Models\CustomerContract::class)
                                    <form method="POST" action="{{ route('erp.bilanci.destroy', $b) }}" style="display:inline" onsubmit="return confirm('{{ trans('general.sure_to_delete') }}')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger"><x-icon type="delete"/></button></form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">{{ trans('erp/bilanci.empty') }}</td></tr>
                    @endforelse
                </tbody>
                @if ($bilanci->isNotEmpty())
                    <tfoot><tr style="font-weight:bold; border-top:2px solid #ddd;"><td colspan="5">{{ trans('erp/bilanci.utile_cumulato') }}</td><td class="text-right {{ $utileCumulato < 0 ? 'text-danger' : 'text-success' }}">{{ $f($utileCumulato) }}</td><td colspan="3"></td></tr></tfoot>
                @endif
            </table>
        </div>
    </div>
</div></div>
@stop

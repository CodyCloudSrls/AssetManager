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

                    <table class="table table-striped snipe-table">
                        <thead>
                            <tr>
                                <th>{{ trans('erp/notule.competence_date') }}</th>
                                <th>{{ trans('erp/notule.professional') }}</th>
                                <th>{{ trans('erp/notule.description') }}</th>
                                <th class="text-right">{{ trans('erp/notule.amount') }}</th>
                                <th class="text-right">{{ trans('erp/notule.paid') }}</th>
                                <th class="text-right">{{ trans('erp/notule.residuo') }}</th>
                                <th>{{ trans('erp/notule.status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($notule as $n)
                                <tr>
                                    <td>{{ optional($n->competence_date)->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $n->display_name }}</td>
                                    <td>{{ $n->description }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($n->amount) }}</td>
                                    <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($n->paid_amount) }}</td>
                                    <td class="text-right {{ $n->residuo > 0 ? 'text-danger' : '' }}">{{ \App\Helpers\Helper::formatCurrencyOutput($n->residuo) }}</td>
                                    <td>
                                        @php($cls = ['pending' => 'label-warning', 'invoiced' => 'label-info', 'paid' => 'label-success'][$n->status] ?? 'label-default')
                                        <span class="label {{ $cls }}">{{ $n->status_label }}</span>
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
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">{{ trans('erp/notule.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $notule->links() }}
                    <p class="help-block">{{ trans('erp/notule.dedup_note') }}</p>
                </div>
            </div>
        </div>
    </div>
@stop

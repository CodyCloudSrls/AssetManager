@extends('layouts/default')

@section('title')
    {{ trans('erp/riconciliazione.title') }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('erp/riconciliazione.title') }}</h2>
                    <div class="box-tools pull-right">
                        <form method="GET" action="{{ route('erp.riconciliazione') }}" class="form-inline">
                            @if ($channel)<input type="hidden" name="channel" value="{{ $channel }}">@endif
                            <select name="year" class="form-control input-sm" onchange="this.form.submit()" aria-label="{{ trans('general.year') }}">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" {{ (int) $y === (int) $year ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="box-body">
                    <p class="help-block">{{ trans('erp/riconciliazione.intro') }}</p>

                    @if (! $hasData)
                        <p class="text-muted">{{ trans('erp/riconciliazione.empty') }}</p>
                    @else
                        <table class="table table-striped snipe-table">
                            <thead>
                                <tr>
                                    <th>{{ trans('erp/riconciliazione.channel') }}</th>
                                    <th class="text-right">{{ trans('erp/riconciliazione.movements') }}</th>
                                    <th class="text-right">{{ trans('erp/riconciliazione.collected') }}</th>
                                    <th class="text-right">{{ trans('erp/riconciliazione.unmatched_count') }}</th>
                                    <th class="text-right">{{ trans('erp/riconciliazione.unmatched_amount') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($channels as $c)
                                    <tr class="{{ $c->account_name === 'TS Pay' ? 'info' : '' }}">
                                        <td><strong>{{ $c->account_name ?: '—' }}</strong>{!! $c->account_name === 'TS Pay' ? ' <span class="label label-primary">TS Pay</span>' : '' !!}</td>
                                        <td class="text-right">{{ number_format($c->movimenti) }}</td>
                                        <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($c->totale) }}</td>
                                        <td class="text-right {{ $c->non_collegati > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($c->non_collegati) }}</td>
                                        <td class="text-right {{ $c->non_collegato > 0 ? 'text-danger' : 'text-muted' }}">{{ \App\Helpers\Helper::formatCurrencyOutput($c->non_collegato) }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('erp.riconciliazione', ['year' => $year, 'channel' => $c->account_name]) }}" class="btn btn-xs btn-default">{{ trans('erp/riconciliazione.detail') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">{{ trans('erp/riconciliazione.empty_year') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($hasData && $unmatched->isNotEmpty())
        <div class="row">
            <div class="col-md-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ trans('erp/riconciliazione.to_reconcile') }} @if ($channel)— {{ $channel }}@endif <span class="badge">{{ $unmatched->count() }}</span></h3>
                    </div>
                    <div class="box-body">
                        <p class="help-block">{{ trans('erp/riconciliazione.to_reconcile_help') }}</p>
                        <table class="table table-condensed snipe-table">
                            <thead>
                                <tr>
                                    <th>{{ trans('erp/riconciliazione.date') }}</th>
                                    <th>{{ trans('erp/riconciliazione.channel') }}</th>
                                    <th class="text-right">{{ trans('erp/riconciliazione.amount') }}</th>
                                    <th>{{ trans('erp/riconciliazione.counterpart') }}</th>
                                    <th>{{ trans('erp/riconciliazione.description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($unmatched as $m)
                                    <tr>
                                        <td>{{ optional($m->entry_date)->format('d/m/Y') }}</td>
                                        <td>{{ $m->account_name }}</td>
                                        <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($m->amount) }}</td>
                                        <td>{{ $m->entity_name ?: '—' }}</td>
                                        <td class="text-muted">{{ $m->description }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($channel && $detail->isNotEmpty())
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ trans('erp/riconciliazione.channel_detail', ['channel' => $channel]) }}</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed snipe-table">
                            <thead>
                                <tr>
                                    <th>{{ trans('erp/riconciliazione.date') }}</th>
                                    <th class="text-right">{{ trans('erp/riconciliazione.amount') }}</th>
                                    <th>{{ trans('erp/riconciliazione.counterpart') }}</th>
                                    <th>{{ trans('erp/riconciliazione.matched_document') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detail as $m)
                                    <tr>
                                        <td>{{ optional($m->entry_date)->format('d/m/Y') }}</td>
                                        <td class="text-right">{{ \App\Helpers\Helper::formatCurrencyOutput($m->amount) }}</td>
                                        <td>{{ $m->entity_name ?: '—' }}</td>
                                        <td>
                                            @if ($m->is_matched)
                                                <span class="label label-success">{{ trans('erp/riconciliazione.matched') }}</span>
                                                @if ($m->document)<span class="text-muted"> {{ $m->document->number ? '#'.$m->document->number : ('FiC '.$m->document_fic_id) }}</span>@endif
                                            @else
                                                <span class="label label-warning">{{ trans('erp/riconciliazione.not_matched') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

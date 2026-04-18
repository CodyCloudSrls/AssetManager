@extends('layouts/default')

@section('title')
{{ trans('general.tickets') }}
@parent
@stop

@section('header_right')
    @can('create', \App\Models\Ticket::class)
        <a href="{{ route('tickets.create') }}" class="btn btn-primary pull-right">
            <x-icon type="create" />
            {{ trans('admin/tickets/form.create') }}
        </a>
    @endcan
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.tickets') }}</h3>
            </div>
            <div class="box-body">
                <div class="btn-toolbar" style="margin-bottom: 18px;">
                    @php
                        $queueButtons = [
                            'all' => trans('general.all'),
                            'open' => trans('admin/tickets/general.open_queue'),
                            'mine' => trans('admin/tickets/general.my_queue'),
                            'unassigned' => trans('admin/tickets/general.unassigned_queue'),
                            'waiting_customer' => trans('admin/tickets/general.waiting_customer_queue'),
                            'waiting_vendor' => trans('admin/tickets/general.waiting_vendor_queue'),
                            'public' => trans('admin/tickets/general.public_queue'),
                            'sla_at_risk' => trans('admin/tickets/general.sla_at_risk_queue'),
                            'closed' => trans('admin/tickets/general.closed_queue'),
                        ];
                    @endphp
                    @foreach ($queueButtons as $queueKey => $queueLabel)
                        @php
                            $queueParams = request()->except('queue');
                            if ($queueKey !== 'all') {
                                $queueParams['queue'] = $queueKey;
                            }
                        @endphp
                        <a
                            href="{{ route('tickets.index', $queueParams) }}"
                            class="btn {{ $currentQueue === $queueKey ? 'btn-primary' : 'btn-default' }}"
                            style="margin-right: 8px; margin-bottom: 8px;">
                            {{ $queueLabel }} <span class="badge">{{ $queueCounts[$queueKey] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>
                <x-table.tickets :route="route('api.tickets.index', request()->only(['queue', 'ticket_status_id', 'ticket_priority_id', 'ticket_type_id', 'assignee_id', 'requester_id', 'company_id', 'source', 'unassigned', 'status_type']))"/>
            </div>
        </div>
    </div>
</div>
@endsection

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@endsection

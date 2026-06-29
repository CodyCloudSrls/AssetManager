@extends('layouts/default')

@section('title')
    {{ trans('erp/general.title') }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title"><x-icon type="erp" class="fa-fw" /> {{ trans('erp/general.title') }}</h2>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ trans('erp/general.intro') }}</p>

                    <div class="row">
                        {{-- Active module: Contracts --}}
                        @can('view', \App\Models\CustomerContract::class)
                            <div class="col-md-4 col-sm-6" style="margin-bottom:16px;">
                                <a href="{{ route('contracts.index') }}" class="box box-default" style="display:block; height:100%; padding:16px; text-decoration:none;">
                                    <h4 style="margin-top:0;"><x-icon type="long-arrow-right" class="fa-fw" /> {{ trans('erp/general.modules.contracts') }}
                                        <span class="label label-success pull-right">{{ trans('erp/general.status_active') }}</span></h4>
                                    <p class="text-muted">{{ trans('erp/general.modules.contracts_help') }}</p>
                                </a>
                            </div>
                        @endcan

                        {{-- Planned financial-control modules (PDF roadmap) --}}
                        @foreach ([
                            'pnl' => 'chart-line',
                            'cashflow' => 'chart-line',
                            'deadlines' => 'warning',
                            'reconciliation' => 'long-arrow-right',
                            'cockpit' => 'chart-line',
                            'payroll' => 'users',
                        ] as $moduleKey => $moduleIcon)
                            <div class="col-md-4 col-sm-6" style="margin-bottom:16px;">
                                <div class="box box-default" style="height:100%; padding:16px; opacity:.7;">
                                    <h4 style="margin-top:0;"><x-icon :type="$moduleIcon" class="fa-fw" /> {{ trans('erp/general.modules.'.$moduleKey) }}
                                        <span class="label label-default pull-right">{{ trans('erp/general.status_planned') }}</span></h4>
                                    <p class="text-muted">{{ trans('erp/general.modules.'.$moduleKey.'_help') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="callout callout-info" style="margin-top:8px;">
                        <p>{{ trans('erp/general.connectors_note') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

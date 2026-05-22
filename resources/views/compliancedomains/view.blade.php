@extends('layouts/default')

@section('title')
{{ $compliancedomain->name }}
@parent
@stop

@section('content')
    <x-container>
        <x-box>
            <div class="row">
                <div class="col-md-8">
                    <dl class="dl-horizontal">
                        <dt>{{ trans('admin/compliancedomains/table.name') }}</dt>
                        <dd>{{ $compliancedomain->name }}</dd>
                        <dt>{{ trans('admin/compliancedomains/table.key') }}</dt>
                        <dd>{{ $compliancedomain->key }}</dd>
                        <dt>{{ trans('admin/compliancedomains/table.is_active') }}</dt>
                        <dd>{{ $compliancedomain->is_active ? trans('general.yes') : trans('general.no') }}</dd>
                        <dt>{{ trans('admin/compliancedomains/table.is_system') }}</dt>
                        <dd>{{ $compliancedomain->is_system ? trans('general.yes') : trans('general.no') }}</dd>
                        <dt>{{ trans('general.document_frameworks') }}</dt>
                        <dd>{{ $frameworksCount }}</dd>
                        @if ($compliancedomain->description)
                            <dt>{{ trans('admin/compliancedomains/table.description') }}</dt>
                            <dd>{{ $compliancedomain->description }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </x-box>
    </x-container>
@stop

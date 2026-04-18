@extends('layouts/default')

@section('title')
    {{ $documenttype->name }}
    @parent
@stop

@section('content')
    <x-container>
        <div class="row">
            <div class="col-md-4">
                <x-box>
                    <dl class="dl-horizontal" style="margin-bottom: 0;">
                        <dt>{{ trans('admin/documenttypes/table.name') }}</dt>
                        <dd>{{ $documenttype->name }}</dd>
                        <dt>{{ trans('admin/documenttypes/table.slug') }}</dt>
                        <dd><code>{{ $documenttype->slug }}</code></dd>
                        <dt>{{ trans('admin/documenttypes/table.is_active') }}</dt>
                        <dd>{{ $documenttype->is_active ? trans('general.yes') : trans('general.no') }}</dd>
                        <dt>{{ trans('admin/documenttypes/table.sort_order') }}</dt>
                        <dd>{{ $documenttype->sort_order }}</dd>
                        <dt>{{ trans('general.company') }}</dt>
                        <dd>{{ $documenttype->company?->name ?? trans('general.na') }}</dd>
                        <dt>{{ trans('general.template_visibility.label') }}</dt>
                        <dd>{{ $documenttype->visibility_label }}</dd>
                        <dt>{{ trans('general.documents') }}</dt>
                        <dd>{{ $documenttype->documents_count }}</dd>
                        @if ($documenttype->description)
                            <dt>{{ trans('admin/documenttypes/table.description') }}</dt>
                            <dd>{{ $documenttype->description }}</dd>
                        @endif
                    </dl>
                </x-box>
            </div>
            <div class="col-md-8">
                <x-box>
                    <x-table.documents :route="route('api.documents.index', ['document_type_id' => $documenttype->id])" />
                </x-box>
            </div>
        </div>
    </x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop

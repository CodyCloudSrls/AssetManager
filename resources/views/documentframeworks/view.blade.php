@extends('layouts/default')

@section('title')
    {{ $documentframework->name }}
    @parent
@stop

@section('content')
    <x-container>
        <div class="row">
            <div class="col-md-4">
                <x-box>
                    <dl class="dl-horizontal" style="margin-bottom: 0;">
                        <dt>{{ trans('admin/documentframeworks/table.name') }}</dt>
                        <dd>{{ $documentframework->name }}</dd>
                        <dt>{{ trans('admin/documentframeworks/table.slug') }}</dt>
                        <dd><code>{{ $documentframework->slug }}</code></dd>
                        <dt>{{ trans('admin/documentframeworks/table.is_active') }}</dt>
                        <dd>{{ $documentframework->is_active ? trans('general.yes') : trans('general.no') }}</dd>
                        <dt>{{ trans('admin/documentframeworks/table.sort_order') }}</dt>
                        <dd>{{ $documentframework->sort_order }}</dd>
                        <dt>{{ trans('general.documents') }}</dt>
                        <dd>{{ $documentframework->documents_count }}</dd>
                        @if ($documentframework->description)
                            <dt>{{ trans('admin/documentframeworks/table.description') }}</dt>
                            <dd>{{ $documentframework->description }}</dd>
                        @endif
                    </dl>
                </x-box>
            </div>
            <div class="col-md-8">
                <x-box>
                    <x-table.documents :route="route('api.documents.index', ['document_framework_id' => $documentframework->id])" />
                </x-box>
            </div>
        </div>
    </x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop

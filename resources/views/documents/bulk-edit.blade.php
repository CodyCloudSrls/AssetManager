@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_edit') }} {{ trans('general.documents') }}
    @parent
@stop

@section('header_right')
    <a href="{{ URL::previous() }}" class="btn btn-sm btn-theme pull-right">
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
    <div class="row">
        <div class="col-md-9 col-md-offset-1">
            <form class="form-horizontal" method="post" action="{{ route('documents.bulk.update') }}" autocomplete="off" role="form">
                @csrf
                <input type="hidden" name="bulk_actions" value="edit">

                @foreach ($documents as $document)
                    <input type="hidden" name="ids[]" value="{{ $document->id }}">
                @endforeach

                <div class="box box-default">
                    <div class="box-body">
                        <div class="callout callout-warning">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ trans('general.bulk_edit_about_to') }}
                            {{ trans('general.documents') }}: {{ number_format($documents->count()) }}
                        </div>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('general.name') }}</th>
                                    <th>{{ trans('admin/documents/form.document_number') }}</th>
                                    <th>{{ trans('general.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($documents as $document)
                                    <tr>
                                        <td>{{ $document->name }}</td>
                                        <td>{{ $document->document_number }}</td>
                                        <td>{{ $documentStatuses[$document->status] ?? $document->status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="form-group {{ $errors->has('status') ? ' has-error' : '' }}">
                            <label for="status" class="col-md-3 control-label">{{ trans('general.status') }}</label>
                            <div class="col-md-5">
                                <select class="form-control select2" name="status" id="status" aria-label="status">
                                    <option value="">{{ trans('general.do_not_change') }}</option>
                                    @foreach ($documentStatuses as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected(old('status') === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                                <p class="help-block">{{ trans('admin/documents/form.status_help') }}</p>
                                {!! $errors->first('status', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_status" value="1" @checked(old('apply_status'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        @include ('partials.forms.edit.user-select', [
                            'translated_name' => trans('admin/documents/form.owner'),
                            'fieldname' => 'owner_id',
                            'item' => new \App\Models\Document,
                            'required' => 'false',
                            'hide_new' => 'true',
                            'select_id' => 'bulk_owner_id',
                        ])
                        <div class="form-group">
                            <div class="col-md-4 col-md-offset-8">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_owner_id" value="1" @checked(old('apply_owner_id'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        @include ('partials.forms.edit.document-type-select', [
                            'translated_name' => trans('admin/documents/form.document_type'),
                            'fieldname' => 'document_type_id',
                            'item' => new \App\Models\Document,
                            'required' => 'false',
                            'hide_new' => 'true',
                        ])
                        <div class="form-group">
                            <div class="col-md-4 col-md-offset-8">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_document_type_id" value="1" @checked(old('apply_document_type_id'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('document_area') ? ' has-error' : '' }}">
                            <label for="document_area" class="col-md-3 control-label">{{ trans('admin/documents/form.document_area') }}</label>
                            <div class="col-md-5">
                                <select class="form-control select2" name="document_area" id="document_area" aria-label="document_area">
                                    <option value="">{{ trans('general.do_not_change') }}</option>
                                    @foreach ($documentAreaOptions as $areaValue => $areaLabel)
                                        <option value="{{ $areaValue }}" @selected(old('document_area') === $areaValue)>{{ $areaLabel }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('document_area', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_document_area" value="1" @checked(old('apply_document_area'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        @foreach (['classification', 'retention_period', 'scope'] as $textField)
                            <div class="form-group {{ $errors->has($textField) ? ' has-error' : '' }}">
                                <label for="{{ $textField }}" class="col-md-3 control-label">{{ trans('admin/documents/form.'.$textField) }}</label>
                                <div class="col-md-5">
                                    <input class="form-control" type="text" name="{{ $textField }}" id="{{ $textField }}" value="{{ old($textField) }}">
                                    {!! $errors->first($textField, '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                                </div>
                                <div class="col-md-4">
                                    <label class="form-control">
                                        <input type="checkbox" name="apply_{{ $textField }}" value="1" @checked(old('apply_'.$textField))>
                                        {{ trans('general.apply') }}
                                    </label>
                                </div>
                            </div>
                        @endforeach

                        @foreach (['issued_at', 'effective_at', 'next_review_at'] as $dateField)
                            <div class="form-group {{ $errors->has($dateField) ? ' has-error' : '' }}">
                                <label for="{{ $dateField }}" class="col-md-3 control-label">{{ trans('admin/documents/form.'.$dateField) }}</label>
                                <div class="col-md-3">
                                    <x-input.datepicker name="{{ $dateField }}" :value="old($dateField)" placeholder="{{ trans('general.select_date') }}"/>
                                    {!! $errors->first($dateField, '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                                </div>
                                <div class="col-md-4 col-md-offset-2">
                                    <label class="form-control">
                                        <input type="checkbox" name="apply_{{ $dateField }}" value="1" @checked(old('apply_'.$dateField))>
                                        {{ trans('general.apply') }}
                                    </label>
                                </div>
                            </div>
                        @endforeach

                        <div class="form-group {{ $errors->has('control_url') ? ' has-error' : '' }}">
                            <label for="control_url" class="col-md-3 control-label">{{ trans('admin/documents/form.control_url') }}</label>
                            <div class="col-md-5">
                                <input class="form-control" type="url" name="control_url" id="control_url" value="{{ old('control_url') }}">
                                {!! $errors->first('control_url', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_control_url" value="1" @checked(old('apply_control_url'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        {!! $errors->first('bulk_actions', '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
                    </div>

                    <div class="box-footer text-right">
                        <a class="btn btn-link pull-left" href="{{ route('documents.index') }}">{{ trans('button.cancel') }}</a>
                        <button type="submit" class="btn btn-success" id="submit-button">
                            <x-icon type="checkmark" />
                            {{ trans('general.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('moar_scripts')
    <script nonce="{{ csrf_token() }}">
        $(function () {
            var bulkApplyFields = {
                status: 'apply_status',
                owner_id: 'apply_owner_id',
                document_type_id: 'apply_document_type_id',
                document_area: 'apply_document_area',
                classification: 'apply_classification',
                retention_period: 'apply_retention_period',
                scope: 'apply_scope',
                issued_at: 'apply_issued_at',
                effective_at: 'apply_effective_at',
                next_review_at: 'apply_next_review_at',
                control_url: 'apply_control_url'
            };

            var markApplyField = function (fieldName) {
                var applyFieldName = bulkApplyFields[fieldName];

                if (applyFieldName) {
                    $('input[name="' + applyFieldName + '"]').prop('checked', true);
                }
            };

            $.each(bulkApplyFields, function (fieldName) {
                $('[name="' + fieldName + '"]').on('input change select2:select select2:unselect select2:clear', function () {
                    markApplyField(fieldName);
                });
            });
        });
    </script>
@stop

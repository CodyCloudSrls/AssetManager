@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_edit') }} {{ trans('admin/tenantservices/general.title') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('tenants.services.index', $tenant) }}" class="btn btn-sm btn-theme pull-right">
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
    <div class="row">
        <div class="col-md-9 col-md-offset-1">
            <form class="form-horizontal" method="post" action="{{ route('tenants.services.bulkeditsave', $tenant) }}" autocomplete="off" role="form">
                @csrf

                @foreach ($services as $service)
                    <input type="hidden" name="ids[]" value="{{ $service->id }}">
                @endforeach

                <div class="box box-default">
                    <div class="box-body">
                        <div class="callout callout-warning">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ trans('general.bulk_edit_about_to') }}
                            {{ trans('admin/tenantservices/general.title') }}: {{ number_format($services->count()) }}
                        </div>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('admin/tenantservices/general.macro_area') }}</th>
                                    <th>{{ trans('admin/tenantservices/general.name') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($services as $service)
                                    <tr>
                                        <td>{{ $service->macro_area_label }}</td>
                                        <td>{{ $service->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Assigned relevance --}}
                        <div class="form-group {{ $errors->has('relevance_override') ? ' has-error' : '' }}">
                            <label for="relevance_override" class="col-md-3 control-label">{{ trans('admin/tenantservices/general.relevance_assigned') }}</label>
                            <div class="col-md-5">
                                <select class="form-control select2" name="relevance_override" id="relevance_override" aria-label="relevance_override">
                                    <option value="">{{ trans('admin/tenantservices/general.relevance_preassigned') }}</option>
                                    @foreach ($impactOptions as $impactValue => $impactLabel)
                                        <option value="{{ $impactValue }}" @selected(old('relevance_override') === $impactValue)>{{ $impactLabel }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('relevance_override', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_relevance_override" value="1" @checked(old('apply_relevance_override'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        {{-- Active state --}}
                        <div class="form-group {{ $errors->has('is_active_state') ? ' has-error' : '' }}">
                            <label for="is_active_state" class="col-md-3 control-label">{{ trans('general.status') }}</label>
                            <div class="col-md-5">
                                <select class="form-control select2" name="is_active_state" id="is_active_state" aria-label="is_active_state">
                                    <option value="1" @selected(old('is_active_state', '1') === '1')>{{ trans('admin/tenantservices/general.active') }}</option>
                                    <option value="0" @selected(old('is_active_state') === '0')>{{ trans('admin/tenantservices/general.inactive') }}</option>
                                </select>
                                {!! $errors->first('is_active_state', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_is_active" value="1" @checked(old('apply_is_active'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        {!! $errors->first('bulk_actions', '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
                    </div>

                    <div class="box-footer text-right">
                        <a class="btn btn-link pull-left" href="{{ route('tenants.services.index', $tenant) }}">{{ trans('button.cancel') }}</a>
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
                relevance_override: 'apply_relevance_override',
                is_active_state: 'apply_is_active'
            };

            $.each(bulkApplyFields, function (fieldName, applyFieldName) {
                $('[name="' + fieldName + '"]').on('input change select2:select', function () {
                    $('input[name="' + applyFieldName + '"]').prop('checked', true);
                });
            });
        });
    </script>
@stop

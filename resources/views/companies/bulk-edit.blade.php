@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_edit') }} &mdash; {{ trans('general.companies') }}
    @parent
@stop

@section('content')
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <form class="form-horizontal" method="POST" action="{{ route('companies.bulksave') }}" autocomplete="off" role="form">
            @csrf
            @foreach ($ids as $id)
                <input type="hidden" name="ids[]" value="{{ $id }}">
            @endforeach

            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.bulk_edit') }}</h2>
                </div>

                <div class="box-body">
                    <div class="callout callout-warning">
                        <p><i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ trans_choice('general.bulk_edit_count', count($companies), ['count' => count($companies)]) }}</p>
                    </div>

                    <table class="table table-striped snipe-table" style="margin-bottom:18px;">
                        <thead>
                            <tr><th>{{ trans('general.name') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $company)
                                <tr><td>{{ $company->name }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>

                    @php
                        $bulkCompanyFields = [
                            'phone' => trans('admin/companies/table.phone'),
                            'fax'   => trans('general.fax'),
                            'email' => trans('general.email'),
                        ];
                    @endphp

                    @foreach ($bulkCompanyFields as $field => $label)
                        <div class="form-group {{ $errors->has($field) ? ' has-error' : '' }}">
                            <label for="{{ $field }}" class="col-md-3 control-label">{{ $label }}</label>
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="{{ $field }}" id="{{ $field }}" value="{{ old($field) }}">
                                {!! $errors->first($field, '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_{{ $field }}" value="1" @checked(old('apply_'.$field))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>
                    @endforeach

                    {{-- Notes --}}
                    <div class="form-group">
                        <label for="notes" class="col-md-3 control-label">{{ trans('general.notes') }}</label>
                        <div class="col-md-5">
                            <textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-control">
                                <input type="checkbox" name="apply_notes" value="1" @checked(old('apply_notes'))>
                                {{ trans('general.apply') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="box-footer text-right">
                    <a class="btn btn-link pull-left" href="{{ route('companies.index') }}">{{ trans('button.cancel') }}</a>
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
            var bulkApplyFields = ['phone', 'fax', 'email', 'notes'];
            $.each(bulkApplyFields, function (i, fieldName) {
                $('[name="' + fieldName + '"]').on('input change', function () {
                    $('input[name="apply_' + fieldName + '"]').prop('checked', true);
                });
            });
        });
    </script>
@stop

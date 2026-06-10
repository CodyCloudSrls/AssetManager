@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_edit') }} {{ trans('admin/contracts/general.contracts') }}
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
            <form class="form-horizontal" method="post" action="{{ route('contracts.bulkeditsave') }}" autocomplete="off" role="form">
                @csrf

                @foreach ($contracts as $contract)
                    <input type="hidden" name="ids[]" value="{{ $contract->id }}">
                @endforeach

                <div class="box box-default">
                    <div class="box-body">
                        <div class="callout callout-warning">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ trans('general.bulk_edit_about_to') }}
                            {{ trans('admin/contracts/general.contracts') }}: {{ number_format($contracts->count()) }}
                        </div>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('general.name') }}</th>
                                    <th>{{ trans('general.customer') }}</th>
                                    <th>{{ trans('general.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($contracts as $contract)
                                    <tr>
                                        <td>{{ $contract->name }}</td>
                                        <td>{{ $contract->customer?->name }}</td>
                                        <td>{{ $contract->status_label }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Status --}}
                        <div class="form-group {{ $errors->has('status') ? ' has-error' : '' }}">
                            <label for="status" class="col-md-3 control-label">{{ trans('general.status') }}</label>
                            <div class="col-md-5">
                                <select class="form-control select2" name="status" id="status" aria-label="status">
                                    <option value="">{{ trans('general.none') }}</option>
                                    @foreach ($statusOptions as $statusValue => $statusLabel)
                                        <option value="{{ $statusValue }}" @selected(old('status') === $statusValue)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('status', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_status" value="1" @checked(old('apply_status'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        {{-- Owner --}}
                        @include ('partials.forms.edit.user-select', [
                            'translated_name' => trans('admin/contracts/general.owner'),
                            'fieldname' => 'owner_id',
                            'item' => new \App\Models\CustomerContract,
                            'required' => 'false',
                            'hide_new' => 'true',
                            'select_id' => 'bulk_owner_id',
                        ])
                        <div class="form-group {{ $errors->has('owner_id') ? ' has-error' : '' }}">
                            <div class="col-md-4 col-md-offset-8">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_owner_id" value="1" @checked(old('apply_owner_id'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                            {!! $errors->first('owner_id', '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
                        </div>

                        {{-- Currency --}}
                        <div class="form-group {{ $errors->has('currency') ? ' has-error' : '' }}">
                            <label for="currency" class="col-md-3 control-label">{{ trans('admin/contracts/general.currency') }}</label>
                            <div class="col-md-2">
                                <input class="form-control" type="text" name="currency" id="currency" maxlength="3" value="{{ old('currency') }}" placeholder="EUR">
                                {!! $errors->first('currency', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4 col-md-offset-3">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_currency" value="1" @checked(old('apply_currency'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        {{-- Dates --}}
                        @foreach (['signed_at', 'starts_at', 'ends_at', 'renewal_due_at', 'notice_due_at'] as $dateField)
                            <div class="form-group {{ $errors->has($dateField) ? ' has-error' : '' }}">
                                <label for="{{ $dateField }}" class="col-md-3 control-label">{{ trans('admin/contracts/general.'.$dateField) }}</label>
                                <div class="col-md-3">
                                    <input class="form-control" type="date" name="{{ $dateField }}" id="{{ $dateField }}" value="{{ old($dateField) }}">
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

                        {!! $errors->first('bulk_actions', '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
                    </div>

                    <div class="box-footer text-right">
                        <a class="btn btn-link pull-left" href="{{ route('contracts.index') }}">{{ trans('button.cancel') }}</a>
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
                currency: 'apply_currency',
                signed_at: 'apply_signed_at',
                starts_at: 'apply_starts_at',
                ends_at: 'apply_ends_at',
                renewal_due_at: 'apply_renewal_due_at',
                notice_due_at: 'apply_notice_due_at'
            };

            $.each(bulkApplyFields, function (fieldName, applyFieldName) {
                $('[name="' + fieldName + '"]').on('input change select2:select select2:unselect select2:clear', function () {
                    $('input[name="' + applyFieldName + '"]').prop('checked', true);
                });
            });
        });
    </script>
@stop

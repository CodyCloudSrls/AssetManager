@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_edit') }} &mdash; {{ trans('admin/suppliers/table.suppliers') }}
    @parent
@stop

@section('content')
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <form class="form-horizontal" method="POST" action="{{ route('suppliers.bulksave') }}" autocomplete="off" role="form">
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
                            {{ trans_choice('general.bulk_edit_count', count($suppliers), ['count' => count($suppliers)]) }}</p>
                    </div>

                    <table class="table table-striped snipe-table" style="margin-bottom:18px;">
                        <thead>
                            <tr>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('general.company') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($suppliers as $supplier)
                                <tr>
                                    <td>{{ $supplier->name }}</td>
                                    <td>{{ $supplier->company?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- NIS relevant --}}
                    <div class="form-group">
                        <label for="nis_relevant_value" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_relevant') }}</label>
                        <div class="col-md-5">
                            <select class="form-control select2" name="nis_relevant_value" id="nis_relevant_value" aria-label="nis_relevant_value">
                                <option value="1" @selected(old('nis_relevant_value') === '1')>{{ trans('general.yes') }}</option>
                                <option value="0" @selected(old('nis_relevant_value', '0') === '0')>{{ trans('general.no') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-control">
                                <input type="checkbox" name="apply_nis_relevant" value="1" @checked(old('apply_nis_relevant'))>
                                {{ trans('general.apply') }}
                            </label>
                        </div>
                    </div>

                    {{-- NIS criticality --}}
                    <div class="form-group {{ $errors->has('nis_criticality') ? ' has-error' : '' }}">
                        <label for="nis_criticality" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_criticality') }}</label>
                        <div class="col-md-5">
                            <select class="form-control select2" name="nis_criticality" id="nis_criticality" aria-label="nis_criticality">
                                @foreach (\App\Models\Supplier::nisCriticalityOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('nis_criticality') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            {!! $errors->first('nis_criticality', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                        <div class="col-md-4">
                            <label class="form-control">
                                <input type="checkbox" name="apply_nis_criticality" value="1" @checked(old('apply_nis_criticality'))>
                                {{ trans('general.apply') }}
                            </label>
                        </div>
                    </div>

                    {{-- NIS relevance type --}}
                    <div class="form-group {{ $errors->has('nis_relevance_type') ? ' has-error' : '' }}">
                        <label for="nis_relevance_type" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_relevance_type') }}</label>
                        <div class="col-md-5">
                            <select class="form-control select2" name="nis_relevance_type" id="nis_relevance_type" aria-label="nis_relevance_type">
                                @foreach (\App\Models\Supplier::nisRelevanceTypeOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('nis_relevance_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            {!! $errors->first('nis_relevance_type', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                        <div class="col-md-4">
                            <label class="form-control">
                                <input type="checkbox" name="apply_nis_relevance_type" value="1" @checked(old('apply_nis_relevance_type'))>
                                {{ trans('general.apply') }}
                            </label>
                        </div>
                    </div>

                    {{-- NIS assessment status --}}
                    <div class="form-group {{ $errors->has('nis_assessment_status') ? ' has-error' : '' }}">
                        <label for="nis_assessment_status" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_assessment_status') }}</label>
                        <div class="col-md-5">
                            <select class="form-control select2" name="nis_assessment_status" id="nis_assessment_status" aria-label="nis_assessment_status">
                                @foreach (\App\Models\Supplier::nisAssessmentStatusOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('nis_assessment_status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            {!! $errors->first('nis_assessment_status', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                        <div class="col-md-4">
                            <label class="form-control">
                                <input type="checkbox" name="apply_nis_assessment_status" value="1" @checked(old('apply_nis_assessment_status'))>
                                {{ trans('general.apply') }}
                            </label>
                        </div>
                    </div>

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
                    <a class="btn btn-link pull-left" href="{{ route('suppliers.index') }}">{{ trans('button.cancel') }}</a>
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
                nis_relevant_value: 'apply_nis_relevant',
                nis_criticality: 'apply_nis_criticality',
                nis_relevance_type: 'apply_nis_relevance_type',
                nis_assessment_status: 'apply_nis_assessment_status',
                notes: 'apply_notes'
            };

            $.each(bulkApplyFields, function (fieldName, applyFieldName) {
                $('[name="' + fieldName + '"]').on('input change select2:select', function () {
                    $('input[name="' + applyFieldName + '"]').prop('checked', true);
                });
            });
        });
    </script>
@stop

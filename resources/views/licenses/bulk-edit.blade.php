@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_edit') }} {{ trans('general.licenses') }}
    @parent
@stop

@section('header_right')
    <a href="{{ URL::previous() }}" class="btn btn-sm btn-theme pull-right">{{ trans('general.back') }}</a>
@stop

@section('content')
    {{-- Hand-rolled fields (no partials): the shared edit partials dereference an $item we don't
         have here, which would fatal. Only fields whose "Applica" box is ticked are written
         (see BulkLicensesController); a blank field with the box ticked clears it (except
         Azienda, which is never blanked to avoid re-homing). --}}
    @php
        $ccCompanies = \App\Models\Company::orderBy('name')->get(['id', 'name']);
        $ccCategories = \App\Models\Category::where('category_type', 'license')->orderBy('name')->get(['id', 'name']);
        $ccManufacturers = \App\Models\Manufacturer::orderBy('name')->get(['id', 'name']);
        $ccSuppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name']);
    @endphp

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <form class="form-horizontal" method="post" action="{{ route('licenses.bulkeditsave') }}" autocomplete="off" role="form">
                @csrf
                @foreach ($licenses as $license)
                    <input type="hidden" name="ids[]" value="{{ $license->id }}">
                @endforeach

                <div class="box box-default">
                    <div class="box-header with-border"><h2 class="box-title">{{ trans('general.bulk_edit') }} {{ trans('general.licenses') }}</h2></div>
                    <div class="box-body">
                        <div class="callout callout-warning">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ trans('general.bulk_edit_about_to') }} {{ trans('general.licenses') }}: {{ number_format($licenses->count()) }}.
                            {{ trans('admin/licenses/bulk.only_ticked') }}
                        </div>

                        <table class="table table-striped">
                            <thead><tr><th>{{ trans('general.name') }}</th><th>{{ trans('admin/licenses/form.to_email') }}</th><th>{{ trans('general.manufacturer') }}</th></tr></thead>
                            <tbody>
                                @foreach ($licenses as $license)
                                    <tr><td>{{ $license->name }}</td><td>{{ $license->license_email }}</td><td>{{ $license->manufacturer?->name }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>

                        @php
                            $ccSelects = [
                                ['company_id',      trans('general.company'),      $ccCompanies,      trans('admin/licenses/bulk.company_never_blank')],
                                ['category_id',     trans('general.category'),     $ccCategories,     null],
                                ['manufacturer_id', trans('general.manufacturer'), $ccManufacturers,  null],
                                ['supplier_id',     trans('general.supplier'),     $ccSuppliers,      null],
                            ];
                        @endphp
                        @foreach ($ccSelects as [$field, $label, $options, $help])
                            <div class="form-group {{ $errors->has($field) ? ' has-error' : '' }}">
                                <label for="{{ $field }}" class="col-md-3 control-label">{{ $label }}</label>
                                <div class="col-md-5">
                                    <select class="form-control select2" name="{{ $field }}" id="{{ $field }}" data-placeholder="{{ trans('general.none') }}" aria-label="{{ $field }}" style="width:100%;">
                                        <option value="">{{ trans('general.none') }}</option>
                                        @foreach ($options as $opt)
                                            <option value="{{ $opt->id }}" @selected(old($field) == $opt->id)>{{ $opt->name }}</option>
                                        @endforeach
                                    </select>
                                    @if ($help)<p class="help-block">{{ $help }}</p>@endif
                                    {!! $errors->first($field, '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
                                </div>
                                <div class="col-md-4">
                                    <label class="form-control"><input type="checkbox" name="apply_{{ $field }}" value="1" @checked(old('apply_'.$field))> {{ trans('general.apply') }}</label>
                                </div>
                            </div>
                        @endforeach

                        @php
                            $ccTexts = [
                                ['license_email',    trans('admin/licenses/form.to_email'), 'email'],
                                ['purchase_cost',    trans('general.purchase_cost'),          'text'],
                                ['purchase_date',    trans('general.purchase_date'),          'date'],
                                ['expiration_date',  trans('admin/licenses/form.expiration'),    'date'],
                                ['termination_date', trans('admin/licenses/form.termination_date'), 'date'],
                                ['min_amt',          trans('general.min_amt'),       'number'],
                            ];
                        @endphp
                        @foreach ($ccTexts as [$field, $label, $type])
                            <div class="form-group {{ $errors->has($field) ? ' has-error' : '' }}">
                                <label for="{{ $field }}" class="col-md-3 control-label">{{ $label }}</label>
                                <div class="col-md-5">
                                    <input type="{{ $type === 'date' ? 'text' : $type }}" class="form-control {{ $type === 'date' ? 'datepicker' : '' }}" name="{{ $field }}" id="{{ $field }}" value="{{ old($field) }}" @if($type==='date') placeholder="YYYY-MM-DD" data-date-format="yyyy-mm-dd" @endif>
                                    {!! $errors->first($field, '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
                                </div>
                                <div class="col-md-4">
                                    <label class="form-control"><input type="checkbox" name="apply_{{ $field }}" value="1" @checked(old('apply_'.$field))> {{ trans('general.apply') }}</label>
                                </div>
                            </div>
                        @endforeach

                        {{-- Notes --}}
                        <div class="form-group {{ $errors->has('notes') ? ' has-error' : '' }}">
                            <label for="notes" class="col-md-3 control-label">{{ trans('general.notes') }}</label>
                            <div class="col-md-5"><textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea></div>
                            <div class="col-md-4"><label class="form-control"><input type="checkbox" name="apply_notes" value="1" @checked(old('apply_notes'))> {{ trans('general.apply') }}</label></div>
                        </div>

                        {{-- Boolean flags --}}
                        @foreach ([['maintained', trans('admin/licenses/form.maintained')], ['reassignable', trans('admin/licenses/form.reassignable')]] as [$field, $label])
                            <div class="form-group">
                                <label for="{{ $field }}" class="col-md-3 control-label">{{ $label }}</label>
                                <div class="col-md-5"><label class="cc-check" style="padding-top:6px;"><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" id="{{ $field }}" value="1" @checked(old($field))> {{ $label }}</label></div>
                                <div class="col-md-4"><label class="form-control"><input type="checkbox" name="apply_{{ $field }}" value="1" @checked(old('apply_'.$field))> {{ trans('general.apply') }}</label></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="box-footer text-right">
                        <a href="{{ URL::previous() }}" class="btn btn-link">{{ trans('button.cancel') }}</a>
                        <button type="submit" class="btn btn-primary"><x-icon type="checkmark"/> {{ trans('general.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    // Auto-tick "Applica" as soon as a field is touched, so a change is never silently ignored.
    $(function () {
        var map = ['company_id','category_id','manufacturer_id','supplier_id','license_email',
                   'purchase_cost','purchase_date','expiration_date','termination_date','min_amt',
                   'notes','maintained','reassignable'];
        map.forEach(function (f) {
            $('[name="' + f + '"]').on('input change select2:select select2:unselect select2:clear', function () {
                $('[name="apply_' + f + '"]').prop('checked', true);
            });
        });
    });
</script>
@stop

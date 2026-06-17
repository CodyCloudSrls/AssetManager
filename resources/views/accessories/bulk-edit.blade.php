@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_edit') }} {{ trans('general.accessories') }}
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
            <form class="form-horizontal" method="post" action="{{ route('accessories.bulkeditsave') }}" autocomplete="off" role="form">
                @csrf

                @foreach ($accessories as $accessory)
                    <input type="hidden" name="ids[]" value="{{ $accessory->id }}">
                @endforeach

                <div class="box box-default">
                    <div class="box-body">
                        <div class="callout callout-warning">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ trans('general.bulk_edit_about_to') }}
                            {{ trans('general.accessories') }}: {{ number_format($accessories->count()) }}
                        </div>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('general.name') }}</th>
                                    <th>{{ trans('general.category') }}</th>
                                    <th>{{ trans('general.qty') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($accessories as $accessory)
                                    <tr>
                                        <td>{{ $accessory->name }}</td>
                                        <td>{{ $accessory->category?->name }}</td>
                                        <td>{{ $accessory->qty }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Category --}}
                        @include ('partials.forms.edit.category-select', ['translated_name' => trans('general.category'), 'fieldname' => 'category_id', 'category_type' => 'accessory'])
                        @include ('accessories.partials.bulk-apply', ['field' => 'category_id'])

                        {{-- Company --}}
                        @include ('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'allow_empty' => true])
                        @include ('accessories.partials.bulk-apply', ['field' => 'company_id'])

                        {{-- Location --}}
                        @include ('partials.forms.edit.location-select', ['translated_name' => trans('general.location'), 'fieldname' => 'location_id'])
                        @include ('accessories.partials.bulk-apply', ['field' => 'location_id'])

                        {{-- Manufacturer --}}
                        @include ('partials.forms.edit.manufacturer-select', ['translated_name' => trans('general.manufacturer'), 'fieldname' => 'manufacturer_id'])
                        @include ('accessories.partials.bulk-apply', ['field' => 'manufacturer_id'])

                        {{-- Supplier --}}
                        @include ('partials.forms.edit.supplier-select', ['translated_name' => trans('general.supplier'), 'fieldname' => 'supplier_id'])
                        @include ('accessories.partials.bulk-apply', ['field' => 'supplier_id'])

                        {{-- Min QTY --}}
                        <div class="form-group {{ $errors->has('min_amt') ? ' has-error' : '' }}">
                            <label for="min_amt" class="col-md-3 control-label">{{ trans('general.min_amt') }}</label>
                            <div class="col-md-5">
                                <input type="number" min="0" class="form-control" name="min_amt" id="min_amt" value="{{ old('min_amt') }}">
                                {!! $errors->first('min_amt', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                        </div>
                        @include ('accessories.partials.bulk-apply', ['field' => 'min_amt'])

                        {{-- Notes --}}
                        <div class="form-group {{ $errors->has('notes') ? ' has-error' : '' }}">
                            <label for="notes" class="col-md-3 control-label">{{ trans('general.notes') }}</label>
                            <div class="col-md-7">
                                <textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
                                {!! $errors->first('notes', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                        </div>
                        @include ('accessories.partials.bulk-apply', ['field' => 'notes'])

                    </div>

                    <div class="box-footer text-right">
                        <a href="{{ route('accessories.index') }}" class="btn btn-link">{{ trans('button.cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

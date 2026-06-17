@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('general.accessories') }}
@parent
@stop


{{-- Page content --}}
@section('content')
    <x-container>
        <x-box name="accessories">
            @can('update', \App\Models\Accessory::class)
                <x-slot:bulkactions>
                    <x-table.bulk-actions :action_route="route('accessories.bulkedit')" model_name="accessories">
                        <option value="edit">{{ trans('general.bulk_edit') }}</option>
                    </x-table.bulk-actions>
                </x-slot:bulkactions>
            @endcan
            <x-table.accessories name="accessories" :route="route('api.accessories.index')" fixed_right_number="3" />
        </x-box>
    </x-container>
@stop


@section('moar_scripts')
@include ('partials.bootstrap-table')
@stop

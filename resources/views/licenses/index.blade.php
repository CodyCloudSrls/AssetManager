@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/licenses/general.software_licenses') }}
@parent
@stop


{{-- Page content --}}
@section('content')
    <x-container>
        <x-box name="licenses">
            <x-slot:bulkactions>
                <x-table.bulk-licenses/>
            </x-slot:bulkactions>

            {{-- Filtri rapidi a schermo (come nei Contratti/Notule). Le opzioni sono i valori
                 GIÀ presenti nelle licenze, quindi la select funge anche da "suggerimento" per
                 Produttore e Licenza email (richiesta di Francesca). L'API filtra già su
                 manufacturer_id / license_email. --}}
            @php
                $ccManufacturers = \App\Models\Manufacturer::whereIn('id', \App\Models\License::query()->whereNotNull('manufacturer_id')->select('manufacturer_id'))
                    ->orderBy('name')->get(['id', 'name']);
                $ccEmails = \App\Models\License::query()->whereNotNull('license_email')->where('license_email', '<>', '')
                    ->distinct()->orderBy('license_email')->pluck('license_email');
            @endphp
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-12">
                    <form method="get" action="{{ route('licenses.index') }}" class="form-inline" role="search">
                        @foreach (request()->only(['company_id', 'status']) as $ccName => $ccValue)
                            <input type="hidden" name="{{ $ccName }}" value="{{ $ccValue }}">
                        @endforeach

                        <div class="form-group" style="margin-right:8px;">
                            <label for="license_manufacturer_filter" class="sr-only">{{ trans('general.manufacturer') }}</label>
                            <select class="form-control select2" name="manufacturer_id" id="license_manufacturer_filter" aria-label="{{ trans('general.manufacturer') }}" style="min-width: 200px;">
                                <option value="">{{ trans('admin/licenses/general.all_manufacturers') }}</option>
                                @foreach ($ccManufacturers as $ccM)
                                    <option value="{{ $ccM->id }}" @selected((int) request('manufacturer_id') === (int) $ccM->id)>{{ $ccM->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin-right:8px;">
                            <label for="license_email_filter" class="sr-only">{{ trans('admin/licenses/form.to_email') }}</label>
                            <select class="form-control select2" name="license_email" id="license_email_filter" aria-label="{{ trans('admin/licenses/form.to_email') }}" style="min-width: 220px;">
                                <option value="">{{ trans('admin/licenses/general.all_emails') }}</option>
                                @foreach ($ccEmails as $ccEmail)
                                    <option value="{{ $ccEmail }}" @selected(request('license_email') === $ccEmail)>{{ $ccEmail }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <x-icon type="search" />
                            {{ trans('admin/licenses/general.apply_filters') }}
                        </button>
                        <a href="{{ route('licenses.index', request()->only(['company_id'])) }}" class="btn btn-default">
                            {{ trans('admin/licenses/general.clear_filters') }}
                        </a>
                    </form>
                </div>
            </div>

            <x-table.licenses
                fixed_right_number="2"
                fixed_number="1"
                show_footer="true"
                show_advanced_search="true"
                name="licenses"
                :route="route('api.licenses.index', array_merge(['status' => e(request('status'))], request()->only(['manufacturer_id', 'license_email'])))"/>

        </x-box>
    </x-container>
@stop

@section('moar_scripts')
@include ('partials.bootstrap-table')

@stop

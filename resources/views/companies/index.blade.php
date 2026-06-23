@extends('layouts/default')

{{-- Page title --}}
@section('title')
  {{ trans('general.companies') }}
  @parent
@stop

{{-- Page content --}}
@section('content')
    <x-container columns="2">

        <x-page-column class="col-md-9">
            <x-box>
                @can('update', App\Models\Company::class)
                    <div style="margin-bottom:10px;">
                        <x-table.bulk-actions
                                name='companies'
                                action_route="{{ route('companies.bulkedit.show') }}"
                                model_name="companies"
                        >
                            <option value="edit">{{ trans('general.bulk_edit') }}</option>
                        </x-table.bulk-actions>
                    </div>
                @endcan
                <table
                  data-columns="{{ \App\Presenters\CompanyPresenter::dataTableLayout() }}"
                  data-cookie-id-table="companiesTable"
                  data-id-table="companiesTable"
                  data-bulk-form-id="#companiesForm"
                  data-bulk-button-id="#companiesButton"
                  data-side-pagination="server"
                  data-sort-order="asc"
                  data-advanced-search="false"
                  id="companiesTable"
                  data-buttons="companyButtons"
                  class="table table-striped snipe-table"
                  data-url="{{ route('api.companies.index') }}"
                  data-export-options='{
                            "fileName": "export-companies-{{ date('Y-m-d') }}",
                            "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                            }'>
                </table>
            </x-box>
        </x-page-column>


        <!-- side address column -->
        <x-page-column class="col-md-3">
          <h2>{{ trans('admin/companies/general.about_companies') }}</h2>
          <p>{{ trans('admin/companies/general.about_companies_description') }}</p>
        </x-page-column>
    </x-container>
@stop

@section('moar_scripts')
  @include ('partials.bootstrap-table')
@stop

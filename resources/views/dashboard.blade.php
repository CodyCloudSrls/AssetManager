@extends('layouts/default')
{{-- Page title --}}
@section('title')
{{ trans('general.dashboard') }}
@parent
@stop


{{-- Page content --}}
@section('content')

<style>
    .dashboard-card-grid {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -7.5px 10px;
    }
    .dashboard-card-col {
        padding: 0 7.5px 15px;
        width: 100%;
    }
    .dashboard-card-col > a {
        display: block;
        height: 100%;
    }
    .dashboard-card-grid .dashboard.small-box {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        min-height: 132px;
        margin-bottom: 0;
        max-width: none !important;
        overflow: hidden !important;
        white-space: normal !important;
    }
    .dashboard-card-grid .dashboard.small-box .inner {
        min-height: 96px;
        padding-right: 92px;
    }
    .dashboard-card-grid .dashboard.small-box .icon {
        right: 12px;
        top: 14px;
        max-width: 36%;
        font-size: 68px;
        opacity: .2;
    }
    .dashboard-card-grid .dashboard.small-box .small-box-footer {
        margin-top: auto;
    }
    @media (min-width: 768px) {
        .dashboard-card-col {
            width: 50%;
        }
    }
    @media (min-width: 992px) {
        .dashboard-card-col {
            width: 33.3333%;
        }
    }
    @media (min-width: 1400px) {
        .dashboard-card-col {
            width: 25%;
        }
    }
    @media (min-width: 1680px) {
        .dashboard-card-col {
            width: 14.2857%;
        }
    }
    .dashboard-status-chart {
        position: relative;
        width: 100%;
        height: 320px;
    }
    .dashboard-status-chart canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }
    @media (min-width: 992px) {
        .dashboard-activity-row {
            display: flex;
            align-items: stretch;
        }
        .dashboard-activity-row > [class*="col-"] {
            display: flex;
        }
        .dashboard-activity-box,
        .dashboard-status-box {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .dashboard-status-box .box-body {
            display: flex;
            flex: 1;
            align-items: stretch;
        }
        .dashboard-status-chart {
            flex: 1;
            height: auto;
            min-height: 0;
        }
    }
</style>

@if ($snipeSettings->dashboard_message!='')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        {!!  Helper::parseEscapedMarkedown($snipeSettings->dashboard_message)  !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row dashboard-card-grid">

    <!-- panel -->
    <div class="dashboard-card-col">
        <a href="{{ route('hardware.index', ['reset_view' => 1]) }}">
            <!-- small hardware box -->
            <div class="dashboard small-box bg-teal">
                <div class="inner">
                    <h3>{{ number_format(\App\Models\Asset::AssetsForShow()->count()) }}</h3>
                    <p>{{ trans('general.assets') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="assets" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->

    <div class="dashboard-card-col">
        <a href="{{ route('licenses.index', ['reset_view' => 1]) }}" aria-hidden="true">
            <!-- small license box -->
            <div class="dashboard small-box bg-maroon">
                <div class="inner">
                    <h3>{{ number_format($counts['license']) }}</h3>
                    <p>{{ trans('general.licenses') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="licenses" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->


    <div class="dashboard-card-col">
    <!-- small documents box -->
        <a href="{{ route('documents.index', ['reset_view' => 1]) }}">
            <div class="dashboard small-box bg-orange">
                <div class="inner">
                    <h3> {{ number_format($counts['document']) }}</h3>
                    <p>{{ trans('general.documents') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="documents" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->

    <div class="dashboard-card-col">
        <a href="{{ route('tickets.index', ['reset_view' => 1]) }}">
            <div class="dashboard small-box bg-aqua">
                <div class="inner">
                    <h3>{{ number_format($counts['ticket']) }}</h3>
                    <p>{{ trans('general.tickets') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="tickets" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->

    <div class="dashboard-card-col">
    <!-- small consumables box -->
        <a href="{{ route('consumables.index', ['reset_view' => 1]) }}">
            <div class="dashboard small-box bg-purple">
                <div class="inner">
                    <h3> {{ number_format($counts['consumable']) }}</h3>
                    <p>{{ trans('general.consumables') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="consumables" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->

    <div class="dashboard-card-col">
        <!-- small kits box -->
        <a href="{{ route('kits.index', ['reset_view' => 1]) }}">
            <div class="dashboard small-box bg-yellow">
                <div class="inner">
                    <h3>{{ number_format($counts['kit']) }}</h3>
                    <p>{{ trans('general.kits') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="kits" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->

    <div class="dashboard-card-col">
        <!-- small users box -->
        <a href="{{ route('users.index', ['reset_view' => 1]) }}">
            <div class="dashboard small-box bg-light-blue">
                <div class="inner">
                    <h3>{{ number_format($counts['user']) }}</h3>
                    <p>{{ trans('general.people') }}</p>
                </div>
                <div class="icon" aria-hidden="true">
                    <x-icon type="users" />
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right" />
                </span>
            </div>
        </a>
    </div><!-- ./col -->

</div>

@if ($counts['grand_total'] == 0)

    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('general.dashboard_info') }}</h2>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">

                            <div class="progress">
                                <div class="progress-bar progress-bar-yellow" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%">
                                    <span class="sr-only">{{ trans('general.60_percent_warning') }}</span>
                                </div>
                            </div>


                            <p><strong>{{ trans('general.dashboard_empty') }}</strong></p>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            @can('create', \App\Models\Asset::class)
                            <a class="btn bg-teal" style="width: 100%" href="{{ route('hardware.create') }}">{{ trans('general.new_asset') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\License::class)
                                <a class="btn bg-maroon" style="width: 100%" href="{{ route('licenses.create') }}">{{ trans('general.new_license') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\Document::class)
                                <a class="btn bg-orange" style="width: 100%" href="{{ route('documents.create') }}">{{ trans('general.new_document') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\Consumable::class)
                                <a class="btn bg-purple" style="width: 100%" href="{{ route('consumables.create') }}">{{ trans('general.new_consumable') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\PredefinedKit::class)
                                <a class="btn bg-yellow" style="width: 100%" href="{{ route('kits.create') }}">{{ trans('general.new_kit') }}</a>
                            @endcan
                        </div>
                        <div class="col-md-2">
                            @can('create', \App\Models\User::class)
                                <a class="btn bg-light-blue" style="width: 100%" href="{{ route('users.create') }}">{{ trans('general.new_user') }}</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@else

<!-- recent activity -->
<div class="row dashboard-activity-row">
  <div class="col-md-8">
    <div class="box box-default dashboard-activity-box">
      <div class="box-header with-border">
        <h2 class="box-title">{{ trans('general.recent_activity') }}</h2>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true">
                <x-icon type="minus" />
                <span class="sr-only">{{ trans('general.collapse') }}</span>
            </button>
        </div>
      </div><!-- /.box-header -->
      <div class="box-body">
        <div class="row">
          <div class="col-md-12">

                <table
                    data-cookie-id-table="dashActivityReport"
                    data-height="500"
                    data-pagination="false"
                    data-side-pagination="server"
                    data-id-table="dashActivityReport"
                    data-sort-order="desc"
                    data-show-columns="false"
                    data-fixed-number="false"
                    data-fixed-right-number="false"
                    data-sort-name="created_at"
                    id="dashActivityReport"
                    class="table table-striped snipe-table"
                    data-url="{{ route('api.activity.index', ['limit' => 25]) }}">
                    <thead>
                    <tr>
                        <th data-field="icon" data-visible="true" style="width: 40px;" class="hidden-xs" data-formatter="iconFormatter"><span  class="sr-only">{{ trans('admin/hardware/table.icon') }}</span></th>
                        <th class="col-sm-3" data-visible="true" data-field="created_at" data-formatter="dateDisplayFormatter">{{ trans('general.date') }}</th>
                        <th class="col-sm-2" data-visible="true" data-field="admin" data-formatter="usersLinkObjFormatter">{{ trans('general.created_by') }}</th>
                        <th class="col-sm-2" data-visible="true" data-field="action_type">{{ trans('general.action') }}</th>
                        <th class="col-sm-3" data-visible="true" data-field="item" data-formatter="polymorphicItemFormatter">{{ trans('general.item') }}</th>
                        <th class="col-sm-2" data-visible="true" data-field="target" data-formatter="polymorphicItemFormatter">{{ trans('general.target') }}</th>
                    </tr>
                    </thead>
                </table>
          </div><!-- /.col -->
          <div class="text-center col-md-12" style="padding-top: 10px;">
            <a href="{{ route('reports.activity') }}" class="btn btn-theme btn-sm" style="width: 100%">{{ trans('general.viewall') }}</a>
          </div>
        </div><!-- /.row -->
      </div><!-- ./box-body -->
    </div><!-- /.box -->
  </div>
  <div class="col-md-4">
        <div class="box box-default dashboard-status-box">
            <div class="box-header with-border">
                <h2 class="box-title">
                    {{ (\App\Models\Setting::getSettings()->dash_chart_type == 'name') ? trans('general.assets_by_status') : trans('general.assets_by_status_type') }}
                </h2>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true">
                        <x-icon type="minus" />
                        <span class="sr-only">{{ trans('general.collapse') }}</span>
                    </button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="chart-responsive dashboard-status-chart">
                    <canvas id="statusPieChart"></canvas>
                </div> <!-- ./chart-responsive -->
            </div><!-- /.box-body -->
        </div> <!-- /.box -->
  </div>

</div> <!--/row-->
<div class="row">
    <div class="col-md-6">

		@can('view', \App\Models\Company::class)
			 <!-- Companies -->	
			<div class="box box-default">
				<div class="box-header with-border">
					<h2 class="box-title">{{ trans('general.companies') }}</h2>
					<div class="box-tools pull-right">
						<button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <x-icon type="minus" />
							<span class="sr-only">{{ trans('general.collapse') }}</span>
						</button>
					</div>
				</div>
				<!-- /.box-header -->
				<div class="box-body">
					<div class="row">
						<div class="col-md-12">
							<table
									data-cookie-id-table="dashCompanySummary"
									data-height="400"
                                    data-pagination="false"
									data-side-pagination="server"
									data-sort-order="desc"
                                    data-show-columns="false"
                                    data-fixed-number="false"
                                    data-fixed-right-number="false"
									data-sort-field="assets_count"
									id="dashCompanySummary"
									class="table table-striped snipe-table"
									data-url="{{ route('api.companies.index', ['sort' => 'assets_count', 'order' => 'asc']) }}">

								<thead>
								<tr>
									<th class="col-sm-3" data-visible="true" data-field="name" data-formatter="companiesLinkFormatter" data-sortable="true">{{ trans('general.name') }}</th>
									<th class="col-sm-1" data-visible="true" data-field="users_count" data-sortable="true">
                                        <x-icon type="users" />
										<span class="sr-only">{{ trans('general.people') }}</span>
									</th>
									<th class="col-sm-1" data-visible="true" data-field="assets_count" data-sortable="true">
                                        <x-icon type="assets" />
										<span class="sr-only">{{ trans('general.asset_count') }}</span>
									</th>
									<th class="col-sm-1" data-visible="true" data-field="accessories_count" data-sortable="true">
                                        <x-icon type="accessories" />
										<span class="sr-only">{{ trans('general.accessories_count') }}</span>
									</th>
									<th class="col-sm-1" data-visible="true" data-field="consumables_count" data-sortable="true">
                                        <x-icon type="consumables" />
										<span class="sr-only">{{ trans('general.consumables_count') }}</span>
									</th>
									<th class="col-sm-1" data-visible="true" data-field="licenses_count" data-sortable="true">
                                        <x-icon type="licenses" />
										<span class="sr-only">{{ trans('general.licenses_count') }}</span>
									</th>
								</tr>
								</thead>
							</table>
						</div> <!-- /.col -->
						<div class="text-center col-md-12" style="padding-top: 10px;">
							<a href="{{ route('companies.index') }}" class="btn btn-theme btn-sm" style="width: 100%">{{ trans('general.viewall') }}</a>
						</div>
					</div> <!-- /.row -->

				</div><!-- /.box-body -->
			</div> <!-- /.box -->
		
		@else
			 <!-- Locations -->
			 <div class="box box-default">
				<div class="box-header with-border">
					<h2 class="box-title">{{ trans('general.locations') }}</h2>
					<div class="box-tools pull-right">
						<button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <x-icon type="minus" />
							<span class="sr-only">{{ trans('general.collapse') }}</span>
						</button>
					</div>
				</div>
				<!-- /.box-header -->
				<div class="box-body">
					<div class="row">
						<div class="col-md-12">

							<table
									data-cookie-id-table="dashLocationSummary"
									data-height="400"
									data-side-pagination="server"
                                    data-pagination="false"
									data-sort-order="desc"
                                    data-fixed-number="false"
                                    data-fixed-right-number="false"
									data-sort-field="assets_count"
									id="dashLocationSummary"
                                    data-show-columns="false"
									class="table table-striped snipe-table"
									data-url="{{ route('api.locations.index', ['sort' => 'assets_count', 'order' => 'asc']) }}">
								<thead>
								<tr>
									<th class="col-sm-3" data-visible="true" data-field="name" data-formatter="locationsLinkFormatter" data-sortable="true">{{ trans('general.name') }}</th>
									
									<th class="col-sm-1" data-visible="true" data-field="assets_count" data-sortable="true">
                                        <x-icon type="assets" />
										<span class="sr-only">{{ trans('general.asset_count') }}</span>
									</th>
									<th class="col-sm-1" data-visible="true" data-field="assigned_assets_count" data-sortable="true">
										
										{{ trans('general.assigned') }}
									</th>
									<th class="col-sm-1" data-visible="true" data-field="users_count" data-sortable="true">
                                        <x-icon type="users" />
										<span class="sr-only">{{ trans('general.people') }}</span>
										
									</th>
									
								</tr>
								</thead>
							</table>
						</div> <!-- /.col -->
						<div class="text-center col-md-12" style="padding-top: 10px;">
							<a href="{{ route('locations.index') }}" class="btn btn-theme btn-sm" style="width: 100%">{{ trans('general.viewall') }}</a>
						</div>
					</div> <!-- /.row -->

				</div><!-- /.box-body -->
			</div> <!-- /.box -->

		@endcan
			
    </div>
    <div class="col-md-6">

        <!-- Categories -->
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('general.asset') }} {{ trans('general.categories') }}</h2>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <x-icon type="minus" />
                        <span class="sr-only">{{ trans('general.collapse') }}</span>
                    </button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">

                        <table
                                data-cookie-id-table="dashCategorySummary"
                                data-height="400"
                                data-pagination="false"
                                data-side-pagination="server"
                                data-show-columns="false"
                                data-fixed-number="false"
                                data-fixed-right-number="false"
                                data-sort-order="desc"
                                data-sort-field="assets_count"
                                id="dashCategorySummary"
                                class="table table-striped snipe-table"
                                data-url="{{ route('api.categories.index', ['sort' => 'assets_count', 'order' => 'asc']) }}">
                            <thead>
                            <tr>
                                <th class="col-sm-3" data-visible="true" data-field="name" data-formatter="categoriesLinkFormatter" data-sortable="true">{{ trans('general.name') }}</th>
                                <th class="col-sm-3" data-visible="true" data-field="category_type" data-sortable="true">
                                    {{ trans('general.type') }}
                                </th>
                                <th class="col-sm-1" data-visible="true" data-field="assets_count" data-sortable="true">
                                    <x-icon type="assets" />
                                    <span class="sr-only">{{ trans('general.asset_count') }}</span>
                                </th>
                                <th class="col-sm-1" data-visible="true" data-field="accessories_count" data-sortable="true">
                                    <x-icon type="licenses" />
                                    <span class="sr-only">{{ trans('general.accessories_count') }}</span>
                                </th>
                                <th class="col-sm-1" data-visible="true" data-field="consumables_count" data-sortable="true">
                                    <x-icon type="consumables" />
                                    <span class="sr-only">{{ trans('general.consumables_count') }}</span>
                                </th>
                                <th class="col-sm-1" data-visible="true" data-field="licenses_count" data-sortable="true">
                                    <x-icon type="licenses" />
                                    <span class="sr-only">{{ trans('general.licenses_count') }}</span>
                                </th>
                            </tr>
                            </thead>
                        </table>

                    </div> <!-- /.col -->
                    <div class="text-center col-md-12" style="padding-top: 10px;">
                        <a href="{{ route('categories.index') }}" class="btn btn-theme btn-sm" style="width: 100%">{{ trans('general.viewall') }}</a>
                    </div>
                </div> <!-- /.row -->

            </div><!-- /.box-body -->
        </div> <!-- /.box -->
    </div>


@endif


@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', ['simple_view' => true, 'nopages' => true])
@stop

@push('js')


        <script src="{{ url(mix('js/dist/Chart.min.js')) }}"></script>
<script nonce="{{ csrf_token() }}">
    // ---------------------------
    // - ASSET STATUS CHART -
    // ---------------------------
      var statusPieCanvas = document.getElementById("statusPieChart");

      if (statusPieCanvas) {
      var pieOptions = {
              responsive: true,
              maintainAspectRatio: false,
              legend: {
                  position: 'top',
              },
              tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        counts = data.datasets[0].data;
                        total = 0;
                        for(var i in counts) {
                            total += counts[i];
                        }
                        prefix = data.labels[tooltipItem.index] || '';
                        return prefix+" "+Math.round(counts[tooltipItem.index]/total*100)+"%";
                    }
                }
              }
          };

      $.ajax({
          type: 'GET',
          url: '{{ (\App\Models\Setting::getSettings()->dash_chart_type == 'name') ? route('api.statuslabels.assets.byname') : route('api.statuslabels.assets.bytype') }}',
          headers: {
              "X-Requested-With": 'XMLHttpRequest',
              "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
          },
          dataType: 'json',
          success: function (data) {
              if (!data || !data.datasets || !data.datasets.length) {
                  return;
              }

              new Chart(statusPieCanvas,{
                  type   : 'pie',
                  data   : data,
                  options: pieOptions
              });
          },
          error: function (data) {
              // window.location.reload(true);
          },
      });
        var last = statusPieCanvas.clientWidth;
        addEventListener('resize', function() {
        var current = statusPieCanvas.clientWidth;
        if (current != last) location.reload();
        last = current;
    });
    }
</script>
@endpush

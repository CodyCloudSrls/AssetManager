@extends('layouts/default')

@section('title0')

  @php
      $requestStatusType = request()->input('status_type');
      $requestOrderNumber = request()->input('order_number');
      $requestCompanyId = request()->input('company_id');
      $requestStatusTypeId = request()->input('status_id');
  @endphp

  @if (($requestCompanyId) && ($company))
    {{ $company->name }}
  @endif



  @if ($requestStatusType)
      @if ($requestStatusType=='Pending')
    {{ trans('general.pending') }}
      @elseif ($requestStatusType=='RTD')
    {{ trans('general.ready_to_deploy') }}
      @elseif ($requestStatusType=='Deployed')
    {{ trans('general.deployed') }}
      @elseif ($requestStatusType=='Undeployable')
    {{ trans('general.undeployable') }}
      @elseif ($requestStatusType=='Deployable')
    {{ trans('general.deployed') }}
      @elseif ($requestStatusType=='Requestable')
    {{ trans('admin/hardware/general.requestable') }}
      @elseif ($requestStatusType=='Archived')
    {{ trans('general.archived') }}
      @elseif ($requestStatusType=='Deleted')
    {{ ucfirst(trans('general.deleted')) }}
      @elseif ($requestStatusType=='byod')
    {{ strtoupper(trans('general.byod')) }}
  @endif
@else
{{ trans('general.all') }}
@endif
{{ trans('general.assets') }}

  @if (Request::has('order_number'))
    : Order #{{ strval($requestOrderNumber) }}
  @endif
@stop

{{-- Page title --}}
@section('title')
@yield('title0')  @parent
@stop


{{-- Page content --}}
@section('content')
    <x-container>
        <x-box name="assets">
            <div id="assetsSelectAllBanner" class="alert alert-info hidden-print hidden" style="margin-bottom:12px;">
                <span class="cc-select-all-prompt">
                    {{ trans('general.select_all_pages_prompt') }}
                    <a href="#" id="assetsSelectAllLink"><strong>{{ trans('general.select_all_pages_link') }} (<span id="assetsSelectAllCount"></span>)</strong></a>
                </span>
                <span class="cc-select-all-done hidden">
                    {{ trans('general.select_all_pages_done') }} <strong class="cc-select-all-done-count"></strong>
                </span>
            </div>
            <x-table.assets :route="route('api.assets.index', request()->only(['status_type', 'order_number', 'company_id', 'tenant_id', 'status_id', 'nis_relevant', 'nis_inventory_scope', 'nis_service_impact']))"/>
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
@include('partials.bootstrap-table')

<script nonce="{{ csrf_token() }}">
    // "Select all across pages" for the assets list. Additive + scoped to this
    // table: reuses the global snipe bulk-selection store, so the existing
    // bulk-edit/checkout/delete flows pick the extra ids up unchanged.
    $(function () {
        var $table = $('#assetsListingTable');
        var $banner = $('#assetsSelectAllBanner');
        if (! $table.length || ! $banner.length) { return; }

        function totalRows() {
            try { return $table.bootstrapTable('getOptions').totalRows || 0; } catch (e) { return 0; }
        }
        function pageRows() {
            try { return ($table.bootstrapTable('getData') || []).length; } catch (e) { return 0; }
        }
        function resetBanner() {
            $banner.addClass('hidden');
            $banner.find('.cc-select-all-prompt').removeClass('hidden');
            $banner.find('.cc-select-all-done').addClass('hidden');
        }

        $table.on('check-all.bs.table', function () {
            if (totalRows() > pageRows()) {
                $('#assetsSelectAllCount').text(totalRows());
                $banner.removeClass('hidden');
            }
        });
        $table.on('uncheck.bs.table uncheck-all.bs.table', resetBanner);

        $('#assetsSelectAllLink').on('click', function (e) {
            e.preventDefault();
            var total = totalRows();
            if (total <= 0) { return; }

            var url = $table.data('url');
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'limit=' + total + '&offset=0';

            var $link = $(this).css('pointer-events', 'none').css('opacity', 0.6);

            $.getJSON(url).done(function (resp) {
                var rows = (resp && resp.rows) ? resp.rows : [];
                var ids = rows.map(function (r) { return r.id; }).filter(function (id) { return id; });

                if (window.snipeTableAddBulkSelectionIds && ids.length) {
                    window.snipeTableAddBulkSelectionIds($table, ids);
                    if (window.snipeTableSyncBulkSelections) {
                        window.snipeTableSyncBulkSelections($table);
                    }
                }

                $banner.find('.cc-select-all-prompt').addClass('hidden');
                $banner.find('.cc-select-all-done-count').text(ids.length);
                $banner.find('.cc-select-all-done').removeClass('hidden');
            }).always(function () {
                $link.css('pointer-events', '').css('opacity', '');
            });
        });
    });
</script>

@stop

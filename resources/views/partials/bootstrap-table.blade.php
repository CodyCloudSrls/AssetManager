@push('css')
    <link rel="stylesheet" href="{{ url(mix('css/dist/bootstrap-table.css')) }}">
@endpush

@push('js')

<script src="{{ url(mix('js/dist/bootstrap-table.js')) }}"></script>
<script src="{{ url(mix('js/dist/bootstrap-table-locale-all.min.js')) }}"></script>

<!-- load english again here, even though it's in the all.js file, because if BS table doesn't have the translation, it otherwise defaults to chinese. See https://bootstrap-table.com/docs/api/table-options/#locale -->
<script src="{{ url(mix('js/dist/bootstrap-table-en-US.min.js')) }}"></script>

<script nonce="{{ csrf_token() }}">
    $(function () {

        @if (request()->boolean('reset_view') || request()->boolean('tenant_switched'))
        if (typeof localStorage !== 'undefined') {
            for (var storedStateKey in localStorage) {
                if (storedStateKey.includes('.bs.table.')) {
                    localStorage.removeItem(storedStateKey);
                }
            }
        }

        document.cookie.split(';').forEach(function(cookie) {
            var cookieName = cookie.split('=')[0].trim();
            if (cookieName.includes('.bs.table.')) {
                document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            }
        });
        @endif


        var blockedFields = "searchable,sortable,switchable,title,visible,formatter,class".split(",");

        var keyBlocked = function(key) {
            for(var j in blockedFields) {
                if (key === blockedFields[j]) {
                    return true;
                }
            }
            return false;
        }

        window.snipeTableSelectedRowsForExport = function ($table) {
            try {
                return $table.bootstrapTable('getSelections') || [];
            } catch (error) {
                return [];
            }
        }

        window.snipeTableSyncExportDataTypeForSelection = function ($table) {
            var bootstrapTable = $table.data('bootstrap.table');
            if (! bootstrapTable || ! bootstrapTable.options) {
                return;
            }

            var selectedRows = window.snipeTableSelectedRowsForExport($table);
            var defaultExportDataType = $table.data('default-export-data-type') || 'basic';
            bootstrapTable.options.exportDataType = (selectedRows.length > 0) ? 'selected' : defaultExportDataType;

            if (typeof bootstrapTable.updateExportButton === 'function') {
                bootstrapTable.updateExportButton();
            }
        }

        window.snipeTableSelectedRowIdsForExport = function ($table) {
            var ids = [];
            var selectedRows = window.snipeTableSelectedRowsForExport($table);

            for (var i in selectedRows) {
                if (selectedRows[i].id !== undefined && selectedRows[i].id !== null) {
                    ids.push(selectedRows[i].id);
                }
            }

            return ids;
        }

        window.snipeTableMarkResponsiveShell = function ($table) {
            var $tableWrapper = $table.closest('.bootstrap-table');

            if ($tableWrapper.length == 0) {
                return;
            }

            $tableWrapper.parent('.table-responsive').addClass('bootstrap-table-responsive-shell');
        }

        window.snipeTableUrlWithSelectedRowIds = function (url, $table) {
            var selectedIds = window.snipeTableSelectedRowIdsForExport($table);
            if (selectedIds.length === 0) {
                return url;
            }

            var exportUrl = new URL(url, window.location.origin);
            exportUrl.searchParams.delete('ids');
            exportUrl.searchParams.delete('ids[]');

            for (var i in selectedIds) {
                exportUrl.searchParams.append('ids[]', selectedIds[i]);
            }

            return exportUrl.toString();
        }

        /** This handles the responsive tab UI on v iew detail pages **/
        function resize() {
            if ($(window).width() < 767) {
                $('.nav-tabs-dropdown').addClass('nav-justified');
                $('.uploadtab').removeClass('pull-right');

            }
            else {
                $('.nav-tabs-dropdown').removeClass('nav-justified');
                $('.uploadtab').addClass('pull-right');
            }
        }

        // Run the function on page load
        $(document).ready(function () {
            resize();
        });

        // Watch for window resize events
        $(window).on('resize', function () {
            resize();
        });

        //open and close tab menu
        $('.nav-tabs-dropdown').on("click", "li:not('.active') a", function (event) {
            $(this).closest('ul').removeClass("open");
        }).on("click", "li.active a", function (event) {
            $(this).closest('ul').toggleClass("open");
        });

        /** End handling the responsive tab UI on view detail pages **/

        $('table.snipe-table').not('.bootstrap-table table').bootstrapTable('destroy').each(function () {

            var $table = $(this);
            var data_export_options = $table.attr('data-export-options');
            var export_options = data_export_options ? JSON.parse(data_export_options) : {};
            export_options['htmlContent'] = false; // this is already the default; but let's be explicit about it
            export_options['jspdf'] = {
                "orientation": "l",
                "autotable": {
                        "styles": {
                            overflow: 'linebreak'
                        },
                        tableWidth: 'wrap'
                }
            };
            // tableWidth: 'wrap',
            // the following callback method is necessary to prevent XSS vulnerabilities
            // (this is taken from Bootstrap Tables's default wrapper around jQuery Table Export)
            export_options['onCellHtmlData'] = function (cell, rowIndex, colIndex, htmlData) {
                if (cell.is('th')) {
                    return cell.find('.th-inner').text().trim()
                }

                var explicitExportValue = cell.attr('data-tableexport-value');
                if (explicitExportValue !== undefined) {
                    return explicitExportValue;
                }

                var renderedText = cell.text().trim();
                if (renderedText !== '') {
                    return renderedText;
                }

                if (htmlData === undefined || htmlData === null) {
                    return '';
                }

                return $('<div/>').html(String(htmlData)).text().trim();
            }

            // This allows us to override the table defaults set below using the data-dash attributes
            var table = this;
            var data_with_default = function (key,default_value) {
                attrib_val = $(table).data(key);
                if(attrib_val !== undefined) {
                    return attrib_val;
                }
                return default_value;
            }

            var default_export_data_type = data_with_default('export-data-type', 'basic');
            $table.data('default-export-data-type', default_export_data_type);


            $table.bootstrapTable({

                ajaxOptions: {
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                },
                // reorderableColumns: true,
                // buttonsPrefix: "btn",
                addrbar: {{ (config('session.bs_table_addrbar') == 'true') ? 'true' : 'false'}}, // deeplink search phrases, sorting, etc
                advancedSearch: data_with_default('advanced-search', true),
                buttonsClass: "tableButton tableButton btn-theme hidden-print",
                buttonsOrder: [
                    'columns',
                    'btnAdd',
                    'btnShowDeleted',
                    'btnShowAdmins',
                    'btnShowExpiring',
                    'btnShowInactive',
                    'refresh',
                    'btnExport',
                    'export',
                    'print',
                    'fullscreen',
                    'advancedSearch',
                ],
                classes: 'table table-responsive table-striped snipe-table table-no-bordered',
                clickToSelect: data_with_default('click-to-select', true),
                cookie: true,
                cookieExpire: '2y',
                cookieStorage: '{{ config('session.bs_table_storage') }}',
                iconsPrefix: 'fa',
                uniqueId: data_with_default('unique-id', 'id'),
                maintainSelected: data_with_default('maintain-selected', true),
                minimumCountColumns: data_with_default('minimum-count-columns', 2),
                mobileResponsive: data_with_default('mobile-responsive', true),
                pagination: data_with_default('pagination', true),
                paginationFirstText: "{{ trans('general.first') }}",
                paginationLastText: "{{ trans('general.last') }}",
                paginationNextText: "{{ trans('general.next') }}",
                paginationPreText: "{{ trans('general.previous') }}",
                search: data_with_default('search', true),
                searchText: "{{ request()->get('assetTag') ?? request()->get('search', '') }}",
                searchHighlight: data_with_default('search-highlight', true),
                showColumns: data_with_default('show-columns', true),
                showColumnsToggleAll: data_with_default('show-columns-toggle-all', true),
                showExport: data_with_default('show-export', true),
                showFullscreen: data_with_default('show-fullscreen', true),
                showPrint: data_with_default('show-print', true),
                showRefresh: data_with_default('show-refresh', true),
                showSearchClearButton: data_with_default('show-search-clear-button', true),
                sortName: data_with_default('sort-name', 'created_at'),
                sortOrder: data_with_default('sort-order', 'desc'),
                fixedColumns: data_with_default('fixed-columns', 'true'),
                fixedRightNumber: data_with_default('fixed-right-number', '1'),
                stickyHeader: true,
                stickyHeaderOffsetLeft: parseInt($('body').css('padding-left'), 10),
                stickyHeaderOffsetRight: parseInt($('body').css('padding-right'), 10),
                trimOnSearch: false,
                undefinedText: '',
                pageList: ['10', '20', '30', '50', '100', '150', '200'{!! ((config('app.max_results') > 200) ? ",'500'" : '') !!}{!! ((config('app.max_results') > 500) ? ",'".config('app.max_results')."'" : '') !!}],
                pageSize: {{  (($snipeSettings->per_page!='') && ($snipeSettings->per_page > 0)) ? $snipeSettings->per_page : 20 }},
                paginationVAlign: 'both',
                queryParams: function (params) {
                    var newParams = {};
                    for (var i in params) {
                        if (!keyBlocked(i)) { // only send the field if it's not in blockedFields
                            newParams[i] = params[i];
                        }
                    }
                    return newParams;
                },
                formatLoadingMessage: function () {
                    return '<h2><x-icon type="spinner" /> {{ trans('general.loading') }} </h2>';
                },
                icons: {
                    advancedSearchIcon: 'fas fa-search-plus',
                    paginationSwitchDown: 'fa-caret-square-o-down',
                    paginationSwitchUp: 'fa-caret-square-o-up',
                    fullscreen: 'fa-expand',
                    columns: 'fa-columns',
                    print: 'fa-print',
                    refresh: 'fas fa-sync-alt',
                    export: 'fa-download',
                    clearSearch: 'fa-times',
                },
                locale: '{{ app()->getLocale() }}',
                exportDataType: default_export_data_type,
                exportOptions: export_options,
                exportTypes: ['xlsx', 'excel', 'csv', 'pdf', 'json', 'xml', 'txt', 'sql', 'doc'],
                onExportStarted: function () {
                    window.snipeTableSyncExportDataTypeForSelection($table);
                },
                onExportSaved: function () {
                    window.snipeTableSyncExportDataTypeForSelection($table);
                },
                onLoadSuccess: function () { // possible 'fixme'? this might be for contents, not for headers?
                    $('[data-tooltip="true"]').tooltip(); // Needed to attach tooltips after ajax call
                },
                onPostHeader: function () {
                    var lookup = {};
                    var lookup_initialized = false;
                    var ths = $('th');
                    var toolbar_buttons = $('.tableButton');

                    ths.each(function (index, element) {
                        th = $(element);
                        //only populate the lookup table once; don't need to keep doing it.
                        if (!lookup_initialized) {
                            // th -> tr -> thead -> table
                            var table = th.parent().parent().parent()
                            var column_data = table.data('columns')

                            for (var column in column_data) {
                                lookup[column_data[column].field] = column_data[column].titleTooltip;
                            }

                            lookup_initialized = true
                        }

                        field = th.data('field'); // find fieldname this column refers to
                        title = lookup[field];

                        if (title) {
                            th.attr('data-toggle', 'tooltip');
                            th.attr('data-tooltip', 'true');
                            th.attr('data-placement', 'top');
                            th.tooltip({container: 'body', title: title});

                        }
                    });

                    // Add tooltips to the toolbar buttons too
                    toolbar_buttons.each(function (index, element) {
                        tableButton = $(element);
                        title = tableButton.attr('title');
                        override_class = tableButton.attr('class');

                        if (title) {
                            // Keep this commented out so that we don't interfere with the dropdown toggle for columns, etc
                            // tableButton.attr('data-toggle', 'tooltip');
                            tableButton.attr('data-tooltip', 'true');
                            tableButton.attr('data-placement', 'auto');

                            // This prevents the slight button jitter on the mouseovees on the dashboard
                            tableButton.tooltip({container: 'body', title: title});

                            // This handles the case where we want a different color button than the default
                            if ((override_class) && ((override_class.indexOf('btn-info') >= 0)) || (override_class.indexOf('btn-danger') >= 0)) {
                                tableButton.removeClass('btn-primary');
                            }
                        }
                    });

                },
                formatNoMatches: function () {
                    return '{{ trans('table.no_matching_records') }}';
                }

            });

            window.snipeTableMarkResponsiveShell($table);

            $table.on('post-body.bs.table reset-view.bs.table column-switch.bs.table', function () {
                window.snipeTableMarkResponsiveShell($table);
            });

        });
    });


    // User table buttons
    window.userButtons = () => ({
        @can('create', \App\Models\User::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('users.create') }}';
            },
            attributes: {
                title: '{{ trans('general.create') }}',
                class: 'btn-warning',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
        @endcan

        btnExport: {
            text: '{{ trans('general.export_all_to_csv') }}',
            icon: 'fa-solid fa-file-csv',
            event () {
                window.location.href = '{{ route('users.export') }}';
            },
            attributes: {
                title: '{{ trans('general.export_all_to_csv') }}',
            }
        },

        btnShowAdmins: {
            text: '{{ trans('general.show_admins') }}',
            icon: 'fa-solid fa-crown',
            event () {
                window.location.href = '{{ (request()->input('admins') == "true") ? route('users.index') : route('users.index', ['admins' => 'true']) }}';
            },
            attributes: {
                title: '{{ trans('general.show_admins') }}',
                class: '{{ (request()->input('admins') == "true") ? ' btn-selected text-danger' : '' }}'
            }
        },

        btnShowDeleted: {
            text: '{{ (request()->input('status') == "deleted") ? trans('admin/users/table.show_current') : trans('admin/users/table.show_deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status') == "deleted") ? route('users.index') : route('users.index', ['status' => 'deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status') == "deleted") ? ' btn-selected' : '' }}',
                title: '{{ (request()->input('status') == "deleted") ? trans('admin/users/table.show_current') : trans('admin/users/table.show_deleted') }}',

            }
        },

    }); // end user table buttons


    @can('create', \App\Models\Company::class)
    // Company table buttons
    window.companyButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('companies.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },

    }); // End company table buttons
    @endcan


    @can('create', \App\Models\Group::class)
    // Groups table buttons
    window.groupButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('groups.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },

    }); // End Groups table buttons
    @endcan


    // Asset table buttons
    window.assetButtons = () => ({
        @can('create', \App\Models\Asset::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                // Carry the current list filters (category/fieldset/custom-field/…) into the
                // create form so cancelling returns to the same filtered list.
                window.location.href = '{{ route('hardware.create') }}' + window.location.search;
            },
            attributes: {
                title: '{{ trans('general.create') }}',
                class: 'btn-warning',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
        @endcan

        @can('update', \App\Models\Asset::class)
        btnAddMaintenance: {
            text: '{{ trans('button.add_maintenance') }}',
            icon: 'fa-solid fa-screwdriver-wrench',
            event () {
                window.location.href = '{{ route('maintenances.create', ['asset_id' => (isset($asset)) ? $asset->id :'' ]) }}';
            },
            attributes: {
                title: '{{ trans('button.add_maintenance') }}',
            }
        },
        @endcan


        btnExport: {
            text: '{{ trans('admin/hardware/general.custom_export') }}',
            icon: 'fa-solid fa-file-csv',
            event () {
                window.location.href = '{{ route('reports/custom') }}';
            },
            attributes: {
                title: '{{ trans('admin/hardware/general.custom_export') }}',
            }
        },

        btnShowDeleted: {
            text: '{{ (request()->input('status_type') == "Deleted") ? trans('general.list_all') : trans('general.deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status_type') == "Deleted") ? route('hardware.index') : route('hardware.index', ['status_type' => 'Deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status_type') == "Deleted") ? 'btn-selected' : '' }}',
                title: '{{ (request()->input('status_type') == "Deleted") ? trans('general.list_all') : trans('general.deleted') }}',

            }
        },
    });

    window.documentButtons = () => ({
        @can('create', \App\Models\Document::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('documents.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
        @endcan
        @can('view', \App\Models\DocumentType::class)
        btnManageTypes: {
            text: '{{ trans('general.document_types') }}',
            icon: 'fa-solid fa-tags',
            event () {
                window.location.href = '{{ route('documenttypes.index') }}';
            },
            attributes: {
                title: '{{ trans('general.document_types') }}',
            }
        },
        @endcan
        @can('view', \App\Models\DocumentFramework::class)
        btnManageFrameworks: {
            text: '{{ trans('general.document_frameworks') }}',
            icon: 'fa-solid fa-folder-tree',
            event () {
                window.location.href = '{{ route('documentframeworks.index') }}';
            },
            attributes: {
                title: '{{ trans('general.document_frameworks') }}',
            }
        },
        @endcan
        @can('view', \App\Models\ComplianceDomain::class)
        btnManageComplianceDomains: {
            text: '{{ trans('admin/compliancedomains/general.title') }}',
            icon: 'fa-solid fa-layer-group',
            event () {
                window.location.href = '{{ route('compliancedomains.index') }}';
            },
            attributes: {
                title: '{{ trans('admin/compliancedomains/general.title') }}',
            }
        },
        @endcan
        btnShowDeleted: {
            text: '{{ (request()->input('status_type') == "Deleted") ? trans('general.list_all') : trans('general.deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status_type') == "Deleted") ? route('documents.index') : route('documents.index', ['status_type' => 'Deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status_type') == "Deleted") ? 'btn-selected' : '' }}',
                title: '{{ (request()->input('status_type') == "Deleted") ? trans('general.list_all') : trans('general.deleted') }}',
            }
        },
    });

    window.ticketButtons = () => ({
        @can('create', \App\Models\Ticket::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('tickets.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
        @endcan
        btnOpenQueue: {
            text: '{{ trans('admin/tickets/general.open_queue') }}',
            icon: 'fa-regular fa-life-ring',
            event () {
                window.location.href = '{{ route('tickets.index', ['queue' => 'open']) }}';
            },
            attributes: {
                class: '{{ (request()->input('queue') == "open") ? 'btn-selected' : '' }}',
                title: '{{ trans('admin/tickets/general.open_queue') }}',
            }
        },
        btnShowDeleted: {
            text: '{{ (request()->input('status_type') == "Deleted") ? trans('general.list_all') : trans('general.deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status_type') == "Deleted") ? route('tickets.index') : route('tickets.index', ['status_type' => 'Deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status_type') == "Deleted") ? 'btn-selected' : '' }}',
                title: '{{ (request()->input('status_type') == "Deleted") ? trans('general.list_all') : trans('general.deleted') }}',
            }
        },
    });

    @can('create', \App\Models\Location::class)
    // Location table buttons
    window.locationButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('locations.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },

        btnShowDeleted: {
            text: '{{ (request()->input('status') == "deleted") ? trans('general.show_current') : trans('general.show_deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status') == "deleted") ? route('locations.index') : route('locations.index', ['status' => 'deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status') == "deleted") ? 'btn-selected' : '' }}',
                title: '{{ (request()->input('status') == "deleted") ? trans('general.show_current') : trans('general.show_deleted') }}',

            }
        },
    });
    @endcan

    @can('create', \App\Models\Accessory::class)
    // Accessory table buttons
    window.accessoryButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('accessories.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\Depreciation::class)
    // Accessory table buttons
    window.depreciationButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('depreciations.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\DocumentType::class)
    window.documenttypeButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('documenttypes.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
            }
        },
        btnShowDeleted: {
            text: '{{ (request()->input('status') == "deleted") ? trans('general.show_current') : trans('general.show_deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status') == "deleted") ? route('documenttypes.index') : route('documenttypes.index', ['status' => 'deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status') == "deleted") ? 'btn-selected' : '' }}',
                title: '{{ (request()->input('status') == "deleted") ? trans('general.show_current') : trans('general.show_deleted') }}',
            }
        },
    });
    @endcan

    @can('create', \App\Models\DocumentFramework::class)
    window.documentframeworkButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('documentframeworks.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
            }
        },
        btnImport: {
            text: '{{ trans('admin/documentframeworks/general.import') }}',
            icon: 'fa-solid fa-file-import',
            event () {
                window.location.href = '{{ route('documentframeworks.import') }}';
            },
            attributes: {
                title: '{{ trans('admin/documentframeworks/general.import') }}',
            }
        },
        btnShowDeleted: {
            text: '{{ (request()->input('status') == "deleted") ? trans('general.show_current') : trans('general.show_deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status') == "deleted") ? route('documentframeworks.index') : route('documentframeworks.index', ['status' => 'deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status') == "deleted") ? 'btn-selected' : '' }}',
                title: '{{ (request()->input('status') == "deleted") ? trans('general.show_current') : trans('general.show_deleted') }}',
            }
        },
    });
    @endcan

    window.complianceDomainButtons = () => ({
        @can('create', \App\Models\ComplianceDomain::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('compliancedomains.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
            }
        },
        @endcan
    });

    @can('create', \App\Models\CustomField::class)
    // Accessory table buttons
    window.customFieldButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('fields.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan


    @can('create', \App\Models\CustomFieldset::class)
    // Accessory table buttons
    window.customFieldsetButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('fieldsets.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\Component::class)
    // Component table buttons kept for backward compatibility while the module is decommissioned from UI entrypoints.
    window.componentButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('components.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\Consumable::class)
    // Consumable table buttons
    window.consumableButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('consumables.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\Manufacturer::class)
    // Manufacturer table buttons
    window.manufacturerButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('manufacturers.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            },
        },

        btnShowDeleted: {
            text: '{{ (request()->input('status') == "Deleted") ? trans('general.list_all') : trans('general.deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status') == "deleted") ? route('manufacturers.index') : route('manufacturers.index', ['status' => 'deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status') == "deleted") ? 'btn-selected' : '' }}',
                title: '{{ (request()->input('status') == "deleted") ? trans('general.list_all') : trans('general.deleted') }}',

            }
        },
    });
    @endcan

    @php
        $supplierAcnExportUrl = route('suppliers.acn_export', array_merge(
            array_filter(request()->only([
                'tenant_id',
                'nis_relevant',
                'nis_relevance_type',
                'nis_criticality',
                'nis_assessment_status',
                'nis_assessment_method',
                'nis_assessment_outcome',
                'nis_review_status',
                'cpv_code',
                'search',
                'filter',
            ]), static fn ($value) => ! is_null($value) && $value !== ''),
            ['nis_relevant' => request()->input('nis_relevant', 1)]
        ));
    @endphp

    // Supplier table buttons
    window.supplierButtons = () => ({
        @can('create', \App\Models\Supplier::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('suppliers.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
        @endcan

        btnExport: {
            text: '{{ trans('admin/suppliers/table.acn_export') }}',
            icon: 'fa-solid fa-file-excel',
            event () {
                window.location.href = window.snipeTableUrlWithSelectedRowIds(@json($supplierAcnExportUrl), $('#supplierListingTable'));
            },
            attributes: {
                title: '{{ trans('admin/suppliers/table.acn_export') }}',
            }
        },
    });

    // Customer table buttons
    window.customerButtons = () => ({
        @can('create', \App\Models\Customer::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('customers.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
        @endcan
    });

    // Contract table buttons
    window.contractButtons = () => ({
        @can('create', \App\Models\CustomerContract::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('contracts.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
        @endcan
    });

    @can('create', \App\Models\Department::class)
    // Department table buttons
    window.departmentButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('departments.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\Department::class)
    // Custom Field table buttons
    window.departmentButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('departments.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('update', \App\Models\Asset::class)
    // Custom Field table buttons
    window.maintenanceButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('maintenances.create', ['asset_id' => (isset($asset)) ? $asset->id :'' ]) }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('button.add_maintenance') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\Category::class)
    // Custom Field table buttons
    window.categoryButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('categories.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\PredefinedKit::class)
    // Custom Field table buttons
    window.kitButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('kits.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan

    @can('create', \App\Models\AssetModel::class)
    // Custom Field table buttons
    window.modelButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('models.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
        btnShowDeleted: {
            text: '{{ (request()->input('status') == "deleted") ? trans('general.list_all') : trans('general.deleted') }}',
            icon: 'fa-solid fa-trash',
            event () {
                window.location.href = '{{ (request()->input('status') == "deleted") ? route('models.index') : route('models.index', ['status' => 'deleted']) }}';
            },
            attributes: {
                class: '{{ (request()->input('status') == "deleted") ? ' btn-selected' : '' }}',
                title: '{{ (request()->input('status') == "deleted") ? trans('general.list_all') : trans('general.deleted') }}',

            }
        },
    });
    @endcan

    @can('create', \App\Models\Statuslabel::class)
    // Status label table buttons
    window.statuslabelButtons = () => ({
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('statuslabels.create') }}';
            },
            attributes: {
                class: 'btn-info',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            }
        },
    });
    @endcan


    // License table buttons
    window.licenseButtons = () => ({
        @can('create', \App\Models\License::class)
        btnAdd: {
            text: '{{ trans('general.create') }}',
            icon: 'fa fa-plus',
            event () {
                window.location.href = '{{ route('licenses.create') }}';
            },
            attributes: {
                class: 'btn-warning',
                title: '{{ trans('general.create') }}',
                @if ($snipeSettings->shortcuts_enabled == 1)
                accesskey: 'n'
                @endif
            },
        },
        @endcan

        btnExport: {
            text: '{{ trans('general.export_all_to_csv') }}',
            icon: 'fa-solid fa-file-csv',
            event () {
                window.location.href = '{{ route('licenses.export', ['category_id' => (isset($category)) ? $category->id :'' ]) }}';
            },
            attributes: {
                title: '{{ trans('general.export_all_to_csv') }}',
            }
        },

        btnShowExpiring: {
            text: '{{ (request()->input('status') == "expiring") ? trans('general.list_all') : trans('general.show_expiring') }}',
            icon: 'fas fa-clock',
            event () {
                window.location.href = '{{ (request()->input('status') == "expiring") ? route('licenses.index') : route('licenses.index', ['status' => 'expiring']) }}';
            },
            attributes: {
                class: "{{ (request()->input('status') == "expiring") ? ' btn-warning' : '' }}",
                title: '{{ (request()->input('status') == "expiring") ? trans('general.list_all') : trans('general.show_expiring') }}',

            }
        },

        btnShowInactive: {
            text: '{{ (request()->input('status') == "inactive") ? trans('general.list_all') : trans('general.show_inactive') }}',
            icon: 'fas fa-history',
            event () {
                window.location.href = '{{ (request()->input('status') == "inactive") ? route('licenses.index') : route('licenses.index', ['status' => 'inactive']) }}';
            },
            attributes: {
                class: "{{ (request()->input('status') == "inactive") ? ' btn-warning' : '' }}",
                title: '{{ (request()->input('status') == "inactive") ? trans('general.list_all') : trans('general.show_inactive') }}',

            }
        },
    });





    function dateRowCheckStyle(value) {
        if ((value.days_to_next_audit) && (value.days_to_next_audit < {{ $snipeSettings->audit_warning_days ?: 0 }})) {
            return { classes : "danger" }
        }
        return {};
    }


    window.snipeTableBulkSelectionForm = function ($table) {
        var buttonName = $table.data('bulk-button-id');
        var formName = $table.data('bulk-form-id');
        var $form = $(buttonName).closest('form');

        if ($form.length == 0 && formName) {
            $form = $(formName).filter('form').first();
        }

        if ($form.length == 0 && formName) {
            $form = $(formName).find('form').first();
        }

        return $form;
    };

    window.snipeTableBulkSelectionButton = function ($table, $form) {
        var buttonName = $table.data('bulk-button-id');
        var $button = $(buttonName);

        if ($button.length == 0 && $form && $form.length > 0) {
            $button = $form.find('button[type="submit"]').first();
        }

        return $button;
    };

    window.snipeTableNormalizeBulkSelectionIds = function (ids) {
        var normalizedIds = [];

        if (ids === undefined || ids === null) {
            return normalizedIds;
        }

        if (! $.isArray(ids)) {
            ids = [ids];
        }

        for (var i in ids) {
            if (! ids.hasOwnProperty(i)) {
                continue;
            }

            if (ids[i] === undefined || ids[i] === null || ids[i] === '' || ids[i] === 'on') {
                continue;
            }

            if (normalizedIds.indexOf(String(ids[i])) === -1) {
                normalizedIds.push(String(ids[i]));
            }
        }

        return normalizedIds;
    };

    window.snipeTableUniqueBulkIdField = function ($table) {
        return $table.data('unique-id') || 'id';
    };

    window.snipeTableBulkIdFromRow = function ($table, row) {
        var uniqueId = window.snipeTableUniqueBulkIdField($table);

        if (! row) {
            return null;
        }

        if (row[uniqueId] !== undefined) {
            return row[uniqueId];
        }

        if (row.id !== undefined) {
            return row.id;
        }

        return null;
    };

    window.snipeTableIdsFromRows = function ($table, rows) {
        var ids = [];

        if (rows === undefined || rows === null) {
            return ids;
        }

        if (! $.isArray(rows)) {
            rows = [rows];
        }

        for (var i in rows) {
            if (! rows.hasOwnProperty(i)) {
                continue;
            }

            ids.push(window.snipeTableBulkIdFromRow($table, rows[i]));
        }

        return window.snipeTableNormalizeBulkSelectionIds(ids);
    };

    window.snipeTableVisibleBulkIds = function ($table) {
        var tableData = [];

        try {
            tableData = $table.bootstrapTable('getData') || [];
        } catch (e) {
            tableData = [];
        }

        return window.snipeTableIdsFromRows($table, tableData);
    };

    window.snipeTableCurrentBulkSelectionIds = function ($table) {
        var $form = window.snipeTableBulkSelectionForm($table);
        var ids = [];

        if ($form.length == 0) {
            return ids;
        }

        $form.find('input[data-bulk-selection="true"]').each(function () {
            ids.push($(this).val());
        });

        return window.snipeTableNormalizeBulkSelectionIds(ids);
    };

    window.snipeTableSelectedBulkIds = function ($table) {
        var ids = [];
        var selectedRows = [];
        var tableData = [];
        var $tableContainer = $table.closest('.bootstrap-table');

        try {
            selectedRows = $table.bootstrapTable('getSelections') || [];
        } catch (e) {
            selectedRows = [];
        }

        for (var i in selectedRows) {
            if (selectedRows.hasOwnProperty(i)) {
                ids.push(window.snipeTableBulkIdFromRow($table, selectedRows[i]));
            }
        }

        try {
            tableData = $table.bootstrapTable('getData') || [];
        } catch (e) {
            tableData = [];
        }

        if ($tableContainer.length == 0) {
            $tableContainer = $table;
        }

        $tableContainer.find('input[name="btSelectItem"]:checked, input[data-index][type="checkbox"]:checked, .bs-checkbox input:checked, tr.selected[data-index]').each(function () {
            var $row = $(this).is('tr') ? $(this) : $(this).closest('tr[data-index]');
            var rowIndex = $row.data('index');

            if (rowIndex === undefined && ! $(this).is('tr')) {
                rowIndex = $(this).data('index');
            }

            if (rowIndex !== undefined && tableData[rowIndex]) {
                ids.push(window.snipeTableBulkIdFromRow($table, tableData[rowIndex]));
                return;
            }

            if (! $(this).is('tr')) {
                ids.push($(this).val());
            }
        });

        return window.snipeTableNormalizeBulkSelectionIds(ids);
    };

    window.snipeTableWriteBulkSelectionIds = function ($table, ids) {
        var $form = window.snipeTableBulkSelectionForm($table);
        var $button = window.snipeTableBulkSelectionButton($table, $form);
        var selectedIds = window.snipeTableNormalizeBulkSelectionIds(ids);

        if ($form.length == 0) {
            return selectedIds;
        }

        $form.find('input[data-bulk-selection="true"]').remove();

        for (var i in selectedIds) {
            if (! selectedIds.hasOwnProperty(i)) {
                continue;
            }

            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'ids[]')
                .attr('value', selectedIds[i])
                .attr('data-bulk-selection', 'true')
                .appendTo($form);
        }

        if ($button.length > 0) {
            if (selectedIds.length > 0) {
                $button.removeAttr('disabled');
            } else {
                $button.attr('disabled', 'disabled');
            }
        }

        return selectedIds;
    };

    window.snipeTableAddBulkSelectionIds = function ($table, ids) {
        var selectedIds = window.snipeTableCurrentBulkSelectionIds($table);

        selectedIds = selectedIds.concat(window.snipeTableNormalizeBulkSelectionIds(ids));

        return window.snipeTableWriteBulkSelectionIds($table, selectedIds);
    };

    window.snipeTableRemoveBulkSelectionIds = function ($table, ids) {
        var selectedIds = window.snipeTableCurrentBulkSelectionIds($table);
        var idsToRemove = window.snipeTableNormalizeBulkSelectionIds(ids);
        var remainingIds = [];

        for (var i in selectedIds) {
            if (! selectedIds.hasOwnProperty(i)) {
                continue;
            }

            if (idsToRemove.indexOf(String(selectedIds[i])) === -1) {
                remainingIds.push(selectedIds[i]);
            }
        }

        return window.snipeTableWriteBulkSelectionIds($table, remainingIds);
    };

    window.snipeTableSyncBulkSelections = function ($table, fallbackIds) {
        var selectedIds = window.snipeTableSelectedBulkIds($table);

        if (selectedIds.length == 0 && fallbackIds !== undefined) {
            selectedIds = window.snipeTableNormalizeBulkSelectionIds(fallbackIds);
        }

        return window.snipeTableWriteBulkSelectionIds($table, selectedIds);
    };

    window.snipeTableSyncVisibleBulkSelections = function ($table) {
        var currentIds = window.snipeTableCurrentBulkSelectionIds($table);
        var visibleIds = window.snipeTableVisibleBulkIds($table);
        var selectedIds = window.snipeTableSelectedBulkIds($table);
        var remainingIds = [];

        if (visibleIds.length == 0) {
            return window.snipeTableSyncBulkSelections($table, currentIds);
        }

        for (var i in currentIds) {
            if (! currentIds.hasOwnProperty(i)) {
                continue;
            }

            if (visibleIds.indexOf(String(currentIds[i])) === -1) {
                remainingIds.push(currentIds[i]);
            }
        }

        return window.snipeTableWriteBulkSelectionIds($table, remainingIds.concat(selectedIds));
    };

    window.snipeTableSyncAllBulkSelections = function () {
        $('.snipe-table').each(function () {
            var $table = $(this);
            window.snipeTableSyncVisibleBulkSelections($table);
            window.snipeTableSyncExportDataTypeForSelection($table);
        });
    };

    $('.snipe-table').on('check.bs.table', function (event, row) {
        var $table = $(this);

        window.snipeTableAddBulkSelectionIds($table, window.snipeTableIdsFromRows($table, row));
        window.snipeTableSyncExportDataTypeForSelection($table);
    });

    $('.snipe-table').on('check-all.bs.table', function (event, rowsAfter) {
        var $table = $(this);

        window.snipeTableAddBulkSelectionIds($table, window.snipeTableIdsFromRows($table, rowsAfter));
        window.snipeTableSyncExportDataTypeForSelection($table);
    });

    $('.snipe-table').on('uncheck.bs.table', function (event, row) {
        var $table = $(this);

        window.snipeTableRemoveBulkSelectionIds($table, window.snipeTableIdsFromRows($table, row));
        window.snipeTableSyncExportDataTypeForSelection($table);
    });

    $('.snipe-table').on('uncheck-all.bs.table', function (event, rowsAfter, rowsBefore) {
        var $table = $(this);
        var rows = rowsBefore || rowsAfter || [];

        window.snipeTableRemoveBulkSelectionIds($table, window.snipeTableIdsFromRows($table, rows));
        window.snipeTableSyncExportDataTypeForSelection($table);
    });

    $('.snipe-table').on('post-body.bs.table page-change.bs.table', function () {
        window.snipeTableSyncBulkSelections($(this), window.snipeTableCurrentBulkSelectionIds($(this)));
        window.snipeTableSyncExportDataTypeForSelection($(this));
    });

    $(document).on('click.snipeBulkSelections change.snipeBulkSelections', 'input[name="btSelectItem"], input[name="btSelectAll"]', function () {
        window.setTimeout(window.snipeTableSyncAllBulkSelections, 0);
        window.setTimeout(window.snipeTableSyncAllBulkSelections, 75);
    });

    $(document).on('click.snipeBulkSelections', '.bootstrap-table tbody tr, .fixed-table-container tbody tr', function () {
        window.setTimeout(window.snipeTableSyncAllBulkSelections, 0);
        window.setTimeout(window.snipeTableSyncAllBulkSelections, 75);
    });

    $('.snipe-table').each(function () {
        var $table = $(this);
        var $form = window.snipeTableBulkSelectionForm($table);

        if ($form.length == 0) {
            return;
        }

        $form.off('submit.snipeBulkSelections').on('submit.snipeBulkSelections', function () {
            window.snipeTableSyncBulkSelections($table, window.snipeTableCurrentBulkSelectionIds($table));
        });

        window.snipeTableSyncBulkSelections($table);
    });

    // Initialize sort-order for bulk actions (label-generation) for snipe-tables
    $('.snipe-table').each(function (i, table) {
        table_cookie_segment = $(table).data('cookie-id-table');
        sort = '';
        order = '';
        cookies = document.cookie.split(";");
        for(i in cookies) {
            cookiedef = cookies[i].split("=", 2);
            cookiedef[0] = cookiedef[0].trim();
            if (cookiedef[0] == table_cookie_segment + ".bs.table.sortOrder") {
                order = cookiedef[1];
            }
            if (cookiedef[0] == table_cookie_segment + ".bs.table.sortName") {
                sort = cookiedef[1];
            }
        }
        if (sort && order) {
            domnode = $($(this).data('bulk-form-id')).get(0);
            if ( domnode && domnode.elements && domnode.elements.sort ) {
                domnode.elements.sort.value = sort;
                domnode.elements.order.value = order;
            }
        }
    });

    // If sort order changes, update the sort-order for bulk-actions (for label-generation)
    $('.snipe-table').on('sort.bs.table', function (event, name, order) {
       var $ccTable = $(this);

       // ── Tri-state sort (LIVE-VERIFY) ──────────────────────────────────────────
       // bootstrap-table cicla asc → desc → asc. Qui il 3° click sulla STESSA colonna
       // (transizione desc → asc) viene interpretato come "disattiva ordinamento" e
       // riporta la tabella all'ordinamento di default (data-sort-name/order).
       var ccLast = $ccTable.data('cc-last-sort');
       var ccDefName = ($ccTable.data('sort-name') || 'created_at');
       var ccDefOrder = ($ccTable.data('sort-order') || 'desc');
       if (ccLast && ccLast.name === name && ccLast.order === 'desc' && order === 'asc'
           && (name !== ccDefName || order !== ccDefOrder)) {
           $ccTable.data('cc-last-sort', { name: ccDefName, order: ccDefOrder });
           setTimeout(function () {
               try { $ccTable.bootstrapTable('refreshOptions', { sortName: ccDefName, sortOrder: ccDefOrder }); } catch (e) {}
           }, 0);
           return;   // salta l'aggiornamento del bulk-form: lo farà il refresh col default
       }
       $ccTable.data('cc-last-sort', { name: name, order: order });

       domnode = $(this).data('bulk-form-id') ? $($(this).data('bulk-form-id')).get(0) : null;
       // make safe in case there isn't a bulk-form-id, or it's not found, or has no 'sort' element
       if ( domnode && domnode.elements && domnode.elements.sort ) {
           domnode.elements.sort.value = name;
           domnode.elements.order.value = order;
       }
    });



    // This specifies the footer columns that should have special styles associated
    // (usually numbers)
    window.footerStyle = column => ({
        remaining: {
            classes: 'text-padding-number-footer-cell'
        },
        qty: {
            classes: 'text-padding-number-footer-cell',
        },
        purchase_cost: {
            classes: 'text-padding-number-footer-cell'
        },
        checkouts_count: {
            classes: 'text-padding-number-footer-cell'
        },
        assets_count: {
            classes: 'text-padding-number-footer-cell'
        },
        seats: {
            classes: 'text-padding-number-footer-cell'
        },
        free_seats_count: {
            classes: 'text-padding-number-footer-cell'
        },
    }[column.field]);




    // This only works for model index pages because it uses the row's model ID
    function genericRowLinkFormatter(destination) {
        return function (value,row) {

            if ((row) && (row.tag_color) && (row.tag_color!='') && (row.tag_color!=undefined)) {
                var tag_icon = '<i class="fa-solid fa-square" style="color: ' + row.tag_color + ';" aria-hidden="true"></i> ';
            } else {
                var tag_icon = '';
            }

            if (value) {
                return '<span style="white-space:nowrap;">' + tag_icon + '<a href="{{ config('app.url') }}/' + destination + '/' + row.id + '">' + value + '</a></span>';
            }
        };
    }



    // This is a special formatter that will indicate whether a user is an admin or superadmin
    function usernameRoleLinkFormatter(value, row) {

            if ((value) && (row)) {

                if (row.role === 'superadmin') {
                    return '<span style="white-space: nowrap" data-tooltip="true" title="{{ trans('general.superadmin_tooltip') }}"><x-icon type="superadmin" title="{{ trans('general.superadmin') }}"  class="text-danger" /> <a href="{{ config('app.url') }}/users/' + row.id + '">' + value + '</a></span>';
                } else if (row.role === 'superuser') {
                    return '<span style="white-space: nowrap" data-tooltip="true" title="{{ trans('general.superuser_tooltip') }}"><x-icon type="superadmin" title="{{ trans('general.superuser') }}"  class="text-primary" /> <a href="{{ config('app.url') }}/users/' + row.id + '">' + value + '</a></span>';
                } else if (row.role === 'admin') {
                    return '<span style="white-space: nowrap" data-tooltip="true" title="{{ trans('general.admin_tooltip') }}"><x-icon type="superadmin" title="{{ trans('general.admin_user') }}" class="text-warning" /> <a href="{{ config('app.url') }}/users/' + row.id + '">' + value + '</a></span>';
                }

                // Regular user
                return '<a href="{{ config('app.url') }}/users/' + row.id + '">' + value + '</a>';
            }

    }

    function progressBarFormatter(value) {
        var bar_color = 'danger';

        if (value <= 25) {
            bar_color = 'danger';
        }
        else if (value <= 75) {
            bar_color = 'warning';
        }
        else if (value <= 100) {
            bar_color = 'success';
        }
        return '<div class="progress progress-sm" data-tooltip="true" title="' + value + '%"><div class="progress-bar progress-bar-' + bar_color + '" role="progressbar" aria-valuenow="' + value + '" aria-valuemin="0" aria-valuemax="100" style="width: ' + value + '%; min-width: 0em;"></div></div>';
    }

    // Use this when we're introspecting into a column object and need to link
    function genericColumnObjLinkFormatter(destination) {
        return function (value,row) {
            if ((value) && (value.status_meta)) {

                var text_color;
                var icon_style;
                var text_help;
                var status_meta = {
                  'deployed': '{{ strtolower(trans('general.deployed')) }}',
                  'deployable': '{{ strtolower(trans('admin/hardware/general.deployable')) }}',
                  'archived': '{{ strtolower(trans('general.archived')) }}',
                  'undeployable': '{{ strtolower(trans('general.undeployable')) }}',
                  'pending': '{{ strtolower(trans('general.pending')) }}'
                }

                switch (value.status_meta) {
                    case 'deployed':
                        text_color = 'blue';
                        icon_style = 'fa-circle';
                        text_help = '<label class="label label-default">{{ trans('general.deployed') }}</label>';
                    break;
                    case 'deployable':
                        text_color = 'green';
                        icon_style = 'fa-circle';
                        text_help = '';
                    break;
                    case 'pending':
                        text_color = 'orange';
                        icon_style = 'fa-circle';
                        text_help = '';
                        break;
                    default:
                        text_color = 'red';
                        icon_style = 'fa-times';
                        text_help = '';
                }

                return '<nobr><a href="{{ config('app.url') }}/' + destination + '/' + value.id + '" data-tooltip="true" title="'+ status_meta[value.status_meta] + '"> <i class="fa ' + icon_style + ' text-' + text_color + '"></i> ' + value.name + ' ' + text_help + ' </a> </nobr>';
            } else if ((value) && (value.name)) {

                // Add some overrides for any funny urls we have
                var dest = destination;
                var tag_color;
                var polymorphicItemFormatterDest = '';



                if (destination == 'fieldsets') {
                    var polymorphicItemFormatterDest = 'fields/';
                }

                // Handle the preceding icon if a tag_color is given in the API response
                if ((value.tag_color) && (value.tag_color!='')) {
                    var tag_icon = '<i class="fa-solid fa-square" style="color: ' + value.tag_color + ';" aria-hidden="true"></i>';
                } else {
                    var tag_icon = '';
                }

                return '<nobr>'+ tag_icon + ' <a href="{{ config('app.url') }}/' + polymorphicItemFormatterDest + dest + '/' + value.id + '">' + value.name + '</a></span>';
            }
        };
    }


    function colorTagFormatter(value, row) {
        if (value) {
            return '<i class="fa-solid fa-square" style="color: ' + value + ';" aria-hidden="true"></i> ' + value;
        }
    }




    function licenseKeyFormatter(value, row) {
        if (value) {
            return '<code class="single-line"><span class="js-copy-link" data-clipboard-target=".js-copy-key-' + row.id + '" aria-hidden="true" data-tooltip="true" data-placement="top" title="{{ trans('general.copy_to_clipboard') }}"><span class="js-copy-key-' + row.id + '">' + value + '</span></span></code>';
        }
    }



    function hardwareAuditFormatter(value, row) {
        return '<a href="{{ config('app.url') }}/hardware/' + row.id + '/audit" class="actions btn btn-sm btn-primary hidden-print" data-tooltip="true" title="{{ trans('general.audit') }}"><x-icon type="audit" /><span class="sr-only">{{ trans('general.audit') }}</span></a>&nbsp;';
    }




    // Make the edit/delete buttons
    function genericActionsFormatter(owner_name, element_name) {
        if (!element_name) {
            element_name = '';
        }


        return function (value,row) {
            var actions = '<nobr>';

            // Add some overrides for any funny urls we have
            var dest = owner_name;

            if (dest =='groups') {
                var dest = 'admin/groups';
            }


            if(element_name != '') {
                dest = dest + '/' + row.owner_id + '/' + element_name;
            }

            if ((row.available_actions) && (row.available_actions.create_asset === true)) {
                actions += '<a href="{{ config('app.url') }}/hardware/create?model_id=' + row.id + '" class="actions btn btn-sm btn-info hidden-print" data-tooltip="true" title="{{ trans('general.new_asset') }}"><x-icon type="plus" class="fa-fw" /><span class="sr-only">{{ trans('general.new_asset') }}</span></a>&nbsp;';
            }

            if ((row.available_actions) && (row.available_actions.clone === true)) {
                actions += '<a href="{{ config('app.url') }}/' + dest + '/' + row.id + '/clone" class="actions btn btn-sm btn-info hidden-print" data-tooltip="true" title="{{ trans('general.clone_item') }}"><x-icon type="clone" class="fa-fw" /><span class="sr-only">{{ trans('general.clone_item') }}</span></a>&nbsp;';
            }

            if ((row.available_actions) && (row.available_actions.audit === true)) {
                actions += '<a href="{{ config('app.url') }}/' + dest + '/' + row.id + '/audit" class="actions btn btn-sm btn-primary hidden-print" data-tooltip="true" title="{{ trans('general.audit') }}"><x-icon type="audit" class="fa-fw" /><span class="sr-only">{{ trans('general.audit') }}</span></a>&nbsp;';
            }

            if ((row.available_actions) && (row.available_actions.update === true)) {
                actions += '<a href="{{ config('app.url') }}/' + dest + '/' + row.id + '/edit" class="actions btn btn-sm btn-warning hidden-print" data-tooltip="true" title="{{ trans('general.update') }}"><x-icon type="edit" class="fa-fw" /><span class="sr-only">{{ trans('general.update') }}</span></a>&nbsp;';
            } else {
                if ((row.available_actions) && (row.available_actions.update != true)) {
                    actions += '<span data-tooltip="true" title="{{ trans('general.cannot_be_edited') }}"><a class="btn btn-warning btn-sm disabled" onClick="return false;"><x-icon type="edit" class="fa-fw" /></a></span>&nbsp;';
                }
            }

            if ((row.available_actions) && (row.available_actions.delete === true)) {

                // use the asset tag if no name is provided

                if (row.name) {
                    var name_for_box = row.name
                } else if (row.asset_tag) {
                    var name_for_box = row.asset_tag
                }



                actions += '<a href="{{ config('app.url') }}/' + dest + '/' + row.id + '" '
                    + ' class="actions btn btn-danger btn-sm delete-asset hidden-print" data-tooltip="true"  '
                    + ' data-toggle="modal" data-icon="fa-trash"'
                    + ' data-content="{{ trans('general.sure_to_delete') }}: ' + name_for_box + '?" '
                    + ' data-title="{{  trans('general.delete') }}" onClick="return false;">'
                    + '<x-icon type="delete" class="fa-fw" /><span class="sr-only">{{ trans('general.delete') }}</span></a>&nbsp;';
            } else {
                // Do not show the delete button on things that are already deleted
                if ((row.available_actions) && (row.available_actions.restore != true)) {
                    actions += '<span data-tooltip="true" title="{{ trans('general.cannot_be_deleted') }}"><a class="btn btn-danger btn-sm delete-asset disabled hidden-print" onClick="return false;"><x-icon type="delete" class="fa-fw" /><span class="sr-only">{{ trans('general.cannot_be_deleted') }}</span></a></span>&nbsp;';
                }

            }


            if ((row.available_actions) && (row.available_actions.restore === true)) {
                actions += '<form style="display: inline;" method="POST" action="{{ config('app.url') }}/' + dest + '/' + row.id + '/restore"> ';
                actions += '@csrf';
                actions += '<button class="btn btn-sm btn-warning" data-tooltip="true" title="{{ trans('general.restore') }}"><x-icon type="restore" class="fa-fw" /><span class="sr-only">{{ trans('general.restore') }}</span></button>&nbsp;';
                actions += '</form>';
            }

            if ((row.available_actions) && (row.available_actions.force_delete === true)) {
                actions += '<form style="display: inline;" method="POST" action="{{ config('app.url') }}/' + dest + '/' + row.id + '/force-delete" onsubmit="return confirm(\'{{ trans('admin/documents/message.force_delete.confirm') }}\')"> ';
                actions += '@csrf';
                actions += '<button class="btn btn-sm btn-danger" data-tooltip="true" title="{{ trans('admin/documents/message.force_delete.action') }}"><x-icon type="delete" class="fa-fw" /><span class="sr-only">{{ trans('admin/documents/message.force_delete.action') }}</span></button>&nbsp;';
                actions += '</form>';
            }

            actions +='</nobr>';
            return actions;

        };
    }


    // This handles the icons and display of polymorphic entries
    function polymorphicItemFormatter(value) {

        var item_destination = '';
        var item_icon;

        if ((value) && (value.type)) {

            if (value.type == 'asset') {
                item_destination = 'hardware';
                item_icon = 'fas fa-barcode';
            } else if (value.type == 'accessory') {
                item_destination = 'accessories';
                item_icon = 'far fa-keyboard';
            } else if (value.type == 'component') {
                item_destination = 'components';
                item_icon = 'far fa-hdd';
            } else if (value.type == 'consumable') {
                item_destination = 'consumables';
                item_icon = 'fas fa-tint';
            } else if (value.type == 'license') {
                item_destination = 'licenses';
                item_icon = 'far fa-save';
            } else if (value.type == 'user') {
                item_destination = 'users';
                item_icon = 'fas fa-user';
            } else if (value.type == 'location') {
                item_destination = 'locations'
                item_icon = 'fas fa-map-marker-alt';
            } else if (value.type == 'maintenance') {
                item_destination = 'maintenances'
                item_icon = 'fa-solid fa-screwdriver-wrench';
            } else if (value.type == 'document') {
                item_destination = 'documents'
                item_icon = 'fa-regular fa-file-lines';
            } else if (value.type == 'ticket') {
                item_destination = 'tickets'
                item_icon = 'fa-regular fa-life-ring';
            } else if (value.type == 'model') {
                item_destination = 'models'
                item_icon = '';
            }

            // display the username if it's checked out to a user, but don't do it if the username's there already
            if (value.username && !value.name.match('\\(') && !value.name.match('\\)')) {
                value.name = value.name + ' (' + value.username + ')';
            }

            return '<nobr><a href="{{ config('app.url') }}/' + item_destination +'/' + value.id + '" data-tooltip="true" title="' + value.type + '"><i class="' + item_icon + ' fa-fw"></i> ' + value.name + '</a></nobr>';

        } else {
            return '';
        }


    }

    // This just prints out the item type in the activity report
    function itemTypeFormatter(value, row) {

        if ((row) && (row.item) && (row.item.type)) {
            return row.item.type;
        }
    }


    // Convert line breaks to <br>
    function notesFormatter(value) {
        if (value) {
            return value.replace(/(?:\r\n|\r|\n)/g, '<br />');
        }
    }

    // Check if checkbox should be selectable
    // Selectability is determined by the API field "selectable" which is set at the Presenter/API Transformer
    // However since different bulk actions have different requirements, we have to walk through the available_actions object
    // to determine whether to disable it
    function checkboxEnabledFormatter (value, row) {

        // add some stuff to get the value of the select2 option here?

        if ((row.available_actions) && (row.available_actions.bulk_selectable) && (row.available_actions.bulk_selectable.delete !== true)) {
            return {
                disabled:true,
                //checked: false, <-- not sure this will work the way we want?
            }
        }
    }

    function licenseInOutFormatter(value, row) {

        // check that checkin is not disabled
        if (row.user_can_checkout === false) {
            return '<span class="btn btn-sm bg-maroon btn-checkout disabled" data-tooltip="true" title="{{ trans('admin/licenses/message.checkout.unavailable') }}">{{ trans('general.checkout') }}</span>';
        } else if (row.disabled === true) {
            return '<span class="btn btn-sm bg-maroon btn-checkout disabled" data-tooltip="true" title="{{ trans('admin/licenses/message.checkout.license_is_inactive') }}">{{ trans('general.checkout') }}</span>';

        } else
            // The user is allowed to check the license seat out and it's available
        if ((row.available_actions.checkout === true) && (row.user_can_checkout === true) && (row.disabled === false)) {
            return '<a href="{{ config('app.url') }}/licenses/' + row.id + '/checkout/" class="btn btn-sm bg-maroon btn-checkout" data-tooltip="true" title="{{ trans('general.checkout_tooltip') }}">{{ trans('general.checkout') }}</a>';
        }
    }
    // We need a special formatter for license seats, since they don't work exactly the same
    // Checkouts need the license ID, checkins need the specific seat ID

    function licenseSeatInOutFormatter(value, row) {
        if (row.disabled && (row.assigned_user || row.assigned_asset)) {
            return '<a href="{{ config('app.url') }}/licenses/' + row.id + '/checkin" class="btn btn-sm bg-purple" data-tooltip="true" title="{{ trans('general.checkin_tooltip') }}">{{ trans('general.checkin') }}</a>';
        }
        if (row.disabled) {
            return '<a href="{{ config('app.url') }}/licenses/' + row.id + '/checkin" class="btn btn-sm bg-maroon btn-checkout disabled" data-tooltip="true" title="{{ trans('general.checkin_tooltip') }}">{{ trans('general.checkout') }}</a>';
        }
        // The user is allowed to check the license seat out and it's available
        if ((row.available_actions.checkout === true) && (row.user_can_checkout === true) && ((!row.assigned_asset) && (!row.assigned_user))) {
            return '<a href="{{ config('app.url') }}/licenses/' + row.license_id + '/checkout/'+row.id+'" class="btn btn-sm bg-maroon btn-checkout" data-tooltip="true" title="{{ trans('general.checkout_tooltip') }}">{{ trans('general.checkout') }}</a>';
        }

        // The user is allowed to check the license seat in and it's available
        if ((row.available_actions.checkin === true) && ((row.assigned_asset) || (row.assigned_user))) {
            return '<a href="{{ config('app.url') }}/licenses/' + row.id + '/checkin/" class="btn btn-sm bg-purple btn-checkin" data-tooltip="true" title="{{ trans('general.checkin_tooltip') }}">{{ trans('general.checkin') }}</a>';
        }

    }

    function genericCheckinCheckoutFormatter(destination) {
        return function (value, row) {

            // The user is allowed to check items out, AND the item is deployable
            if ((row.available_actions.checkout == true) && (row.user_can_checkout == true) && ((!row.asset_id) && (!row.assigned_to))) {

                    return '<a href="{{ config('app.url') }}/' + destination + '/' + row.id + '/checkout" class="btn btn-sm bg-maroon btn-checkout" data-tooltip="true" title="{{ trans('general.checkout_tooltip') }}">{{ trans('general.checkout') }}</a>';

            // The user is allowed to check items out, but the item is not able to be checked out
            } else if (((row.user_can_checkout == false)) && (row.available_actions.checkout == true) && (!row.assigned_to)) {

                // We use slightly different language for assets versus other things, since they are the only
                // item that has a status label
                if (destination =='hardware') {
                    return '<span  data-tooltip="true" title="{{ trans('admin/hardware/general.undeployable_tooltip') }}"><a class="btn btn-sm bg-maroon btn-checkout disabled">{{ trans('general.checkout') }}</a></span>';
                } else {
                    return '<span  data-tooltip="true" title="{{ trans('general.undeployable_tooltip') }}"><a class="btn btn-sm bg-maroon btn-checkout disabled">{{ trans('general.checkout') }}</a></span>';
                }

            // The user is allowed to check items in
            } else if (row.available_actions.checkin == true)  {
                if (row.assigned_to) {
                    return '<a href="{{ config('app.url') }}/' + destination + '/' + row.id + '/checkin" class="btn btn-sm bg-purple btn-checkin" data-tooltip="true" title="{{ trans('general.checkin_tooltip') }}">{{ trans('general.checkin') }}</a>';
                } else if (row.assigned_pivot_id) {
                    return '<a href="{{ config('app.url') }}/' + destination + '/' + row.assigned_pivot_id + '/checkin" class="btn btn-sm bg-purple btn-checkin" data-tooltip="true" title="{{ trans('general.checkin_tooltip') }}">{{ trans('general.checkin') }}</a>';
                }

            }

        }


    }


    // This is only used by the requestable assets section
    function assetRequestActionsFormatter (row, value) {
        if (value.assigned_to_self == true){
            return '<button class="btn btn-danger btn-sm btn-block disabled" data-tooltip="true" title="{{ trans('admin/hardware/message.requests.cancel') }}">{{ trans('button.cancel') }}</button>';
        } else if (value.available_actions.cancel == true)  {
            return '<form action="{{ config('app.url') }}/account/request-asset/' + value.id + '/cancel" method="POST">@csrf<button class="btn btn-danger btn-block btn-sm" data-tooltip="true" title="{{ trans('admin/hardware/message.requests.cancel') }}">{{ trans('button.cancel') }}</button></form>';
        } else if (value.available_actions.request == true)  {
            return '<form action="{{ config('app.url') }}/account/request-asset/'+ value.id + '" method="POST">@csrf<button class="btn btn-block btn-primary btn-sm" data-tooltip="true" title="{{ trans('general.request_item') }}">{{ trans('button.request') }}</button></form>';
        }

    }



    var formatters = [
        'accessories',
        'categories',
        'companies',
        'compliancedomains',
        'components',
        'consumables',
        'contracts',
        'customers',
        'departments',
        'documentframeworks',
        'documentframeworkrequirements',
        'documenttypes',
        'documents',
        'tickets',
        'depreciations',
        'fieldsets',
        'groups',
        'hardware',
        'kits',
        'licenses',
        'locations',
        'maintenances',
        'manufacturers',
        'models',
        'statuslabels',
        'suppliers',
        'users',
    ];

    for (var i in formatters) {
        window[formatters[i] + 'LinkFormatter'] = genericRowLinkFormatter(formatters[i]);
        window[formatters[i] + 'LinkObjFormatter'] = genericColumnObjLinkFormatter(formatters[i]);
        window[formatters[i] + 'ActionsFormatter'] = genericActionsFormatter(formatters[i]);
        window[formatters[i] + 'InOutFormatter'] = genericCheckinCheckoutFormatter(formatters[i]);
    }

    var child_formatters = [
        ['kits', 'models'],
        ['kits', 'licenses'],
        ['kits', 'consumables'],
        ['kits', 'accessories'],
    ];

    for (var i in child_formatters) {
        var owner_name = child_formatters[i][0];
        var child_name = child_formatters[i][1];
        window[owner_name + '_' + child_name + 'ActionsFormatter'] = genericActionsFormatter(owner_name, child_name);
    }

    function ticketsActionsFormatter(value, row) {
        var actions = '<nobr>';
        var ticketLabel = row.subject ? row.subject : row.ticket_number;

        if ((row.available_actions) && (row.available_actions.view === true)) {
            actions += '<a href="{{ config('app.url') }}/tickets/' + row.id + '" class="actions btn btn-sm btn-info hidden-print" data-tooltip="true" title="{{ trans('general.view') }}"><i class="far fa-eye fa-fw" aria-hidden="true"></i><span class="sr-only">{{ trans('general.view') }}</span></a>&nbsp;';
        }

        if ((row.available_actions) && (row.available_actions.update === true)) {
            actions += '<a href="{{ config('app.url') }}/tickets/' + row.id + '/edit" class="actions btn btn-sm btn-warning hidden-print" data-tooltip="true" title="{{ trans('general.update') }}"><x-icon type="edit" class="fa-fw" /><span class="sr-only">{{ trans('general.update') }}</span></a>&nbsp;';
        }

        if ((row.available_actions) && (row.available_actions.delete === true)) {
            actions += '<a href="{{ config('app.url') }}/tickets/' + row.id + '" '
                + ' class="actions btn btn-danger btn-sm delete-asset hidden-print" data-tooltip="true" '
                + ' data-toggle="modal" data-icon="fa-trash"'
                + ' data-content="{{ trans('general.sure_to_delete') }}: ' + ticketLabel + '?" '
                + ' data-title="{{  trans('general.delete') }}" onClick="return false;">'
                + '<x-icon type="delete" class="fa-fw" /><span class="sr-only">{{ trans('general.delete') }}</span></a>&nbsp;';
        }

        if ((row.available_actions) && (row.available_actions.restore === true)) {
            actions += '<form style="display: inline;" method="POST" action="{{ config('app.url') }}/tickets/' + row.id + '/restore">';
            actions += '@csrf';
            actions += '<button class="btn btn-sm btn-warning" data-tooltip="true" title="{{ trans('general.restore') }}"><x-icon type="restore" class="fa-fw" /><span class="sr-only">{{ trans('general.restore') }}</span></button>&nbsp;';
            actions += '</form>';
        }

        actions += '</nobr>';

        return actions;
    }

    function ticketPersonFormatter(value) {
        if (!value || !value.name) {
            return '';
        }

        if (value.id && value.id > 0) {
            return '<a href="{{ config('app.url') }}/users/' + value.id + '">' + value.name + '</a>';
        }

        return value.name;
    }

    function documentAssignmentsFormatter(value, row) {
        if (!value || !Array.isArray(value) || value.length === 0) {
            return '';
        }

        var html = [];
        var visibleAssignments = value.slice(0, 3);

        for (var i = 0; i < visibleAssignments.length; i++) {
            var assignment = visibleAssignments[i];
            var labelClass = assignment.is_expired ? 'label-danger' : (assignment.is_expiring ? 'label-warning' : 'label-default');
            var name = assignment.name ? assignment.name : '{{ trans('general.na') }}';
            var target = assignment.url ? '<a href="' + assignment.url + '">' + name + '</a>' : name;
            var title = assignment.type ? assignment.type + ' - ' + assignment.relation_type : assignment.relation_type;

            if (assignment.approval_status) {
                title += ' - ' + assignment.approval_status;
            }

            html.push('<span class="label ' + labelClass + '" data-tooltip="true" title="' + title + '">' + target + '</span>');
        }

        if (value.length > visibleAssignments.length) {
            html.push('<span class="label label-info">+' + (value.length - visibleAssignments.length) + '</span>');
        }

        return html.join(' ');
    }

    function documentAssignmentTargetFormatter(value, row) {
        if (!value || !value.name) {
            return '';
        }

        var label = value.type ? '<span class="text-muted">' + value.type + '</span> ' : '';
        var target = value.url ? '<a href="' + value.url + '">' + value.name + '</a>' : value.name;

        return label + target;
    }

    function documentAssignmentStatusFormatter(value, row) {
        if (!value) {
            return '';
        }

        var labelClass = 'label-default';

        if (row.status === '{{ \App\Models\DocumentAssignment::STATUS_REQUIRED }}') {
            labelClass = 'label-warning';
        } else if (row.status === '{{ \App\Models\DocumentAssignment::STATUS_COMPLETED }}' || row.status === '{{ \App\Models\DocumentAssignment::STATUS_ACTIVE }}') {
            labelClass = 'label-success';
        } else if (row.status === '{{ \App\Models\DocumentAssignment::STATUS_EXPIRED }}' || row.status === '{{ \App\Models\DocumentAssignment::STATUS_REVOKED }}' || row.is_expired) {
            labelClass = 'label-danger';
        }

        var status = '<span class="label ' + labelClass + '">' + value + '</span>';

        if (row.is_expired) {
            status += '<div class="text-danger">{{ trans('admin/documents/general.assignment_expired_flag') }}</div>';
        } else if (row.is_expiring) {
            status += '<div class="text-warning">{{ trans('admin/documents/general.assignment_expiring_flag') }}</div>';
        }

        return status;
    }

    function documentAssignmentApprovalFormatter(value, row) {
        if (!value) {
            return '';
        }

        var labelClass = 'label-default';

        if (row.approval_status === '{{ \App\Models\DocumentAssignment::APPROVAL_SUBMITTED }}' || row.approval_status === '{{ \App\Models\DocumentAssignment::APPROVAL_IN_REVIEW }}') {
            labelClass = 'label-warning';
        } else if (row.approval_status === '{{ \App\Models\DocumentAssignment::APPROVAL_APPROVED }}') {
            labelClass = 'label-success';
        } else if (row.approval_status === '{{ \App\Models\DocumentAssignment::APPROVAL_REJECTED }}') {
            labelClass = 'label-danger';
        }

        return '<span class="label ' + labelClass + '">' + value + '</span>';
    }

    function documentAssignmentActionsFormatter(value, row) {
        var actions = '<nobr>';

        if (row.document && row.available_actions && row.available_actions.view === true) {
            actions += '<a href="{{ config('app.url') }}/documents/' + row.document.id + '" class="actions btn btn-sm btn-info hidden-print" data-tooltip="true" title="{{ trans('general.view') }}"><i class="far fa-eye fa-fw" aria-hidden="true"></i><span class="sr-only">{{ trans('general.view') }}</span></a>&nbsp;';
        }

        if (row.document && row.available_actions && row.available_actions.update === true) {
            actions += '<a href="{{ config('app.url') }}/documents/' + row.document.id + '/assignments/' + row.id + '/edit" class="actions btn btn-sm btn-warning hidden-print" data-tooltip="true" title="{{ trans('general.update') }}"><x-icon type="edit" class="fa-fw" /><span class="sr-only">{{ trans('general.update') }}</span></a>&nbsp;';
        }

        actions += '</nobr>';

        return actions;
    }

    function documentRequirementsFormatter(value, row) {
        if (!value || !Array.isArray(value) || value.length === 0) {
            return '';
        }

        var html = [];
        var visibleRequirements = value.slice(0, 3);

        for (var i = 0; i < visibleRequirements.length; i++) {
            var requirement = visibleRequirements[i];
            var code = requirement.code ? requirement.code : '{{ trans('general.na') }}';
            var title = requirement.title ? requirement.title : '';
            var role = requirement.coverage_role ? requirement.coverage_role : '';
            var tooltip = title + (role ? ' - ' + role : '');

            html.push('<span class="label label-default" data-tooltip="true" title="' + tooltip + '">' + code + '</span>');
        }

        if (value.length > visibleRequirements.length) {
            html.push('<span class="label label-info">+' + (value.length - visibleRequirements.length) + '</span>');
        }

        return html.join(' ');
    }

    function documentFilesCountFormatter(value, row) {
        var count = parseInt(value, 10);

        if (isNaN(count) || count <= 0) {
            return '<span class="text-muted">0</span>';
        }

        return '<a href="{{ config('app.url') }}/documents/' + row.id + '#files" data-tooltip="true" title="{{ trans('general.uploaded_files') }}"><i class="fas fa-paperclip fa-fw" aria-hidden="true"></i> ' + count + '</a>';
    }

    function documentFrameworkRequirementDocumentsCountFormatter(value, row) {
        var count = parseInt(value, 10);
        var minimum = parseInt(row.minimum_required_documents, 10);
        var shortfall = parseInt(row.document_shortfall_count, 10);

        if (isNaN(count)) {
            count = 0;
        }

        if (isNaN(minimum)) {
            minimum = 1;
        }

        if (isNaN(shortfall)) {
            shortfall = 0;
        }

        if (row.document_minimum_satisfied === false) {
            return '<span class="text-danger" data-tooltip="true" title="{{ trans('admin/documentframeworkrequirements/table.minimum_required_documents') }}: ' + minimum + ' - {{ trans('admin/documentframeworkrequirements/table.document_shortfall_count') }}: ' + shortfall + ' - {{ trans('admin/documents/form.status_help') }}">' + count + '</span>';
        }

        return count;
    }



    // This is  gross, but necessary so that we can package the API response
    // for custom fields in a more useful way.
    function customFieldsFormatter(value, row) {


            if ((!this) || (!this.title)) {
                return '';
            }

            var field_column = this.title;

            // Pull out any HTMl that might be passed via the presenter
            // (for example, the locked icon for encrypted fields)
            var field_column_plain = field_column.replace(/<(?:.|\n)*?> ?/gm, '');
            if ((row.custom_fields) && (row.custom_fields[field_column_plain])) {

                // If the field type needs special formatting, do that here
                if ((row.custom_fields[field_column_plain].field_format) && (row.custom_fields[field_column_plain].value)) {
                    if (row.custom_fields[field_column_plain].field_format=='URL') {
                        return '<a href="' + row.custom_fields[field_column_plain].value + '" target="_blank" rel="noopener">' + row.custom_fields[field_column_plain].value + '</a>';
                    } else if (row.custom_fields[field_column_plain].field_format=='BOOLEAN') {
                        return (row.custom_fields[field_column_plain].value == 1) ? "<span class='fas fa-check-circle' style='color:green'>" : "<span class='fas fa-times-circle' style='color:red' />";
                    } else if (row.custom_fields[field_column_plain].field_format=='EMAIL') {
                        return '<a href="mailto:' + row.custom_fields[field_column_plain].value + '" style="white-space: nowrap" data-tooltip="true" title="{{ trans('general.send_email') }}"><x-icon type="email" /> ' + row.custom_fields[field_column_plain].value + '</a>';
                    }
                }
                return row.custom_fields[field_column_plain].value;

            }

    }


    function createdAtFormatter(value) {
        if ((value) && (value.formatted)) {
            return value.formatted;
        }
    }

    function externalLinkFormatter(value) {

        if (value) {
            if ((value.indexOf("{") === -1) || (value.indexOf("}") ===-1)) {
                return '<nobr><a href="' + value + '" target="_blank" title="{{ trans('general.external_link_tooltip') }} ' + value + '" data-tooltip="true"><x-icon type="external-link" /> ' + value + '</a></nobr>';
            }
            return value;
        }
    }

    function groupsFormatter(value) {

        if (value) {
            var groups = '';
            for (var index in value.rows) {
                groups += '<a href="{{ config('app.url') }}/admin/groups/' + value.rows[index].id + '" class="label label-default">' + value.rows[index].name + '</a> ';
            }
            return groups;
        }
    }



    function changeLogFormatter(value) {

        var result = '<div style="word-break: break-word;">';
        var pretty_index = '';

            for (var index in value) {


                // Check if it's a custom field
                if (index.startsWith('_snipeit_')) {
                    pretty_index = index.replace("_snipeit_", "Custom:_");
                } else {
                    pretty_index = index;
                }

                extra_pretty_index = prettyLog(pretty_index);

                result += extra_pretty_index + ': <del>' + value[index].old + '</del>  <x-icon type="long-arrow-right" /> ' + value[index].new + '<br>'
            }

        return result+'</div>';

    }

    function prettyLog(str) {
        let frags = str.split('_');
        for (let i = 0; i < frags.length; i++) {
            frags[i] = frags[i].charAt(0).toUpperCase() + frags[i].slice(1);
        }
        return frags.join(' ');
    }

    // Show the warning if below min qty
    function minAmtFormatter(row, value) {

        if ((row) && (row!=undefined)) {

            if (value.remaining <= value.min_amt) {
                return  '<span class="text-danger text-bold" data-tooltip="true" title="{{ trans('admin/licenses/general.below_threshold_short') }}"><x-icon type="warning" class="text-yellow" /> ' + value.min_amt + '</span>';
            }
            return value.min_amt
        }
        return '--';
    }



    // Create a linked phone number in the table list
    function phoneFormatter(value) {
        if (value) {
            return  '<span style="white-space: nowrap;"><a href="tel:' + value + '" data-tooltip="true" title="{{ trans('general.call') }}"><x-icon type="phone" /> ' + value + '</a></span>';
        }
    }

    // Create a linked phone number in the table list
    function mobileFormatter(value) {
        if (value) {
            return  '<span style="white-space: nowrap;"><a href="tel:' + value + '" data-tooltip="true" title="{{ trans('general.call') }}"><x-icon type="mobile" /> ' + value + '</a></span>';
        }
    }


    function deployedLocationFormatter(row, value) {
        if ((row) && (row!=undefined)) {
            // Handle the preceding icon if a tag_color is given in the API response
            if ((row.tag_color) && (row.tag_color!='')) {
                var tag_icon = '<i class="fa-solid fa-square" style="color: ' + row.tag_color + ';" aria-hidden="true"></i> ';
            } else {
                var tag_icon = '';
            }

            return '<nobr>' + tag_icon +'<a href="{{ config('app.url') }}/locations/' + row.id + '">' + row.name + '</a></nobr>';
        } else if (value.rtd_location) {
            return '<a href="{{ config('app.url') }}/locations/' + value.rtd_location.id + '">' + value.rtd_location.name + '</a>';
        }

    }

    function groupsAdminLinkFormatter(value, row) {
        return '<a href="{{ config('app.url') }}/admin/groups/' + row.id + '">' + value + '</a>';
    }

    function assetTagLinkFormatter(value, row) {
        if ((row.asset) && (row.asset.id)) {
            if (row.asset.deleted_at) {
                return '<span style="white-space: nowrap;"><x-icon type="x" class="text-danger" /><span class="sr-only">{{ trans('admin/hardware/general.deleted') }}</span> <del><a href="{{ config('app.url') }}/hardware/' + row.asset.id + '" data-tooltip="true" title="{{ trans('admin/hardware/general.deleted') }}">' + row.asset.asset_tag + '</a></del></span>';
            }
            return '<a href="{{ config('app.url') }}/hardware/' + row.asset.id + '">' + row.asset.asset_tag + '</a>';
        }
        return '';

    }

    function departmentNameLinkFormatter(value, row) {
        if ((row.assigned_user) && (row.assigned_user.department) && (row.assigned_user.department.name)) {
            return '<a href="{{ config('app.url') }}/departments/' + row.assigned_user.department.id + '">' + row.assigned_user.department.name + '</a>';
        }

    }

    function assetNameLinkFormatter(value, row) {
        if ((row.asset) && (row.asset.name)) {
            return '<a href="{{ config('app.url') }}/hardware/' + row.asset.id + '">' + row.asset.name + '</a>';
        }
    }

    function assetSerialLinkFormatter(value, row) {

        if ((row.asset) && (row.asset.serial)) {
            if (row.asset.deleted_at) {
                return '<span style="white-space: nowrap;"><x-icon type="x" class="text-danger" /><span class="sr-only">deleted</span> <del><a href="{{ config('app.url') }}/hardware/' + row.asset.id + '" data-tooltip="true" title="{{ trans('admin/hardware/general.deleted') }}">' + row.asset.serial + '</a></del></span>';
            }
            return '<a href="{{ config('app.url') }}/hardware/' + row.asset.id + '">' + row.asset.serial + '</a>';
        }
        return '';
    }

    function trueFalseFormatter(value) {
        if ((value) && ((value == 'true') || (value == '1'))) {
            return '<x-icon type="checkmark" class="text-success" /><span class="sr-only">{{ trans('general.true') }}</span>';
        } else {
            return '<x-icon type="x" class="text-danger" /><span class="sr-only">{{ trans('general.false') }}</span>';
        }
    }

    function dateDisplayFormatter(value) {
        if (value) {
            return  value.formatted;
        }
    }

    // Renewal/expiry date + a colored "time left" indicator (bar + label) driven by
    // row.renewal_days_left (signed: negative = overdue). Mirrors the "% remaining" idea.
    function renewalDateFormatter(value, row) {
        if (!value) { return ''; }
        var out = value.formatted;
        var days = (row && row.renewal_days_left !== null && typeof row.renewal_days_left !== 'undefined') ? parseInt(row.renewal_days_left, 10) : null;
        if (days === null || isNaN(days)) { return out; }

        var cls, label;
        if (days < 0) {
            cls = 'danger';
            label = '{{ trans('admin/hardware/form.renewal_overdue') }}'.replace(':days', Math.abs(days));
        } else if (days === 0) {
            cls = 'danger';
            label = '{{ trans('admin/hardware/form.renewal_today') }}';
        } else if (days <= 30) {
            cls = 'warning';
            label = '{{ trans('admin/hardware/form.renewal_in_days') }}'.replace(':days', days);
        } else {
            cls = 'success';
            label = '{{ trans('admin/hardware/form.renewal_in_days') }}'.replace(':days', days);
        }
        // Fraction of a 90-day horizon still available (clamped); overdue shows a full red bar.
        var pct = days < 0 ? 100 : Math.max(4, Math.min(100, Math.round((days / 90) * 100)));

        return out
            + '<div class="progress" style="height:6px;margin:4px 0 2px;background:#e9e9e9;">'
            + '<div class="progress-bar progress-bar-' + cls + '" role="progressbar" style="width:' + pct + '%;"></div></div>'
            + '<small class="text-' + cls + '">' + label + '</small>';
    }

    function iconFormatter(value) {
        if (value) {
            return '<i class="' + value + '  icon-med"></i>';
        }
    }

    function emailFormatter(value) {
        if (value) {
            return '<a href="mailto:' + value + '" style="white-space: nowrap" data-tooltip="true" title="{{ trans('general.send_email') }}"><x-icon type="email" /> ' + value + '</a>';
        }
    }

    function linkFormatter(value) {
        if (value) {
            return '<a href="' + value + '">' + value + '</a>';
        }
    }

    function assetCompanyFilterFormatter(value, row) {
        if (value) {
            return '<a href="{{ config('app.url') }}/hardware/?company_id=' + row.id + '">' + value + '</a>';
        }
    }

    function assetCompanyObjFilterFormatter(value, row) {
        if ((row) && (row.company)) {
            return '<a href="{{ config('app.url') }}/hardware/?company_id=' + row.company.id + '">' + row.company.name + '</a>';
        }
    }

    function usersCompanyObjFilterFormatter(value, row) {
        if (value) {
            return '<a href="{{ config('app.url') }}/users/?company_id=' + row.id + '">' + value + '</a>';
        } else {
            return value;
        }
    }

    function locationCompanyObjFilterFormatter(value, row) {
        if (value) {
            return '<a href="{{ url('/') }}/locations/?company_id=' + row.company.id + '">' + row.company.name + '</a>';
        } else {
            return value;
        }
    }

    function employeeNumFormatter(value, row) {

        if ((row) && (row.assigned_to) && ((row.assigned_to.employee_number))) {
            return '<a href="{{ config('app.url') }}/users/' + row.assigned_to.id + '">' + row.assigned_to.employee_number + '</a>';
        }
    }

    function jobtitleFormatter(value, row) {
        if ((row) && (row.assigned_to) && ((row.assigned_to.jobtitle))) {
            return '<a href="{{ config('app.url') }}/users/' + row.assigned_to.id + '">' + row.assigned_to.jobtitle + '</a>';
        }
    }

    function orderNumberObjFilterFormatter(value, row) {
        if (value) {
            return '<a href="{{ config('app.url') }}/hardware/?order_number=' + row.order_number + '">' + row.order_number + '</a>';
        }
    }

    function auditImageFormatter(value, row) {
        if ((row) && (row.file) && (row.file.url)) {
            return '<a href="' + row.file.url + '" data-toggle="lightbox" data-type="image"><img src="' + row.file.url + '" style="max-height: {{ $snipeSettings->thumbnail_max_h }}px; width: auto;" class="img-responsive" alt=""></a>'
        }
    }


   function imageFormatter(value, row) {

        if (value) {

            // This is a clunky override to handle unusual API responses where we're presenting a link instead of an array
            if (row.avatar) {
                var altName = '';
            }
            else if (row.name) {
                var altName = row.name;
            }
            else if ((row) && (row.model)) {
                var altName = row.model.name;
           }
            return '<a href="' + value + '" data-toggle="lightbox" data-type="image"><img src="' + value + '" style="max-height: {{ $snipeSettings->thumbnail_max_h }}px; width: auto;" class="img-responsive" alt="' + altName + '"></a>';
        }
    }


    // This is users in the user accounts section for EULAs
    function downloadFormatter(value) {
        if (value) {
            return '<a href="' + value + '" class="btn btn-sm btn-theme"><x-icon type="download" /></a>';
        }
    }

    // This is used by the UploadedFilesPresenter and the HistoryPresenter
    // It handles the download and inline buttons for files that are uploaded to assets, users, etc
    function fileDownloadButtonsFormatter(row, value) {

        if (value)  {
            if (value.url) {
                var inlinable = value.inlineable;
                var exists_on_disk = value.exists_on_disk;
                var download_url = value.url;
            } else if (value.file) {
                var inlinable = value.file.inlineable;
                var exists_on_disk = value.file.exists_on_disk;
                var download_url = value.file.url;
            } else {
                return '';
            }

            var download_button = '<a href="' + download_url + '" class="btn btn-sm btn-theme" data-tooltip="true" title="{{ trans('general.download') }}"><x-icon type="download" /></a>';
            var download_button_disabled = '<span data-tooltip="true" title="{{ trans('general.file_does_not_exist') }}"><a class="btn btn-sm btn-theme disabled"><x-icon type="download" /></a></span>';
            var inline_button = '<a href="'+ download_url +'?inline=true" class="btn btn-sm btn-theme" target="_blank" data-tooltip="true" title="{{ trans('general.open_new_window') }}"><x-icon type="external-link" /></a>';
            var inline_button_disabled = '<span data-tooltip="true" title="{{ trans('general.file_not_inlineable') }}"><a class="btn btn-sm btn-theme disabled" target="_blank" data-tooltip="true" title="{{ trans('general.file_does_not_exist') }}"><x-icon type="external-link" /></a></span>';

            if (exists_on_disk === true) {
                if (inlinable === true) {
                    return '<span style="white-space: nowrap;">' + download_button + ' ' + inline_button + '</span>';
                } else {
                    return '<span style="white-space: nowrap;">' + download_button + ' ' + inline_button_disabled + '</span>';
                }
            } else {
                return '<span style="white-space: nowrap;">' + download_button_disabled + ' ' + inline_button_disabled + '</span>';
            }

        }
    }


    function filePreviewFormatter(row, value) {

        if ((value) && (value.url) && (value.inlineable)) {

            if (value.mediatype == 'image') {
                return '<a href="' + value.url + '" data-toggle="lightbox" data-type="image"><img src="' + value.url + '" style="max-height: {{ $snipeSettings->thumbnail_max_h }}px; width: auto;" class="img-responsive" alt=""></a>';
            } else if (value.mediatype == 'video') {
                return '<a href="' + value.url + '?inline=true" data-toggle="lightbox" data-type="video"><video style="max-height: {{ $snipeSettings->thumbnail_max_h }}px; width: auto;" class="img-responsive"><source src="' + value.url + '?inline=true"></video></a>';
            } else if (value.mediatype == 'audio') {
                return '<audio controls><source src="' + value.url + '?inline=true" type="audio/mp3">Your browser does not support the audio element.</audio>';
            }
            return '{{ trans('general.preview_not_available') }}';
        }
        return '{{ trans('general.preview_not_available') }}';

    }




    // This is used in the table listings
    function deleteUploadFormatter(value, row) {

        if ((row.available_actions) && (row.available_actions.delete === true)) {
            var destination;

            // This is kinda gross, but for right now we're posting to the GUI delete routes
            // All of these URLS and storage directories need to be updated to be more consistent :(
            if (row.item.type === 'assetmodels') {
                destination = 'models';
            } else if (row.item.type === 'assets') {
                destination = 'hardware';
            } else if (row.item.type === 'customercontracts') {
                destination = 'contracts';
            } else {
                destination = row.item.type;
            }

            // Edit-note button. row.note is already HTML-escaped server-side, so it is safe to
            // embed in a double-quoted attribute; the modal handler reads it back decoded.
            var noteAttr = (row.note != null) ? row.note : '';
            var editButton = '<a href="#" class="actions btn btn-default btn-sm edit-file-note" '
                + ' data-toggle="modal" data-target="#editFileNoteModal"'
                + ' data-object-type="' + destination + '" data-object-id="' + row.item.id + '" data-file-id="' + row.id + '"'
                + ' data-note="' + noteAttr + '" data-tooltip="true" title="{{ trans('general.edit') }}">'
                + '<x-icon type="edit" /><span class="sr-only">{{ trans('general.edit') }}</span></a>&nbsp;';

            var deleteButton = '<a href="{{ config('app.url') }}/' + destination + '/' + row.item.id + '/files/' + row.id + '/delete" '
                + ' data-target="#dataConfirmModal" class="actions btn btn-danger btn-sm delete-asset" data-tooltip="true"  '
                + ' data-toggle="modal" data-icon="fa-trash"'
                + ' data-content="{{ trans('general.file_upload_status.confirm_delete') }}: ' + row.filename + '?" '
                + ' data-title="{{  trans('general.delete') }}" onClick="return false;" data-icon="fa-trash">'
                + '<x-icon type="delete" /><span class="sr-only">{{ trans('general.delete') }}</span></a>&nbsp;';

            return editButton + deleteButton;
        }
    }

    // This handles the custom view for the filestable blade component gallery-card component
    window.customViewFormatter = data => {
        const template = $('#fileGalleryTemplate').html()
        let view = ''

        $.each(data, function (i, row) {

            delete_url = row.url +'/delete';

            if (row.exists_on_disk === true)
            {
                if (row.mediatype === 'image') {
                    embed_code = '<a href="' + row.url + '" data-toggle="lightbox" data-type="image" data-title="' + row.filename + row.filename + '" data-footer="' + row.note + '" class="embed-responsive-item"><img src="' + row.url + '?inline=true" alt="" style="max-width: 100%"></a>';
                } else if (row.mediatype === 'video') {
                    embed_code = '<a href="' + row.url + '" data-toggle="lightbox" data-type="video" data-title="' + row.filename + row.filename + '" data-footer="' + row.note + '" class="embed-responsive-item"><video controls><source src="' + row.url + '?inline=true" type="video/mp4">Your browser does not support the video tag.</video></a>';
                } else if (row.mediatype === 'audio') {
                    embed_code = '<audio style="width: 100%" controls><source src="' + row.url + '?inline=true" type="audio/mpeg">Your browser does not support the audio element.</audio>';
                } else if (row.mediatype === 'pdf') {
                    embed_code = '<object height="200" style="width: 100%" type="application/pdf" data="' + row.url + '?inline=true">File cannot be displayed</object>';
                } else {
                    embed_code = '<div class="text-center"><a href="' + row.url + '?inline=true"><i class="' + row.icon + '" style="font-size: 50px" /></i></a></div>';
                }
            } else {
                embed_code = '<div class="text-center text-danger" style="padding-top: 20px;"><i class="fa-solid fa-heart-crack" style="font-size: 80px" /></i> <br><br>{{ trans('general.file_upload_status.file_not_found') }}</div>';
            }

            view += template.replace('%ID%', row.id)
                .replace('%ICON%', row.icon)
                .replace('%FILETYPE%', row.filetype)
                .replace('%FILE_URL%', row.url)
                .replace('%LINK_URL%', row.url)
                .replace('%FILENAME%', (row.exists_on_disk === true) ? row.filename : '<x-icon type="x" /> <del>' + row.filename + '</del>')
                .replace('%CREATED_AT%', row.created_at.formatted)
                .replace('%CREATED_BY%', (row.created_by) ? row.created_by.name : '')
                .replace('%NOTE%', (row.note) ? row.note : '')
                .replace('%PANEL_CLASS%', (row.exists_on_disk === true) ? 'default' : 'danger')
                .replace('%FILE_EMBED%', embed_code)
                .replace('%DOWNLOAD_BUTTON%', (row.exists_on_disk === true) ? '<a href="'+ row.url +'" class="btn btn-sm btn-theme"><x-icon type="download" /></a> ' : '<span class="btn btn-sm btn-theme disabled" data-tooltip="true" title="{{ trans('general.file_upload_status.file_not_found') }}"><x-icon type="download" /></span>')
                .replace('%NEW_WINDOW_BUTTON%', (row.exists_on_disk === true) ? '<a href="'+ row.url +'?inline=true" class="btn btn-sm btn-theme" target="_blank"><x-icon type="external-link" /></a> ' : '<span class="btn btn-sm btn-theme disabled" data-tooltip="true" title="{{ trans('general.file_upload_status.file_not_found') }}"><x-icon type="external-link"/></span>')
                .replace('%DELETE_BUTTON%', (row.available_actions.delete === true) ?
                    '<a href="'+delete_url+'" class="delete-asset btn btn-danger btn-sm" data-icon="fa-trash" data-toggle="modal" data-content="{{ trans('general.file_upload_status.confirm_delete') }} '+ row.filename +'?" data-title="{{ trans('general.delete') }}" onClick="return false;" data-target="#dataConfirmModal"><x-icon type="delete" /><span class="sr-only">{{ trans('general.delete') }}</span></a>' :
                    '<a class="btn btn-sm btn-danger disabled" data-tooltip="true" title="{{ trans('general.file_upload_status.file_not_found') }}"><x-icon type="delete" /><span class="sr-only">{{ trans('general.delete') }}</span></a>'
                );
        })

        return `<div class="row">${view}</div>`
    }



    function fileNameFormatter(row, value) {

        if (value) {
            if ((value.file) && (value.file.filename) && (value.file.url)) {

                if (value.file.exists_on_disk === true) {
                    return '<a href="' + value.file.url + '">' + value.file.filename + '</a>';
                }

                return '<span class="text-danger" style="text-decoration: line-through;" data-tooltip="true" title="{{ trans('general.file_does_not_exist') }}"><x-icon type="x" /> ' + value.file.filename + '</span>';

            } else if ((value.filename) && (value.url)) {
                if (value.exists_on_disk === true) {
                    return '<a href="' + value.url + '">' + value.filename + '</a>';
                }
                return '<span class="text-danger" style="text-decoration: line-through;" data-tooltip="true" title="{{ trans('general.file_does_not_exist') }}"><x-icon type="x" /> ' + value.filename + '</span>';
            }
        }

    }

    function fileIntegrityFormatter(value) {
        if (! value) {
            return '';
        }

        if (value.sha256) {
            return '<span class="label label-success" data-tooltip="true" title="' + value.algorithm.toUpperCase() + ': ' + value.sha256 + '">' + value.status + '</span>';
        }

        return '<span class="label label-default">' + value.status + '</span>';
    }


    function linkToUserSectionBasedOnCount (count, id, section) {
        if (count) {
            return '<a href="{{ config('app.url') }}/users/' + id + '#' + section +'">' + count + '</a>';
        }

        return count;
    }

    function linkNumberToUserAssetsFormatter(value, row) {
        return linkToUserSectionBasedOnCount(value, row.id, 'asset');
    }

    function linkNumberToUserLicensesFormatter(value, row) {
        return linkToUserSectionBasedOnCount(value, row.id, 'licenses');
    }

    function linkNumberToUserConsumablesFormatter(value, row) {
        return linkToUserSectionBasedOnCount(value, row.id, 'consumables');
    }

    function linkNumberToUserAccessoriesFormatter(value, row) {
        return linkToUserSectionBasedOnCount(value, row.id, 'accessories');
    }

    function linkNumberToUserDocumentsFormatter(value, row) {
        return linkToUserSectionBasedOnCount(value, row.id, 'documents');
    }

    function linkNumberToUserTicketsFormatter(value, row) {
        return linkToUserSectionBasedOnCount(value, row.id, 'tickets');
    }

    function linkNumberToUserManagedUsersFormatter(value, row) {
        return linkToUserSectionBasedOnCount(value, row.id, 'managed-users');
    }

    function linkNumberToUserManagedLocationsFormatter(value, row) {
        return linkToUserSectionBasedOnCount(value, row.id, 'managed-locations');
    }

    function labelPerPageFormatter(value, row, index, field) {
        if (row) {
            if (!row.hasOwnProperty('sheet_info')) { return 1; }
            else { return row.sheet_info.labels_per_page; }
        }
    }

    function labelRadioFormatter(value, row, index, field) {
        if (row) {
            return row.name == '{{ str_replace("\\", "\\\\", $snipeSettings->label2_template) }}';
        }
    }

    function labelSizeFormatter(value, row) {
        if (row) {
            return row.width + ' x ' + row.height + ' ' + row.unit;
        }
    }

    function cleanFloat(number) {
        if(!number) { // in a JavaScript context, meaning, if it's null or zero or unset
            return 0.0;
        }
        if ("{{$snipeSettings->digit_separator}}" == "1.234,56") {
            // yank periods, change commas to periods
            periodless = number.toString().replace(/\./g,"");
            decimalfixed = periodless.replace(/,/g,".");
        } else {
            // yank commas, that's it.
            decimalfixed = number.toString().replace(/\,/g,"");
        }
        return parseFloat(decimalfixed);
    }


    function qtySumFormatter(data) {
        var currentField = this.field;
        var total = 0;
        var fieldname = this.field;

        $.each(data, function() {
            var r = this;
            total += this[currentField];
        });
        return total;
    }

    function sumFormatter(data) {
        if (Array.isArray(data)) {
            var field = this.field;
            var total_sum = data.reduce(function(sum, row) {

                return (sum) + (cleanFloat(row[field]) || 0);
            }, 0);

            return numberWithCommas(total_sum.toFixed(2));
        }
        return 'not an array';
    }

    function sumFormatterQuantity(data){
        if(Array.isArray(data)) {

            // Prevents issues on page load where data is an empty array
            if(data[0] == undefined){
                return 0.00
            }
            // Check that we are actually trying to sum cost from a table
            // that has a quantity column. We must perform this check to
            // support licences which use seats instead of qty
            if('qty' in data[0]) {
                var multiplier = 'qty';
            } else if('seats' in data[0]) {
                var multiplier = 'seats';
            } else {
                return 'no quantity';
            }
            var total_sum = data.reduce(function(sum, row) {
                return (sum) + (cleanFloat(row["purchase_cost"])*row[multiplier] || 0);
            }, 0);
            return numberWithCommas(total_sum.toFixed(2));
        }
        return 'not an array';
    }

    function numberWithCommas(value) {

        if ((value) && ("{{$snipeSettings->digit_separator}}" == "1.234,56")){
            var parts = value.toString().split(".");
             parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
             return parts.join(",");
         } else {
             var parts = value.toString().split(",");
             parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
             return parts.join(".");
        }
        return value
    }

    $(function () {
        $('#bulkEdit').click(function () {
            var selectedIds = $('.snipe-table').bootstrapTable('getSelections');
            $.each(selectedIds, function(key,value) {
                $( "#bulkForm" ).append($('<input type="hidden" name="ids[' + value.id + ']" value="' + value.id + '">' ));
            });

        });
    });

    $(function() {

        // This handles the search box highlighting on both ajax and client-side
        // bootstrap tables
        var searchboxHighlighter = function (event) {

            $('.search-input').each(function (index, element) {

                if ($(element).val() != '') {
                    $(element).addClass('search-highlight');
                    $(element).next().children().addClass('search-highlight');
                } else {
                    $(element).removeClass('search-highlight');
                    $(element).next().children().removeClass('search-highlight');
                }
            });
        };

        $("[name='clearSearch']").click(function () {

            // This hacks around a stupid issue in BS tables where the search text would get remembered for way too long even after it was cleared
            for (storedSearch in localStorage) {
                if (storedSearch.endsWith('.bs.table.searchText')) {
                    localStorage.removeItem(storedSearch);
                }
            }

            $('.search-input').each(function (index, element) {
                $(element).val('');
            });
        });

        $('.search button[name=clearSearch]').click(searchboxHighlighter);
        searchboxHighlighter({ name:'pageload'});
        $('.search-input').keyup(searchboxHighlighter);

        //  This is necessary to make the bootstrap tooltips work inside of the
        // wenzhixin/bootstrap-table formatters
        $('#table').on('post-body.bs.table', function () {
            $('[data-tooltip="true"]').tooltip({
                container: 'body'
            });


        });
    });

</script>

@endpush

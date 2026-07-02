{{-- Module-organized sidebar. Each module section (Asset, ERP, Compliance, Documents,
     Tickets, Administration) is introduced by a header and groups its own pages +
     reports + settings. Per-tenant feature flags gate the modular sections. Every
     item preserves its original route + permission gate. --}}
@php
    $ccSidebarTenant = $navbarActiveTenant ?? $navbarCurrentTenant ?? null;
    $ccCanSeeServices = ($navbarCanAccessTenantAdminArea ?? false) && $ccSidebarTenant;

    $ccAssetVisible = Gate::allows('index', \App\Models\Asset::class)
        || Gate::allows('view', \App\Models\License::class)
        || Gate::allows('index', \App\Models\Accessory::class)
        || Gate::allows('view', \App\Models\Consumable::class)
        || Gate::allows('view', \App\Models\PredefinedKit::class);

    $ccErpVisible = \App\Models\Tenant::currentContextHasFeature(\App\Models\Tenant::FEATURE_ERP)
        && Gate::allows('view', \App\Models\CustomerContract::class);

    $ccNis2 = \App\Models\Tenant::currentContextHasFeature(\App\Models\Tenant::FEATURE_NIS2);
    // Compliance area shows if ANY compliance domain module is enabled (NIS2/GDPR/DL81/ISO27001/AI Act/ISO9001).
    $ccAnyCompliance = collect(array_keys(\App\Models\Tenant::complianceFeatureDomains()))
        ->contains(fn ($feat) => \App\Models\Tenant::currentContextHasFeature($feat));
    $ccComplianceInner = $ccCanSeeServices || Gate::allows('view', \App\Models\DocumentType::class) || Gate::allows('view', \App\Models\DocumentFramework::class);
    $ccNisReports = Gate::allows('reports.nis_risk_matrix.view') || Gate::allows('reports.nis_real_coverage.view');
    $ccComplianceSection = $ccAnyCompliance && ($ccComplianceInner || $ccNisReports);

    $ccDocsVisible = \App\Models\Tenant::currentContextHasFeature(\App\Models\Tenant::FEATURE_DOCUMENTS) && Gate::allows('index', \App\Models\Document::class);
    $ccTicketsVisible = \App\Models\Tenant::currentContextHasFeature(\App\Models\Tenant::FEATURE_TICKETS) && Gate::allows('index', \App\Models\Ticket::class);

    $ccAdminVisible = Gate::allows('view', \App\Models\User::class)
        || Gate::allows('view', \App\Models\Manufacturer::class)
        || Gate::allows('view', \App\Models\Supplier::class)
        || Gate::allows('view', \App\Models\Company::class)
        || Gate::allows('view', \App\Models\Location::class)
        || Gate::allows('view', \App\Models\Department::class)
        || Gate::allows('import')
        || Gate::allows('admin');
@endphp

{{-- Dashboard (cross-cutting) --}}
@can('admin')
    <li {!! (\request()->route()->getName()=='home' ? ' class="active"' : '') !!} class="firstnav">
        <a href="{{ route('home') }}">
            <x-icon type="dashboard" class="fa-fw" />
            <span>{{ trans('general.dashboard') }}</span>
        </a>
    </li>
@endcan

{{-- ═══════════════ ASSET / INVENTARIO ═══════════════ --}}
@if ($ccAssetVisible)
    <li class="header">{{ trans('nav/modules.asset') }}</li>
@endif
@can('index', \App\Models\Asset::class)
    <li class="treeview{{ ((request()->is('statuslabels/*') || request()->is(['hardware*', 'maintenances*'])) ? ' active' : '') }}">
        <a href="#">
            <x-icon type="assets" class="fa-fw" />
            <span>{{ trans('general.assets') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            <li {!! (request()->is('hardware/overview') ? ' class="active"' : '') !!}>
                <a href="{{ route('hardware.overview') }}">
                    <x-icon type="dashboard" class="text-grey fa-fw"/>
                    {{ trans('admin/hardware/general.overview_title') }}
                </a>
            </li>
            <li {!! (!request()->query('status_type') && (request()->is('hardware')) ? ' class="active"' : '') !!}>
                <a href="{{ url('hardware') }}">
                    <x-icon type="circle" class="text-grey fa-fw"/>
                    {{ trans('general.list_all') }}
                    <span class="badge">{{ (isset($total_assets)) ? $total_assets : '' }}</span>
                </a>
            </li>

            <?php $status_navs = \App\Models\Statuslabel::where('show_in_nav', '=', 1)->withCount('assets as asset_count')->get(); ?>
            @if (count($status_navs) > 0)
                @foreach ($status_navs as $status_nav)
                    <li{!! (request()->is('statuslabels/'.$status_nav->id) ? ' class="active"' : '') !!}>
                        <a href="{{ route('statuslabels.show', ['statuslabel' => $status_nav->id]) }}">
                            <i class="fas fa-circle text-grey fa-fw" aria-hidden="true"{!!  ($status_nav->color!='' ? ' style="color: '.e($status_nav->color).'"' : '') !!}></i>
                            {{ $status_nav->name }}
                            <span class="badge badge-secondary">{{ $status_nav->asset_count }}</span></a></li>
                @endforeach
            @endif

            <li id="deployed-sidenav-option" {!! (request()->query('status_type') == 'Deployed' ? ' class="active"' : '') !!}>
                <a href="{{ url('hardware?status_type=Deployed') }}">
                    <x-icon type="circle" class="text-blue fa-fw" />
                    {{ trans('general.deployed') }}
                    <span class="badge">{{ (isset($total_deployed_sidebar)) ? $total_deployed_sidebar : '' }}</span>
                </a>
            </li>
            <li id="rtd-sidenav-option"{!! (request()->query('status_type') == 'RTD' ? ' class="active"' : '') !!}>
                <a href="{{ url('hardware?status_type=RTD') }}">
                    <x-icon type="circle" class="text-green fa-fw" />
                    {{ trans('general.ready_to_deploy') }}
                    <span class="badge">{{ (isset($total_rtd_sidebar)) ? $total_rtd_sidebar : '' }}</span>
                </a>
            </li>
            <li id="pending-sidenav-option"{!! (request()->query('status_type') == 'Pending' ? ' class="active"' : '') !!}>
                <a href="{{ url('hardware?status_type=Pending') }}">
                    <x-icon type="circle" class="text-orange fa-fw" />
                    {{ trans('general.pending') }}
                    <span class="badge">{{ (isset($total_pending_sidebar)) ? $total_pending_sidebar : '' }}</span>
                </a>
            </li>
            <li id="undeployable-sidenav-option"{!! (request()->query('status') == 'Undeployable' ? ' class="active"' : '') !!} ><a
                    href="{{ url('hardware?status_type=Undeployable') }}">
                    <x-icon type="x" class="text-red fa-fw" />
                    {{ trans('general.undeployable') }}
                    <span class="badge">{{ (isset($total_undeployable_sidebar)) ? $total_undeployable_sidebar : '' }}</span>
                </a>
            </li>
            <li id="byod-sidenav-option"{!! (request()->query('status_type') == 'byod' ? ' class="active"' : '') !!}>
                <a href="{{ url('hardware?status_type=byod') }}">
                    <x-icon type="x" class="text-red fa-fw" />
                    {{ trans('general.byod') }}
                    <span class="badge">{{ (isset($total_byod_sidebar)) ? $total_byod_sidebar : '' }}</span>
                </a>
            </li>
            <li id="archived-sidenav-option"{!! (request()->query('status_type') == 'Archived' ? ' class="active"' : '') !!}>
                <a href="{{ url('hardware?status_type=Archived') }}">
                    <x-icon type="x" class="text-red fa-fw" />
                    {{ trans('admin/hardware/general.archived') }}
                    <span class="badge">{{ (isset($total_archived_sidebar)) ? $total_archived_sidebar : '' }}</span>
                </a>
            </li>
            <li id="requestable-sidenav-option"{!! (request()->query('status_type') == 'Requestable' ? ' class="active"' : '') !!}>
                <a href="{{ url('hardware?status_type=Requestable') }}">
                    <x-icon type="checkmark" class="text-blue fa-fw" />
                    {{ trans('admin/hardware/general.requestable') }}
                </a>
            </li>

            @can('audit', \App\Models\Asset::class)
                <li id="audit-due-sidenav-option"{!! (request()->is('hardware/audit/due') ? ' class="active"' : '') !!}>
                    <a href="{{ route('assets.audit.due') }}">
                        <x-icon type="audit" class="text-yellow fa-fw"/>
                        {{ trans('general.audit_due') }}
                        <span class="badge">{{ (isset($total_due_and_overdue_for_audit)) ? $total_due_and_overdue_for_audit : '' }}</span>
                    </a>
                </li>
            @endcan

            @can('checkin', \App\Models\Asset::class)
                <li id="checkin-due-sidenav-option"{!! (request()->is('hardware/checkins/due') ? ' class="active"' : '') !!}>
                    <a href="{{ route('assets.checkins.due') }}">
                        <x-icon type="due" class="text-orange fa-fw"/>
                        {{ trans('general.checkin_due') }}
                        <span class="badge">{{ (isset($total_due_and_overdue_for_checkin)) ? $total_due_and_overdue_for_checkin : '' }}</span>
                    </a>
                </li>
            @endcan

            <li class="divider">&nbsp;</li>
            @can('checkin', \App\Models\Asset::class)
                <li{!! (request()->is('hardware/quickscancheckin') ? ' class="active"' : '') !!}>
                    <a href="{{ route('hardware/quickscancheckin') }}">{{ trans('general.quickscan_checkin') }}</a>
                </li>
            @endcan

            @can('checkout', \App\Models\Asset::class)
                <li{!! (request()->is('hardware/bulkcheckout') ? ' class="active"' : '') !!}>
                    <a href="{{ route('hardware.bulkcheckout.show') }}">{{ trans('general.bulk_checkout') }}</a>
                </li>
                <li{!! (request()->is('hardware/requested') ? ' class="active"' : '') !!}>
                    <a href="{{ route('assets.requested') }}">{{ trans('general.requested') }}</a>
                </li>
            @endcan

            @can('create', \App\Models\Asset::class)
                <li{!! (request()->query('status_type') == 'Deleted' ? ' class="active"' : '') !!}>
                    <a href="{{ url('hardware?status_type=Deleted') }}">{{ trans('general.deleted') }}</a>
                </li>
                <li {!! (request()->is('maintenances') ? ' class="active"' : '') !!}>
                    <a href="{{ route('maintenances.index') }}">{{ trans('general.maintenances') }}</a>
                </li>
            @endcan
            @can('admin')
                <li id="import-history-sidenav-option" {!! (request()->is('hardware/history') ? ' class="active"' : '') !!}>
                    <a href="{{ url('hardware/history') }}">{{ trans('general.import-history') }}</a>
                </li>
            @endcan
            @can('audit', \App\Models\Asset::class)
                <li id="bulk-audit-sidenav-option" {!! (request()->is('hardware/bulkaudit') ? ' class="active"' : '') !!}>
                    <a href="{{ route('assets.bulkaudit') }}">{{ trans('general.bulkaudit') }}</a>
                </li>
            @endcan
        </ul>
    </li>
@endcan
@can('view', \App\Models\License::class)
    <li{!! (request()->is('licenses*') ? ' class="active"' : '') !!}>
        <a href="{{ route('licenses.index') }}">
            <x-icon type="licenses" class="fa-fw"/>
            <span>{{ trans('general.licenses') }}</span>
        </a>
    </li>
@endcan
@can('index', \App\Models\Accessory::class)
    <li id="accessories-sidenav-option"{!! (request()->is('accessories*') ? ' class="active"' : '') !!}>
        <a href="{{ route('accessories.index') }}">
            <x-icon type="accessories" class="fa-fw" />
            <span>{{ trans('general.accessories') }}</span>
        </a>
    </li>
@endcan
@can('view', \App\Models\Consumable::class)
    <li id="consumables-sidenav-option"{!! (request()->is('consumables*') ? ' class="active"' : '') !!}>
        <a href="{{ url('consumables') }}">
            <x-icon type="consumables" class="fa-fw" />
            <span>{{ trans('general.consumables') }}</span>
        </a>
    </li>
@endcan
@can('view', \App\Models\PredefinedKit::class)
    <li id="kits-sidenav-option"{!! (request()->is('kits') ? ' class="active"' : '') !!}>
        <a href="{{ route('kits.index') }}">
            <x-icon type="kits" class="fa-fw" />
            <span>{{ trans('general.kits') }}</span>
        </a>
    </li>
@endcan

{{-- Asset reports --}}
@can('reports.view')
    <li class="treeview{{ (request()->is('reports/activity') || request()->is('reports/custom') || request()->is('reports/audit') || request()->is('reports/depreciation') || request()->is('reports/licenses') || request()->is('reports/maintenances') || request()->is('reports/unaccepted_assets') || request()->is('reports/accessories')) ? ' active' : '' }}">
        <a href="#" class="dropdown-toggle">
            <x-icon type="reports" class="fa-fw" />
            <span>{{ trans('nav/modules.reports') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            <li {{!! (request()->is('reports/activity') ? ' class="active"' : '') !!}}><a href="{{ route('reports.activity') }}">{{ trans('general.activity_report') }}</a></li>
            <li {{!! (request()->is('reports/custom') ? ' class="active"' : '') !!}}><a href="{{ url('reports/custom') }}">{{ trans('general.custom_report') }}</a></li>
            <li {{!! (request()->is('reports/audit') ? ' class="active"' : '') !!}}><a href="{{ route('reports.audit') }}">{{ trans('general.audit_report') }}</a></li>
            <li {{!! (request()->is('reports/depreciation') ? ' class="active"' : '') !!}}><a href="{{ url('reports/depreciation') }}">{{ trans('general.depreciation_report') }}</a></li>
            <li {{!! (request()->is('reports/licenses') ? ' class="active"' : '') !!}}><a href="{{ url('reports/licenses') }}">{{ trans('general.license_report') }}</a></li>
            <li {{!! (request()->is('reports/maintenances') ? ' class="active"' : '') !!}}><a href="{{ route('ui.reports.maintenances') }}">{{ trans('general.asset_maintenance_report') }}</a></li>
            <li {{!! (request()->is('reports/unaccepted_assets') ? ' class="active"' : '') !!}}><a href="{{ url('reports/unaccepted_assets') }}">{{ trans('general.unaccepted_asset_report') }}</a></li>
            <li {{!! (request()->is('reports/accessories') ? ' class="active"' : '') !!}}><a href="{{ url('reports/accessories') }}">{{ trans('general.accessory_report') }}</a></li>
        </ul>
    </li>
@endcan

{{-- Asset settings --}}
@can('backend.interact')
    <li id="settings-sidenav-option" class="treeview {!! (request()->is(App\Helpers\Helper::SettingUrls()) ? ' active' : '') !!}">
        <a href="#" id="settings">
            <x-icon type="settings" class="fa-fw" />
            <span>{{ trans('nav/modules.settings') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            @if(Gate::allows('view', App\Models\CustomField::class) || Gate::allows('view', App\Models\CustomFieldset::class))
                <li {!! (request()->is('fields*') ? ' class="active"' : '') !!}><a href="{{ route('fields.index') }}">{{ trans('admin/custom_fields/general.custom_fields') }}</a></li>
            @endif
            @can('view', \App\Models\Statuslabel::class)
                <li {!! (request()->is('statuslabels*') ? ' class="active"' : '') !!}><a href="{{ route('statuslabels.index') }}">{{ trans('general.status_labels') }}</a></li>
            @endcan
            @can('view', \App\Models\AssetModel::class)
                <li {{!! (request()->is('models*') ? ' class="active"' : '') !!}}><a href="{{ route('models.index') }}">{{ trans('general.asset_models') }}</a></li>
            @endcan
            @can('view', \App\Models\Category::class)
                <li {{!! (request()->is('categories*') ? ' class="active"' : '') !!}}><a href="{{ route('categories.index') }}">{{ trans('general.categories') }}</a></li>
            @endcan
            @can('view', \App\Models\Depreciation::class)
                <li {{!! (request()->is('depreciations*') ? ' class="active"' : '') !!}}><a href="{{ route('depreciations.index') }}">{{ trans('general.depreciation') }}</a></li>
            @endcan
        </ul>
    </li>
@endcan

{{-- ═══════════════ ERP / GESTIONALE ═══════════════ --}}
@if ($ccErpVisible)
    <li class="header">{{ trans('nav/modules.erp') }}</li>
    <li class="treeview{{ (request()->is('erp*') || request()->is('contracts*') || request()->is('reports/contract-forecast')) ? ' active' : '' }}">
        <a href="#" class="dropdown-toggle">
            <x-icon type="erp" class="fa-fw" />
            <span>{{ trans('erp/general.title') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            @can('view', \App\Models\CustomerContract::class)
                <li {!! (request()->is('erp') ? ' class="active"' : '') !!}><a href="{{ route('erp.index') }}">{{ trans('erp/general.nav.cockpit') }}</a></li>
                <li {!! (request()->is('contracts*') ? ' class="active"' : '') !!}><a href="{{ route('contracts.index') }}">{{ trans('erp/general.modules.contracts') }}</a></li>
                <li {!! (request()->is('erp/notule*') ? ' class="active"' : '') !!}><a href="{{ route('erp.notule.index') }}">{{ trans('erp/notule.title') }}</a></li>
            @endcan
            @can('reports.view')
                <li {!! (request()->is('erp/fotografia') ? ' class="active"' : '') !!}><a href="{{ route('erp.fotografia') }}">{{ trans('erp/fotografia.nav') }}</a></li>
                <li {!! (request()->is('erp/controllo-gestione') ? ' class="active"' : '') !!}><a href="{{ route('erp.controllo') }}">{{ trans('erp/controllo.title') }}</a></li>
                <li {!! (request()->is('erp/riconciliazione') ? ' class="active"' : '') !!}><a href="{{ route('erp.riconciliazione') }}">{{ trans('erp/riconciliazione.title') }}</a></li>
                <li {!! (request()->is('erp/bilancio-simulato') ? ' class="active"' : '') !!}><a href="{{ route('erp.bilancio') }}">{{ trans('erp/bilancio.nav') }}</a></li>
                <li {!! (request()->is('erp/bilanci*') ? ' class="active"' : '') !!}><a href="{{ route('erp.bilanci.index') }}">{{ trans('erp/bilanci.nav') }}</a></li>
                <li {!! (request()->is('erp/finanziamenti*') ? ' class="active"' : '') !!}><a href="{{ route('erp.finanziamenti.index') }}">{{ trans('erp/finanziamenti.nav') }}</a></li>
                <li {!! (request()->is('erp/previsionali*') ? ' class="active"' : '') !!}><a href="{{ route('erp.previsionali.index') }}">{{ trans('erp/previsionali.nav') }}</a></li>
                <li {!! (request()->is('reports/contract-forecast') ? ' class="active"' : '') !!}><a href="{{ route('reports.contract-forecast') }}">{{ trans('erp/general.nav.forecast') }}</a></li>
                <li {!! (request()->is('erp/ammortamenti') ? ' class="active"' : '') !!}><a href="{{ route('erp.ammortamenti') }}">{{ trans('erp/general.nav.ammortamenti') }}</a></li>
            @endcan
        </ul>
    </li>
@endif

{{-- ═══════════════ COMPLIANCE — un menu a sé per ogni framework attivo ═══════════════ --}}
@if ($ccComplianceSection)
    <li class="header">{{ trans('nav/modules.compliance') }}</li>
    @php($ccDomainLabels = \App\Models\ComplianceDomain::options())
    @php($ccCanFrameworks = Gate::allows('view', \App\Models\DocumentFramework::class))

    {{-- Catalogo & impostazioni condivise: servizi, tipologie documento, elenco completo framework/requisiti. --}}
    @if ($ccComplianceInner)
        <li class="treeview{{ (request()->routeIs('tenants.services.*') || request()->is('documenttypes*') || ((request()->is('documentframeworks*') || request()->is('documentframeworkrequirements*')) && ! request('compliance_domain'))) ? ' active' : '' }}">
            <a href="#" class="dropdown-toggle">
                <x-icon type="settings" class="fa-fw" />
                <span>{{ trans('nav/modules.compliance_catalog') }}</span>
                <x-icon type="angle-left" class="pull-right fa-fw"/>
            </a>
            <ul class="treeview-menu">
                @if ($ccCanSeeServices)
                    <li {!! (request()->routeIs('tenants.services.*') ? ' class="active"' : '') !!}><a href="{{ route('tenants.services.index', $ccSidebarTenant) }}">{{ trans('admin/tenantservices/general.sidebar_title') }}</a></li>
                @endif
                @can('view', \App\Models\DocumentType::class)
                    <li {!! (request()->is('documenttypes*') ? ' class="active"' : '') !!}><a href="{{ route('documenttypes.index') }}">{{ trans('general.document_types') }}</a></li>
                @endcan
                @if ($ccCanFrameworks)
                    <li {!! (request()->is('documentframeworks*') && ! request('compliance_domain') ? ' class="active"' : '') !!}><a href="{{ route('documentframeworks.index') }}">{{ trans('general.document_frameworks') }}</a></li>
                    <li {!! (request()->is('documentframeworkrequirements*') && ! request('compliance_domain') ? ' class="active"' : '') !!}><a href="{{ route('documentframeworkrequirements.index') }}">{{ trans('general.document_framework_requirements') }}</a></li>
                @endif
            </ul>
        </li>
    @endif

    {{-- Un modulo (menu) separato per ogni dominio compliance attivo: NIS2, GDPR, D.Lgs. 81, ISO 27001, AI Act, ISO 9001. --}}
    @foreach (\App\Models\Tenant::complianceFeatureDomains() as $ccFeat => $ccDom)
        @php($ccDomEnabled = \App\Models\Tenant::currentContextHasFeature($ccFeat) && isset($ccDomainLabels[$ccDom]))
        @php($ccDomReports = $ccDom === 'nis2' && $ccNisReports)
        @if ($ccDomEnabled && ($ccCanFrameworks || $ccDomReports))
            @php($ccDomActive = (request()->is('documentframeworks*') || request()->is('documentframeworkrequirements*')) && request('compliance_domain') === $ccDom)
            @php($ccDomReportsActive = $ccDomReports && (request()->is('reports/nis-risk-matrix') || request()->is('reports/nis-real-coverage')))
            <li class="treeview{{ ($ccDomActive || $ccDomReportsActive) ? ' active' : '' }}">
                <a href="#" class="dropdown-toggle">
                    <x-icon type="compliance" class="fa-fw" />
                    <span>{{ $ccDomainLabels[$ccDom] }}</span>
                    <x-icon type="angle-left" class="pull-right fa-fw"/>
                </a>
                <ul class="treeview-menu">
                    @if ($ccCanFrameworks)
                        <li {!! (request()->is('documentframeworks*') && request('compliance_domain') === $ccDom ? ' class="active"' : '') !!}><a href="{{ route('documentframeworks.index', ['compliance_domain' => $ccDom]) }}">{{ trans('general.document_frameworks') }}</a></li>
                        <li {!! (request()->is('documentframeworkrequirements*') && request('compliance_domain') === $ccDom ? ' class="active"' : '') !!}><a href="{{ route('documentframeworkrequirements.index', ['compliance_domain' => $ccDom]) }}">{{ trans('general.document_framework_requirements') }}</a></li>
                    @endif
                    @if ($ccDomReports)
                        @can('reports.nis_risk_matrix.view')
                            <li {!! (request()->is('reports/nis-risk-matrix') ? ' class="active"' : '') !!}><a href="{{ route('reports.nis-risk-matrix') }}">{{ trans('admin/reports/general.nis_risk_matrix') }}</a></li>
                        @endcan
                        @can('reports.nis_real_coverage.view')
                            <li {!! (request()->is('reports/nis-real-coverage') ? ' class="active"' : '') !!}><a href="{{ route('reports.nis-real-coverage') }}">{{ trans('admin/reports/general.nis_real_coverage') }}</a></li>
                        @endcan
                    @endif
                </ul>
            </li>
        @endif
    @endforeach
@endif

{{-- ═══════════════ DOCUMENTI ═══════════════ --}}
@if ($ccDocsVisible)
    <li class="header">{{ trans('nav/modules.documents') }}</li>
    <li class="treeview{{ (request()->is('documents*') ? ' active' : '') }}">
        <a href="#">
            <x-icon type="documents" class="fa-fw" />
            <span>{{ trans('general.documents') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            <li{!! (!request()->query('status') && !request()->query('review_status') && !request()->query('status_type') && request()->is('documents') ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index') }}"><x-icon type="circle" class="text-grey fa-fw"/> {{ trans('general.list_all') }}<span class="badge">{{ $total_documents ?? '' }}</span></a>
            </li>
            <li{!! (request()->routeIs('documents.evidence_requests.index') ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.evidence_requests.index') }}"><x-icon type="requests" class="text-yellow fa-fw"/> {{ trans('admin/documents/general.delegated_evidence_requests') }}</a>
            </li>
            <li{!! (request()->query('status') == 'active' ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index', ['status' => 'active']) }}"><x-icon type="circle" class="text-green fa-fw"/> {{ trans('admin/documents/general.statuses.active') }}<span class="badge">{{ $total_documents_active ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('status') == 'draft' ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index', ['status' => 'draft']) }}"><x-icon type="circle" class="text-blue fa-fw"/> {{ trans('admin/documents/general.statuses.draft') }}<span class="badge">{{ $total_documents_draft ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('status') == 'in_review' ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index', ['status' => 'in_review']) }}"><x-icon type="circle" class="text-orange fa-fw"/> {{ trans('admin/documents/general.statuses.in_review') }}<span class="badge">{{ $total_documents_in_review ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('review_status') == 'due' ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index', ['review_status' => 'due']) }}"><x-icon type="calendar" class="text-yellow fa-fw"/> {{ trans('admin/documents/general.review_due') }}<span class="badge">{{ $total_documents_due_review ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('review_status') == 'overdue' ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index', ['review_status' => 'overdue']) }}"><x-icon type="expiration" class="text-red fa-fw"/> {{ trans('admin/documents/general.review_overdue') }}<span class="badge">{{ $total_documents_overdue_review ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('status') == 'obsolete' ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index', ['status' => 'obsolete']) }}"><x-icon type="x" class="text-red fa-fw"/> {{ trans('admin/documents/general.statuses.obsolete') }}<span class="badge">{{ $total_documents_obsolete ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('status') == 'archived' ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index', ['status' => 'archived']) }}"><x-icon type="files" class="text-grey fa-fw"/> {{ trans('admin/documents/general.statuses.archived') }}<span class="badge">{{ $total_documents_archived ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('status_type') == 'Deleted' ? ' class="active"' : '') !!}>
                <a href="{{ route('documents.index', ['status_type' => 'Deleted']) }}"><x-icon type="delete" class="text-red fa-fw"/> {{ trans('general.deleted') }}<span class="badge">{{ $total_documents_deleted ?? '' }}</span></a>
            </li>
        </ul>
    </li>
@endif

{{-- ═══════════════ TICKET / HELPDESK ═══════════════ --}}
@if ($ccTicketsVisible)
    <li class="header">{{ trans('nav/modules.tickets') }}</li>
    <li class="treeview{{ (request()->is('tickets*') ? ' active' : '') }}">
        <a href="#">
            <x-icon type="tickets" class="fa-fw" />
            <span>{{ trans('general.tickets') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            <li{!! (!request()->query('queue') && !request()->query('ticket_status_id') && request()->is('tickets') ? ' class="active"' : '') !!}>
                <a href="{{ route('tickets.index') }}"><x-icon type="circle" class="text-grey fa-fw"/> {{ trans('general.list_all') }}<span class="badge">{{ $total_tickets ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('queue') == 'open' ? ' class="active"' : '') !!}>
                <a href="{{ route('tickets.index', ['queue' => 'open']) }}"><x-icon type="circle" class="text-green fa-fw"/> {{ trans('admin/tickets/general.open_queue') }}<span class="badge">{{ $total_tickets_open ?? '' }}</span></a>
            </li>
            <li{!! (request()->query('assignee_id') == 'me' ? ' class="active"' : '') !!}>
                <a href="{{ route('tickets.index', ['assignee_id' => 'me']) }}"><x-icon type="users" class="text-blue fa-fw"/> {{ trans('admin/tickets/general.my_queue') }}<span class="badge">{{ $total_tickets_my_queue ?? '' }}</span></a>
            </li>
            <li{!! (request()->boolean('unassigned') ? ' class="active"' : '') !!}>
                <a href="{{ route('tickets.index', ['unassigned' => 1]) }}"><x-icon type="warning" class="text-orange fa-fw"/> {{ trans('admin/tickets/general.unassigned_queue') }}<span class="badge">{{ $total_tickets_unassigned ?? '' }}</span></a>
            </li>
        </ul>
    </li>
@endif

{{-- ═══════════════ AMMINISTRAZIONE / SISTEMA ═══════════════ --}}
@if ($ccAdminVisible)
    <li class="header">{{ trans('nav/modules.administration') }}</li>
@endif
@can('view', \App\Models\User::class)
    <li class="treeview{{ (request()->is('users*') ? ' active' : '') }}" id="users-sidenav-option">
        <a href="#" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=6" : ''}}>
            <x-icon type="users" class="fa-fw" />
            <span>{{ trans('general.people') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            <li {!! ((request()->is('users')  && (request()->input() == null)) ? ' class="active"' : '') !!} id="users-sidenav-list-all">
                <a href="{{ route('users.index') }}"><x-icon type="circle" class="text-grey fa-fw fa-fw"/> {{ trans('general.list_all') }}</a>
            </li>
            <li class="{{ (request()->is('users') && request()->input('superadmins') == "true") ? 'active' : '' }}" id="users-sidenav-superadmins">
                <a href="{{ route('users.index', ['superadmins' => 'true']) }}"><x-icon type="superadmin" class="text-danger fa-fw"/> {{ trans('general.show_superadmins') }}</a>
            </li>
            <li class="{{ (request()->is('users') && request()->input('admins') == "true") ? 'active' : '' }}" id="users-sidenav-list-admins">
                <a href="{{ route('users.index', ['admins' => 'true']) }}"><x-icon type="admin" class="text-warning fa-fw"/> {{ trans('general.show_admins') }}</a>
            </li>
            <li class="{{ (request()->is('users') && request()->input('status') == "deleted") ? 'active' : '' }}" id="users-sidenav-deleted">
                <a href="{{ route('users.index', ['status' => 'deleted']) }}"><x-icon type="x" class="text-danger fa-fw"/> {{ trans('general.deleted_users') }}</a>
            </li>
            <li class="{{ (request()->is('users') && request()->input('activated') == "1") ? 'active' : '' }}" id="users-sidenav-activated">
                <a href="{{ route('users.index', ['activated' => true]) }}"><i class="fa-solid fa-person-circle-check text-success fa-fw"></i> {{ trans('general.login_enabled') }}</a>
            </li>
            <li class="{{ (request()->is('users') && request()->input('activated') == "0") ? 'active' : '' }}" id="users-sidenav-not-activated">
                <a href="{{ route('users.index', ['activated' => false]) }}"><i class="fa-solid fa-person-circle-xmark text-danger fa-fw"></i> {{ trans('general.login_disabled') }}</a>
            </li>
        </ul>
    </li>
@endcan
@if (Gate::allows('view', \App\Models\Manufacturer::class) || Gate::allows('view', \App\Models\Supplier::class) || Gate::allows('view', \App\Models\Customer::class))
    <li class="treeview{{ (request()->is('manufacturers*') || request()->is('suppliers*') || request()->is('customers*')) ? ' active' : '' }}">
        <a href="#" class="dropdown-toggle">
            <x-icon type="records" class="fa-fw" />
            <span>{{ trans('general.nav_records') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            @can('view', \App\Models\Customer::class)
                <li {!! (request()->is('customers*') ? ' class="active"' : '') !!}><a href="{{ route('customers.index') }}">{{ trans('general.customers') }}</a></li>
            @endcan
            @can('view', \App\Models\Manufacturer::class)
                <li {!! (request()->is('manufacturers*') ? ' class="active"' : '') !!}><a href="{{ route('manufacturers.index') }}">{{ trans('general.manufacturers') }}</a></li>
            @endcan
            @can('view', \App\Models\Supplier::class)
                <li {!! (request()->is('suppliers*') ? ' class="active"' : '') !!}><a href="{{ route('suppliers.index') }}">{{ trans('general.suppliers') }}</a></li>
            @endcan
        </ul>
    </li>
@endif
@if (Gate::allows('view', \App\Models\Company::class) || Gate::allows('view', \App\Models\Location::class) || Gate::allows('view', \App\Models\Department::class))
    <li class="treeview{{ (request()->is('companies*') || request()->is('locations*') || request()->is('departments*')) ? ' active' : '' }}">
        <a href="#" class="dropdown-toggle">
            <x-icon type="organization" class="fa-fw" />
            <span>{{ trans('general.nav_organization') }}</span>
            <x-icon type="angle-left" class="pull-right fa-fw"/>
        </a>
        <ul class="treeview-menu">
            @can('view', \App\Models\Company::class)
                <li {!! (request()->is('companies*') ? ' class="active"' : '') !!}><a href="{{ route('companies.index') }}">{{ trans('general.companies') }}</a></li>
            @endcan
            @can('view', \App\Models\Location::class)
                <li {!! (request()->is('locations*') ? ' class="active"' : '') !!}><a href="{{ route('locations.index') }}">{{ trans('general.locations') }}</a></li>
            @endcan
            @can('view', \App\Models\Department::class)
                <li {!! (request()->is('departments*') ? ' class="active"' : '') !!}><a href="{{ route('departments.index') }}">{{ trans('general.departments') }}</a></li>
            @endcan
        </ul>
    </li>
@endif
@can('import')
    <li id="import-sidenav-option"{!! (request()->is('import*') ? ' class="active"' : '') !!}>
        <a href="{{ route('imports.index') }}">
            <x-icon type="import" class="fa-fw" />
            <span>{{ trans('general.import') }}</span>
        </a>
    </li>
@endcan
@can('viewRequestable', \App\Models\Asset::class)
    <li{!! (request()->is('account/requestable-assets') ? ' class="active"' : '') !!}>
        <a href="{{ route('requestable-assets') }}">
            <x-icon type="requestable" class="fa-fw" />
            <span>{{ trans('general.requestable_items') }}</span>
        </a>
    </li>
@endcan
{{-- System settings (general settings, groups, tenants, API docs) are reached by
     superadmins via the gear icon in the top bar — intentionally not in the sidebar. --}}

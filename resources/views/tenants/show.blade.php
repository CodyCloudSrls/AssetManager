@extends('layouts/default')

@section('title')
    {{ trans('admin/tenants/general.title') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('tenants.index') }}" class="btn btn-default">{{ trans('general.back') }}</a>
@stop

@php
    $tenantRoleOptions = [
        \App\Models\Tenant::ROLE_ADMIN => trans('admin/tenants/general.role_admin'),
        \App\Models\Tenant::ROLE_VIEWER => trans('admin/tenants/general.role_viewer'),
    ];
    $tenantDrilldowns = [
        'requirements_total' => route('documentframeworkrequirements.index', ['tenant_id' => $tenant->id]),
        'requirements_covered' => route('documentframeworkrequirements.index', ['tenant_id' => $tenant->id, 'coverage_status' => \App\Models\DocumentFrameworkRequirement::COVERAGE_COVERED]),
        'requirements_at_risk' => route('documentframeworkrequirements.index', ['tenant_id' => $tenant->id, 'coverage_status' => \App\Models\DocumentFrameworkRequirement::COVERAGE_AT_RISK]),
        'requirements_missing' => route('documentframeworkrequirements.index', ['tenant_id' => $tenant->id, 'coverage_status' => \App\Models\DocumentFrameworkRequirement::COVERAGE_MISSING]),
        'requirements_supporting_only' => route('documentframeworkrequirements.index', ['tenant_id' => $tenant->id, 'coverage_status' => \App\Models\DocumentFrameworkRequirement::COVERAGE_SUPPORTING_ONLY]),
        'documents_total' => route('documents.index', ['tenant_id' => $tenant->id]),
        'documents_due' => route('documents.index', ['tenant_id' => $tenant->id, 'review_status' => 'due']),
        'documents_overdue' => route('documents.index', ['tenant_id' => $tenant->id, 'review_status' => 'overdue']),
        'tickets_open' => route('tickets.index', ['tenant_id' => $tenant->id, 'queue' => 'open']),
        'tickets_sla_at_risk' => route('tickets.index', ['tenant_id' => $tenant->id, 'queue' => 'sla_at_risk']),
        'suppliers_relevant' => route('suppliers.index', ['tenant_id' => $tenant->id, 'nis_relevant' => 1]),
        'suppliers_review_due' => route('suppliers.index', ['tenant_id' => $tenant->id, 'nis_relevant' => 1, 'nis_review_status' => 'due']),
        'suppliers_without_review_date' => route('suppliers.index', ['tenant_id' => $tenant->id, 'nis_relevant' => 1, 'nis_review_status' => 'missing']),
        'assets_nis_relevant' => route('hardware.index', ['tenant_id' => $tenant->id, 'nis_relevant' => 1]),
        'assets_high_impact' => route('hardware.index', ['tenant_id' => $tenant->id, 'nis_relevant' => 1, 'nis_service_impact' => ['high', 'critical']]),
        'frameworks' => route('documentframeworks.index', ['tenant_id' => $tenant->id, 'status' => 'active', 'is_active' => 1]),
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ $rootCompany?->name ?? trans('admin/tenants/general.title') }}</h2>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 style="margin-top: 0;">{{ trans('admin/tenants/general.overview') }}</h4>
                        <table class="table table-striped">
                            <tbody>
                            <tr>
                                <th style="width: 220px;">{{ trans('admin/tenants/general.uuid') }}</th>
                                <td><code>{{ $tenant->uuid }}</code></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.root_company') }}</th>
                                <td>
                                    @if ($rootCompany)
                                        <a href="{{ route('companies.show', $rootCompany) }}">{{ $rootCompany->name }}</a>
                                    @else
                                        -
                                    @endif
                                    <p class="help-block" style="margin:4px 0 0;">{{ trans('admin/tenants/general.root_company_help') }}</p>
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.companies_count') }}</th>
                                <td>{{ $companies->count() }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.members_count') }}</th>
                                <td>{{ $members->count() }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.settings.default_locale') }}</th>
                                <td>{{ trans('localizations.languages')[$tenant->defaultLocale()] ?? $tenant->defaultLocale() }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.settings.default_compliance_jurisdiction') }}</th>
                                <td>{{ \App\Models\Tenant::complianceJurisdictionOptions()[$tenant->defaultComplianceJurisdiction()] ?? $tenant->defaultComplianceJurisdiction() }}</td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.root_company_link') }}</th>
                                <td>
                                    @if ($rootCompany)
                                        <a href="{{ route('companies.edit', $rootCompany) }}" class="btn btn-default btn-sm">{{ trans('admin/tenants/general.edit_root_company') }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.helpdesk.title') }}</th>
                                <td>
                                    <span class="label {{ $tenant->isHelpdeskEnabled() ? 'label-success' : 'label-default' }}">
                                        {{ $tenant->isHelpdeskEnabled() ? trans('general.yes') : trans('general.no') }}
                                    </span>
                                </td>
                            </tr>
                            @if ($canManageTenant)
                                <tr>
                                    <th>{{ trans('admin/tenants/general.config.title') }}</th>
                                    <td>
                                        <a href="{{ route('tenants.config.edit', $tenant) }}" class="btn btn-primary btn-sm">
                                            <x-icon type="settings" class="fa-fw" /> {{ trans('admin/tenants/general.config.open') }}
                                        </a>
                                        <p class="help-block" style="margin:6px 0 0;">{{ trans('admin/tenants/general.config.section_general_help') }}</p>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th>{{ trans('admin/tenantservices/general.title') }}</th>
                                <td>
                                    <a href="{{ route('tenants.services.index', $tenant) }}" class="btn btn-default btn-sm">
                                        {{ trans('admin/tenantservices/general.inventory_title') }}
                                    </a>
                                    <span class="text-muted" style="margin-left: 8px;">
                                        {{ number_format($activeServicesCount) }} / {{ number_format($servicesCount) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.helpdesk.public_url') }}</th>
                                <td><code>{{ $tenant->publicHelpdeskUrl() }}</code></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="callout callout-info" style="margin-top: 0;">
                            <h4>{{ trans('admin/tenants/general.members') }}</h4>
                            <p>{{ trans('admin/tenants/general.members_help') }}</p>
                        </div>
                    </div>
                </div>

                <hr>

                <h4>{{ trans('admin/tenants/general.compliance.title') }}</h4>
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <table class="table table-striped snipe-table">
                            <tbody>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.requirements_total') }}</th>
                                <td><a href="{{ $tenantDrilldowns['requirements_total'] }}">{{ number_format($complianceSummary['requirements']['total']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/documentframeworkrequirements/general.coverage.covered') }}</th>
                                <td><a href="{{ $tenantDrilldowns['requirements_covered'] }}">{{ number_format($complianceSummary['requirements']['covered']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/documentframeworkrequirements/general.coverage.at_risk') }}</th>
                                <td><a href="{{ $tenantDrilldowns['requirements_at_risk'] }}">{{ number_format($complianceSummary['requirements']['at_risk']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/documentframeworkrequirements/general.coverage.missing') }}</th>
                                <td><a href="{{ $tenantDrilldowns['requirements_missing'] }}">{{ number_format($complianceSummary['requirements']['missing']) }}</a></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <table class="table table-striped snipe-table">
                            <tbody>
                            <tr>
                                <th>{{ trans('general.documents') }}</th>
                                <td><a href="{{ $tenantDrilldowns['documents_total'] }}">{{ number_format($complianceSummary['documents']['total']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/documents/general.review_due') }}</th>
                                <td><a href="{{ $tenantDrilldowns['documents_due'] }}">{{ number_format($complianceSummary['documents']['due']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/documents/general.review_overdue') }}</th>
                                <td><a href="{{ $tenantDrilldowns['documents_overdue'] }}">{{ number_format($complianceSummary['documents']['overdue']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.open_tickets') }}</th>
                                <td><a href="{{ $tenantDrilldowns['tickets_open'] }}">{{ number_format($complianceSummary['tickets']['open']) }}</a></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <table class="table table-striped snipe-table">
                            <tbody>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.nis_suppliers') }}</th>
                                <td><a href="{{ $tenantDrilldowns['suppliers_relevant'] }}">{{ number_format($complianceSummary['suppliers']['relevant']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.supplier_reviews_due') }}</th>
                                <td><a href="{{ $tenantDrilldowns['suppliers_review_due'] }}">{{ number_format($complianceSummary['suppliers']['review_due']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.suppliers_without_review') }}</th>
                                <td><a href="{{ $tenantDrilldowns['suppliers_without_review_date'] }}">{{ number_format($complianceSummary['suppliers']['without_review_date']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.sla_at_risk') }}</th>
                                <td><a href="{{ $tenantDrilldowns['tickets_sla_at_risk'] }}">{{ number_format($complianceSummary['tickets']['sla_at_risk']) }}</a></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <table class="table table-striped snipe-table">
                            <tbody>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.nis_assets') }}</th>
                                <td><a href="{{ $tenantDrilldowns['assets_nis_relevant'] }}">{{ number_format($complianceSummary['assets']['nis_relevant']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.high_impact_assets') }}</th>
                                <td><a href="{{ $tenantDrilldowns['assets_high_impact'] }}">{{ number_format($complianceSummary['assets']['high_impact']) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.compliance.frameworks') }}</th>
                                <td><a href="{{ $tenantDrilldowns['frameworks'] }}">{{ number_format($complianceSummary['frameworks']->count()) }}</a></td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/documentframeworkrequirements/general.coverage.supporting_only') }}</th>
                                <td><a href="{{ $tenantDrilldowns['requirements_supporting_only'] }}">{{ number_format($complianceSummary['requirements']['supporting_only']) }}</a></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <table class="table table-striped snipe-table">
                    <thead>
                    <tr>
                        <th>{{ trans('general.document_framework') }}</th>
                        <th>{{ trans('admin/documentframeworks/general.coverage.coverage_percent') }}</th>
                        <th>{{ trans('admin/documentframeworkrequirements/general.coverage.covered') }}</th>
                        <th>{{ trans('admin/documentframeworkrequirements/general.coverage.at_risk') }}</th>
                        <th>{{ trans('admin/documentframeworkrequirements/general.coverage.missing') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($complianceSummary['frameworks'] as $frameworkSummary)
                        <tr>
                            <td><a href="{{ route('documentframeworks.show', $frameworkSummary['id']) }}">{{ $frameworkSummary['name'] }}</a></td>
                            <td>{{ $frameworkSummary['coverage_percent'] }}%</td>
                            <td><a href="{{ route('documentframeworkrequirements.index', ['tenant_id' => $tenant->id, 'document_framework_id' => $frameworkSummary['id'], 'coverage_status' => \App\Models\DocumentFrameworkRequirement::COVERAGE_COVERED]) }}">{{ number_format($frameworkSummary['covered']) }}</a></td>
                            <td><a href="{{ route('documentframeworkrequirements.index', ['tenant_id' => $tenant->id, 'document_framework_id' => $frameworkSummary['id'], 'coverage_status' => \App\Models\DocumentFrameworkRequirement::COVERAGE_AT_RISK]) }}">{{ number_format($frameworkSummary['at_risk']) }}</a></td>
                            <td><a href="{{ route('documentframeworkrequirements.index', ['tenant_id' => $tenant->id, 'document_framework_id' => $frameworkSummary['id'], 'coverage_status' => \App\Models\DocumentFrameworkRequirement::COVERAGE_MISSING]) }}">{{ number_format($frameworkSummary['missing']) }}</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ trans('admin/tenants/general.compliance.no_frameworks') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                @if ($canManageTenant)
                    <hr>

                    <form method="POST" action="{{ route('tenants.members.store', $tenant) }}" class="form-horizontal">
                        @csrf
                        <div class="form-group">
                            <label for="tenant_member_user_id" class="col-md-2 control-label">{{ trans('admin/tenants/general.member_user') }}</label>
                            <div class="col-md-5">
                                <select
                                    name="user_id"
                                    id="tenant_member_user_id"
                                    class="js-data-ajax"
                                    data-endpoint="users?exclude_superusers=1&tenant_exclude={{ $tenant->id }}"
                                    data-placeholder="{{ trans('admin/tenants/general.add_member') }}"
                                    style="width: 100%;"
                                    required></select>
                            </div>
                            <label for="tenant_member_role" class="col-md-2 control-label">{{ trans('admin/tenants/general.role') }}</label>
                            <div class="col-md-2">
                                <select name="role" id="tenant_member_role" class="form-control" required>
                                    @foreach ($tenantRoleOptions as $roleValue => $roleLabel)
                                        <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary btn-block">{{ trans('general.save') }}</button>
                            </div>
                        </div>
                    </form>
                @endif

                <hr>

                <h4>{{ trans('admin/tenants/general.helpdesk.title') }}</h4>
                <table class="table table-striped snipe-table">
                    <tbody>
                    <tr>
                        <th style="width: 220px;">{{ trans('admin/tenants/general.helpdesk.contact_email') }}</th>
                        <td>{{ $tenant->publicHelpdeskContactEmail() ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('admin/tenants/general.helpdesk.contact_phone') }}</th>
                        <td>{{ $tenant->publicHelpdeskContactPhone() ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('admin/tenants/general.helpdesk.public_ticket_types') }}</th>
                        <td>
                            @if ($publicTicketTypes->count() > 0)
                                {{ $publicTicketTypes->pluck('name')->implode(', ') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    </tbody>
                </table>

                <hr>

                <h4>{{ trans('admin/tenants/general.mail.title') }}</h4>
                <table class="table table-striped snipe-table">
                    <tbody>
                    <tr>
                        <th style="width: 220px;">{{ trans('admin/tenants/general.mail.notification_email') }}</th>
                        <td>{{ $tenant->notificationEmail() ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('admin/tenants/general.mail.reply_to_email') }}</th>
                        <td>{{ $tenant->notificationReplyToEmail() ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('admin/tenants/general.mail.reply_to_name') }}</th>
                        <td>{{ $tenant->notificationReplyToName() ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('admin/tenants/general.mail.from_name') }}</th>
                        <td>{{ $tenant->notificationFromName() ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('admin/tenants/general.mail.document_review_warning_days') }}</th>
                        <td>{{ $tenant->documentReviewWarningDays() }} {{ trans('general.days') }}</td>
                    </tr>
                    <tr>
                        <th>{{ trans('admin/tenants/general.mail.events_title') }}</th>
                        <td>{{ collect($tenant->notificationEvents())->map(fn ($event) => \App\Models\Tenant::mailNotificationEventOptions()[$event] ?? $event)->implode(', ') ?: '-' }}</td>
                    </tr>
                    </tbody>
                </table>

                <hr>

                <h4>{{ trans('admin/tenants/general.members') }}</h4>
                <table class="table table-striped snipe-table">
                    <thead>
                    <tr>
                        <th>{{ trans('general.name') }}</th>
                        <th>{{ trans('admin/tenants/general.member_username') }}</th>
                        <th>{{ trans('admin/tenants/general.member_base_company') }}</th>
                        <th>{{ trans('admin/tenants/general.role') }}</th>
                        <th>{{ trans('admin/tenants/general.member_actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>
                                <a href="{{ route('users.show', $member) }}">{{ $member->display_name ?: $member->present()->fullName }}</a>
                            </td>
                            <td>{{ $member->username }}</td>
                            <td>{{ $member->company?->name ?? '-' }}</td>
                            <td>
                                @if ($canManageTenant)
                                    <form method="POST" action="{{ route('tenants.members.update', [$tenant, $member]) }}" class="form-inline">
                                        @csrf
                                        @method('PUT')
                                        <select name="role" class="form-control input-sm">
                                            @foreach ($tenantRoleOptions as $roleValue => $roleLabel)
                                                <option value="{{ $roleValue }}" {{ ($member->pivot->role === $roleValue) ? 'selected' : '' }}>{{ $roleLabel }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">{{ trans('general.update') }}</button>
                                    </form>
                                @else
                                    {{ $tenantRoleOptions[$member->pivot->role] ?? $member->pivot->role }}
                                @endif
                            </td>
                            <td>
                                @if ($canManageTenant)
                                    <form method="POST" action="{{ route('tenants.members.destroy', [$tenant, $member]) }}" onsubmit="return confirm('{{ trans('general.are_you_sure') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">{{ trans('button.delete') }}</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">{{ trans('admin/tenants/general.empty_members') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <hr>

                <h4>
                    {{ trans('admin/tenants/general.companies') }}
                    @if ($rootCompany)
                        @can('create', \App\Models\Company::class)
                            <a href="{{ route('companies.create', ['parent_id' => $rootCompany->id]) }}" class="btn btn-primary btn-sm pull-right">
                                <x-icon type="plus" class="fa-fw" />
                                {{ trans('admin/tenants/general.add_company') }}
                            </a>
                        @endcan
                    @endif
                </h4>
                <p class="help-block">{{ trans('admin/tenants/general.add_company_help') }}</p>
                <table class="table table-striped snipe-table">
                    <thead>
                    <tr>
                        <th>{{ trans('general.name') }}</th>
                        <th>{{ trans('admin/companies/table.parent') }}</th>
                        <th>{{ trans('general.email') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($companies as $company)
                        <tr>
                            <td><a href="{{ route('companies.show', $company) }}">{{ $company->name }}</a></td>
                            <td>{{ $company->parent?->name ?? '-' }}</td>
                            <td>{{ $company->email ?? '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

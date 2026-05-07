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
                                    @if ($canManageTenant)
                                        <a href="{{ route('tenants.helpdesk.edit', $tenant) }}" class="btn btn-default btn-sm" style="margin-left: 8px;">
                                            {{ trans('admin/tenants/general.helpdesk.edit') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.mail.title') }}</th>
                                <td>
                                    @if ($canManageTenant)
                                        <a href="{{ route('tenants.mail.edit', $tenant) }}" class="btn btn-default btn-sm">
                                            {{ trans('admin/tenants/general.mail.edit') }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ trans('admin/tenants/general.settings.title') }}</th>
                                <td>
                                    @if ($canManageTenant)
                                        <a href="{{ route('tenants.settings.edit', $tenant) }}" class="btn btn-default btn-sm">
                                            {{ trans('admin/tenants/general.settings.edit') }}
                                        </a>
                                    @else
                                        -
                                    @endif
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
                                        <button type="submit" class="btn btn-sm btn-primary">{{ trans('button.update') }}</button>
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

                <h4>{{ trans('admin/tenants/general.companies') }}</h4>
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

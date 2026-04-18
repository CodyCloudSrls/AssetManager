@extends('layouts/default')

@section('title')
    {{ trans('admin/tenants/general.title') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('tenants.create') }}" class="btn btn-primary">
        <x-icon type="plus" /> {{ trans('admin/tenants/general.create') }}
    </a>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('admin/tenants/general.title') }}</h2>
            </div>
            <div class="box-body">
                <table class="table table-striped snipe-table">
                    <thead>
                    <tr>
                        <th>{{ trans('admin/tenants/general.uuid') }}</th>
                        <th>{{ trans('admin/tenants/general.root_company') }}</th>
                        <th>{{ trans('admin/tenants/general.companies_count') }}</th>
                        <th>{{ trans('admin/tenants/general.users_count') }}</th>
                        <th>{{ trans('admin/tenants/general.assets_count') }}</th>
                        <th>{{ trans('table.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($tenants as $row)
                        <tr>
                            <td><code>{{ $row->tenant->uuid }}</code></td>
                            <td>
                                <a href="{{ route('tenants.show', $row->tenant) }}">{{ $row->root_company->name }}</a>
                            </td>
                            <td>{{ $row->companies_count }}</td>
                            <td>{{ $row->users_count }}</td>
                            <td>{{ $row->assets_count }}</td>
                            <td>
                                <a href="{{ route('tenants.show', $row->tenant) }}" class="btn btn-sm btn-primary">
                                    <x-icon type="more-info" />
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

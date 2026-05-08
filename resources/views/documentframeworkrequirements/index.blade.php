@extends('layouts/default')

@section('title')
    {{ trans('admin/documentframeworkrequirements/general.work_queue') }}
    @parent
@stop

@section('content')
    <x-container>
        <x-box>
            <div class="row" style="margin-bottom: 15px;">
                <div class="{{ $editableFrameworks->isNotEmpty() ? 'col-md-9' : 'col-md-12' }}">
                    <form method="get" action="{{ route('documentframeworkrequirements.index') }}" class="form-inline" role="search">
                        @foreach (request()->only(['tenant_id']) as $filterName => $filterValue)
                            <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                        @endforeach

                        <div class="form-group">
                            <label for="document_framework_filter" class="sr-only">{{ trans('admin/documentframeworkrequirements/table.framework') }}</label>
                            <select class="form-control select2" name="document_framework_id" id="document_framework_filter" aria-label="{{ trans('admin/documentframeworkrequirements/table.framework') }}" style="min-width: 260px;">
                                <option value="">{{ trans('admin/documentframeworkrequirements/general.all_frameworks') }}</option>
                                @foreach ($frameworks as $framework)
                                    <option value="{{ $framework->id }}" @selected((int) request('document_framework_id') === (int) $framework->id)>{{ $framework->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="coverage_status_filter" class="sr-only">{{ trans('admin/documentframeworkrequirements/table.coverage') }}</label>
                            <select class="form-control select2" name="coverage_status" id="coverage_status_filter" aria-label="{{ trans('admin/documentframeworkrequirements/table.coverage') }}" style="min-width: 220px;">
                                <option value="">{{ trans('admin/documentframeworkrequirements/general.all_coverage_statuses') }}</option>
                                @foreach ($coverageOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(request('coverage_status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <x-icon type="search" />
                            {{ trans('admin/documentframeworkrequirements/general.apply_filters') }}
                        </button>

                        <a href="{{ route('documentframeworkrequirements.index') }}" class="btn btn-default">
                            {{ trans('admin/documentframeworkrequirements/general.clear_filters') }}
                        </a>
                    </form>
                </div>

                @if ($editableFrameworks->isNotEmpty())
                    <div class="col-md-3 text-right">
                        @if ($editableFrameworks->count() === 1)
                            <a href="{{ route('documentframeworkrequirements.create', $editableFrameworks->first()) }}" class="btn btn-primary">
                                <x-icon type="plus" />
                                {{ trans('admin/documentframeworkrequirements/general.create') }}
                            </a>
                        @else
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <x-icon type="plus" />
                                    {{ trans('admin/documentframeworkrequirements/general.create') }}
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    @foreach ($editableFrameworks as $framework)
                                        <li><a href="{{ route('documentframeworkrequirements.create', $framework) }}">{{ $framework->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <x-table.documentframeworkrequirements
                :route="route('api.documentframeworkrequirements.index', request()->only(['tenant_id', 'document_framework_id', 'coverage_status']))"
                table_header="{{ trans('admin/documentframeworkrequirements/general.work_queue') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include('partials.bootstrap-table')
@stop

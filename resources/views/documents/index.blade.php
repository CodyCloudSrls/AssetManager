@extends('layouts/default')

@section('title')
    @if (request('review_status') === 'due')
        {{ trans('admin/documents/general.review_due') }}
    @elseif (request('review_status') === 'overdue')
        {{ trans('admin/documents/general.review_overdue') }}
    @elseif (request('status'))
        {{ trans('admin/documents/general.statuses.'.request('status')) }}
    @elseif (request('status_type') === 'Deleted')
        {{ trans('general.deleted') }}
    @elseif ($selectedRequirement)
        {{ $selectedRequirement->code }}
    @else
        {{ trans('general.all') }}
    @endif
    {{ trans('general.documents') }}
    @parent
@stop

@section('content')
    <x-container>
        <x-box name="documents">
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-12">
                    <form method="get" action="{{ route('documents.index') }}" class="form-inline" role="search">
                        @foreach (request()->only(['status', 'review_status', 'status_type', 'company_id', 'owner_id', 'document_type_id']) as $filterName => $filterValue)
                            <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                        @endforeach

                        <div class="form-group">
                            <label for="document_framework_filter" class="sr-only">{{ trans('admin/documents/form.framework') }}</label>
                            <select class="form-control select2" name="document_framework_id" id="document_framework_filter" aria-label="{{ trans('admin/documents/form.framework') }}" style="min-width: 240px;">
                                <option value="">{{ trans('admin/documents/form.all_frameworks') }}</option>
                                @foreach ($frameworks as $framework)
                                    <option value="{{ $framework->id }}" @selected((int) request('document_framework_id') === (int) $framework->id)>{{ $framework->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="document_requirement_filter" class="sr-only">{{ trans('admin/documents/form.framework_requirements') }}</label>
                            <select class="form-control select2" name="document_framework_requirement_id" id="document_requirement_filter" aria-label="{{ trans('admin/documents/form.framework_requirements') }}" style="min-width: 280px;">
                                <option value="">{{ trans('admin/documents/form.all_requirements') }}</option>
                                @foreach ($requirements as $requirement)
                                    <option value="{{ $requirement->id }}" data-framework-id="{{ $requirement->document_framework_id }}" @selected((int) request('document_framework_requirement_id') === (int) $requirement->id)>
                                        {{ $requirement->code }} - {{ $requirement->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <x-icon type="search" />
                            {{ trans('admin/documents/form.apply_filters') }}
                        </button>

                        <a href="{{ route('documents.index') }}" class="btn btn-default">
                            {{ trans('admin/documents/form.clear_filters') }}
                        </a>
                    </form>
                </div>
            </div>

            <x-table.documents :route="route('api.documents.index', request()->only(['status', 'review_status', 'status_type', 'company_id', 'owner_id', 'document_type_id', 'document_framework_id', 'document_framework_requirement_id']))"/>
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include('partials.bootstrap-table')
    <script nonce="{{ csrf_token() }}">
        $(function () {
            const $frameworkFilter = $('#document_framework_filter');
            const $requirementFilter = $('#document_requirement_filter');

            function syncRequirementFilter() {
                const frameworkId = String($frameworkFilter.val() || '');

                $requirementFilter.find('option[data-framework-id]').each(function () {
                    const $option = $(this);
                    const visible = !frameworkId || String($option.data('framework-id')) === frameworkId;
                    $option.prop('disabled', !visible);
                });

                const selectedRequirement = $requirementFilter.find('option:selected');
                if (selectedRequirement.length && selectedRequirement.prop('disabled')) {
                    $requirementFilter.val('').trigger('change.select2');
                }
            }

            $frameworkFilter.on('change', syncRequirementFilter);
            syncRequirementFilter();
        });
    </script>
@stop

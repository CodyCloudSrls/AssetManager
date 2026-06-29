@extends('layouts/default')

@section('title')
    {{ trans('admin/documentframeworkrequirements/general.work_queue') }}
    @parent
@stop

@section('content')
    <x-container>
        <x-box>
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-12">
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
            </div>

            <x-table.documentframeworkrequirements
                :route="route('api.documentframeworkrequirements.index', request()->only(['tenant_id', 'document_framework_id', 'coverage_status', 'compliance_domain']))"
                table_header="{{ trans('admin/documentframeworkrequirements/general.work_queue') }}"
                buttons="documentframeworkrequirementsButtons"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    <script nonce="{{ csrf_token() }}">
        window.documentframeworkrequirementsButtons = () => ({
            @if ($editableFrameworks->isNotEmpty())
            btnAdd: {
                text: @json(trans('general.create')),
                icon: 'fa fa-plus',
                event () {
                    const createOptions = @json($editableFrameworkCreateOptions);
                    const selectedFrameworkId = String(@json($selectedFrameworkId));
                    const selectedOption = createOptions.find((option) => String(option.id) === selectedFrameworkId);
                    const createTarget = selectedOption || (createOptions.length === 1 ? createOptions[0] : null);

                    if (createTarget) {
                        window.location.href = createTarget.url;
                        return;
                    }

                    const frameworkFilter = $('#document_framework_filter');
                    frameworkFilter.one('select2:select.documentframeworkrequirements-create', function (event) {
                        const option = createOptions.find((item) => String(item.id) === String(event.params.data.id));

                        if (option) {
                            window.location.href = option.url;
                        }
                    });

                    if (frameworkFilter.data('select2')) {
                        frameworkFilter.select2('open');
                    } else {
                        frameworkFilter.trigger('focus');
                    }
                },
                attributes: {
                    class: 'btn-warning',
                    title: @json(trans('admin/documentframeworkrequirements/general.create')),
                    @if ($snipeSettings->shortcuts_enabled == 1)
                    accesskey: 'n'
                    @endif
                }
            },
            @endif
        });
    </script>

    @include('partials.bootstrap-table')
@stop

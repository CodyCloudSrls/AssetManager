@extends('layouts/default')

@section('title')
    {{ trans('admin/documents/general.edit_assignment') }} {{ $document->name }}
    @parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-10 col-lg-offset-1 col-md-12 col-md-offset-0 col-sm-12 col-sm-offset-0">
            <form class="form-horizontal" method="POST" action="{{ route('documents.assignments.update', [$document, $documentAssignment]) }}">
                @csrf
                @method('PUT')

                <div class="box box-default">
                    <div class="box-header with-border">
                        <div class="col-md-9 text-left" style="padding: 0;">
                            <h2 class="box-title" style="padding-top: 8px; padding-bottom: 7px;">
                                {{ $document->name }}
                            </h2>
                        </div>
                        <div class="col-md-3 text-right" style="padding-right: 10px;">
                            <button type="submit" class="btn btn-success pull-right" name="submit">
                                <x-icon type="checkmark" />
                                {{ trans('general.save') }}
                            </button>
                        </div>
                    </div>

                    <div class="box-body">
                        <div style="padding-top: 30px;">
                            @include('documents.partials.assignment-fields', [
                                'document' => $document,
                                'documentAssignment' => $documentAssignment,
                                'assignableTypeToken' => $assignableTypeToken,
                            ])

                            <div class="col-md-12">
                                <hr>
                            </div>

                            <div class="form-group">
                                <div class="col-md-7 col-md-offset-3">
                                    <a href="{{ route('documents.show', $document) }}" class="btn btn-default">
                                        {{ trans('button.cancel') }}
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <x-icon type="checkmark" />
                                        {{ trans('general.save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('moar_scripts')
    <script nonce="{{ csrf_token() }}">
        $(function () {
            function selectedAssignableType() {
                return $('input[name="assignment_assignable_type"]:checked').val();
            }

            function syncAssignableSelectors() {
                const selectedType = selectedAssignableType();
                $('#assignable_user_wrapper').toggle(selectedType === '{{ \App\Models\DocumentAssignment::ASSIGNABLE_USER }}');
                $('#assignable_asset_wrapper').toggle(selectedType === '{{ \App\Models\DocumentAssignment::ASSIGNABLE_ASSET }}');
                $('#assignable_location_id').toggle(selectedType === '{{ \App\Models\DocumentAssignment::ASSIGNABLE_LOCATION }}');
            }

            $('input[name="assignment_assignable_type"]').on('change', syncAssignableSelectors);
            $('#document_assignment_advanced_toggle').on('click', function () {
                $('#document_assignment_advanced_details').slideToggle('fast');
                $('#document_assignment_advanced_icon').toggleClass('fa-caret-right fa-caret-down');
            });
            syncAssignableSelectors();

            @if ($errors->has('issued_at') || $errors->has('completed_at') || $errors->has('revoked_at') || $errors->has('notes'))
                $('#document_assignment_advanced_icon').removeClass('fa-caret-right').addClass('fa-caret-down');
            @endif
        });
    </script>
@endsection

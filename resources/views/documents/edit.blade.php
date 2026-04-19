@extends('layouts/edit-form', [
    'createText' => trans('admin/documents/form.create'),
    'updateText' => trans('admin/documents/form.update'),
    'topSubmit' => true,
    'formAction' => $document->id ? route('documents.update', $document) : route('documents.store'),
    'index_route' => 'documents.index',
    'item' => $document,
    'container_classes' => 'col-lg-10 col-lg-offset-1 col-md-12 col-md-offset-0 col-sm-12 col-sm-offset-0',
    'options' => [
        'back' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.previous_page')]),
        'index' => trans('admin/hardware/form.redirect_to_all', ['type' => trans('general.documents')]),
        'item' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.document')]),
    ],
])

@section('inputFields')

    @include ('partials.forms.edit.name', ['translated_name' => trans('general.name'), 'required' => 'true', 'item' => $document])
    @include ('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $document])
    @include ('partials.forms.edit.user-select', ['translated_name' => trans('admin/documents/form.owner'), 'fieldname' => 'owner_id', 'item' => $document, 'required' => 'false'])
    @include ('partials.forms.edit.document-type-select', ['translated_name' => trans('admin/documents/form.document_type'), 'fieldname' => 'document_type_id', 'item' => $document, 'required' => 'false'])
    @include ('partials.forms.edit.document-framework-select', ['translated_name' => trans('admin/documents/form.framework'), 'fieldname' => 'document_framework_id', 'item' => $document, 'required' => 'false'])

    <div class="col-md-12 col-sm-12">
        <fieldset name="document-framework-coverage">
            <x-form.legend>
                <a id="document_framework_requirements_toggle">
                    <x-icon type="caret-right" class="fa-fw" id="document_framework_requirements_icon" />
                    {{ trans('admin/documents/form.framework_requirements_section') }}
                </a>
            </x-form.legend>

            <div id="document_framework_requirements_details" class="col-md-12" style="display:none">
                <div class="form-group">
                    <label for="primary_requirement_ids" class="col-md-3 control-label">{{ trans('admin/documents/form.primary_requirements') }}</label>
                    <div class="col-md-7">
                        <select class="form-control select2" multiple name="primary_requirement_ids[]" id="primary_requirement_ids" aria-label="primary_requirement_ids">
                            @foreach ($frameworkRequirements as $requirement)
                                <option value="{{ $requirement->id }}" @selected(in_array($requirement->id, old('primary_requirement_ids', $selectedPrimaryRequirementIds), true))>
                                    {{ $requirement->code }} - {{ $requirement->title }}
                                </option>
                            @endforeach
                        </select>
                        <p class="help-block">{{ trans('admin/documents/form.primary_requirements_help') }}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="supporting_requirement_ids" class="col-md-3 control-label">{{ trans('admin/documents/form.supporting_requirements') }}</label>
                    <div class="col-md-7">
                        <select class="form-control select2" multiple name="supporting_requirement_ids[]" id="supporting_requirement_ids" aria-label="supporting_requirement_ids">
                            @foreach ($frameworkRequirements as $requirement)
                                <option value="{{ $requirement->id }}" @selected(in_array($requirement->id, old('supporting_requirement_ids', $selectedSupportingRequirementIds), true))>
                                    {{ $requirement->code }} - {{ $requirement->title }}
                                </option>
                            @endforeach
                        </select>
                        <p class="help-block">{{ trans('admin/documents/form.supporting_requirements_help') }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
    </div>

    <div class="form-group {{ $errors->has('document_number') ? ' has-error' : '' }}">
        <label for="document_number" class="col-md-3 control-label">{{ trans('admin/documents/form.document_number') }}</label>
        <div class="col-md-4">
            <input class="form-control" type="text" name="document_number" id="document_number" value="{{ old('document_number', $document->document_number) }}">
            {!! $errors->first('document_number', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('reference') ? ' has-error' : '' }}">
        <label for="reference" class="col-md-3 control-label">{{ trans('admin/documents/form.reference') }}</label>
        <div class="col-md-7">
            <input class="form-control" type="text" name="reference" id="reference" value="{{ old('reference', $document->reference) }}">
            {!! $errors->first('reference', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('version') ? ' has-error' : '' }}">
        <label for="version" class="col-md-3 control-label">{{ trans('admin/documents/form.version') }}</label>
        <div class="col-md-3">
            <input class="form-control" type="text" name="version" id="version" value="{{ old('version', $document->version) }}">
            {!! $errors->first('version', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('status') ? ' has-error' : '' }}">
        <label for="status" class="col-md-3 control-label">{{ trans('general.status') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="status" id="status" aria-label="status">
                @foreach ($documentStatuses as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected(old('status', $document->status ?: \App\Models\Document::STATUS_DRAFT) == $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            {!! $errors->first('status', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="col-md-12 col-sm-12">
        <fieldset name="document-governance">
            <x-form.legend>
                <a id="document_governance_toggle">
                    <x-icon type="caret-right" class="fa-fw" id="document_governance_icon" />
                    {{ trans('admin/documents/form.governance_section') }}
                </a>
            </x-form.legend>

            <div id="document_governance_details" class="col-md-12" style="display:none">
                <div class="form-group {{ $errors->has('classification') ? ' has-error' : '' }}">
                    <label for="classification" class="col-md-3 control-label">{{ trans('admin/documents/form.classification') }}</label>
                    <div class="col-md-4">
                        <input class="form-control" type="text" name="classification" id="classification" value="{{ old('classification', $document->classification) }}">
                        {!! $errors->first('classification', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('retention_period') ? ' has-error' : '' }}">
                    <label for="retention_period" class="col-md-3 control-label">{{ trans('admin/documents/form.retention_period') }}</label>
                    <div class="col-md-4">
                        <input class="form-control" type="text" name="retention_period" id="retention_period" value="{{ old('retention_period', $document->retention_period) }}">
                        {!! $errors->first('retention_period', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('scope') ? ' has-error' : '' }}">
                    <label for="scope" class="col-md-3 control-label">{{ trans('admin/documents/form.scope') }}</label>
                    <div class="col-md-7">
                        <input class="form-control" type="text" name="scope" id="scope" value="{{ old('scope', $document->scope) }}">
                        {!! $errors->first('scope', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('issued_at') ? ' has-error' : '' }}">
                    <label for="issued_at" class="col-md-3 control-label">{{ trans('admin/documents/form.issued_at') }}</label>
                    <div class="col-md-4">
                        <x-input.datepicker name="issued_at" :value="old('issued_at', optional($document->issued_at)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
                        {!! $errors->first('issued_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('effective_at') ? ' has-error' : '' }}">
                    <label for="effective_at" class="col-md-3 control-label">{{ trans('admin/documents/form.effective_at') }}</label>
                    <div class="col-md-4">
                        <x-input.datepicker name="effective_at" :value="old('effective_at', optional($document->effective_at)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
                        {!! $errors->first('effective_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('next_review_at') ? ' has-error' : '' }}">
                    <label for="next_review_at" class="col-md-3 control-label">{{ trans('admin/documents/form.next_review_at') }}</label>
                    <div class="col-md-4">
                        <x-input.datepicker name="next_review_at" :value="old('next_review_at', optional($document->next_review_at)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
                        {!! $errors->first('next_review_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>
            </div>
        </fieldset>
    </div>

    <div class="col-md-12 col-sm-12">
        <fieldset name="document-content">
            <x-form.legend>
                <a id="document_content_toggle">
                    <x-icon type="caret-right" class="fa-fw" id="document_content_icon" />
                    {{ trans('admin/documents/form.content_section') }}
                </a>
            </x-form.legend>

            <div id="document_content_details" class="col-md-12" style="display:none">
                <div class="form-group {{ $errors->has('control_url') ? ' has-error' : '' }}">
                    <label for="control_url" class="col-md-3 control-label">{{ trans('admin/documents/form.control_url') }}</label>
                    <div class="col-md-7">
                        <input class="form-control" type="url" name="control_url" id="control_url" value="{{ old('control_url', $document->control_url) }}" placeholder="https://example.com/document">
                        {!! $errors->first('control_url', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('summary') ? ' has-error' : '' }}">
                    <label for="summary" class="col-md-3 control-label">{{ trans('admin/documents/form.summary') }}</label>
                    <div class="col-md-7">
                        <textarea class="form-control" name="summary" id="summary" rows="4">{{ old('summary', $document->summary) }}</textarea>
                        <p class="help-block">{!! trans('general.markdown') !!}</p>
                        {!! $errors->first('summary', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('notes') ? ' has-error' : '' }}">
                    <label for="notes" class="col-md-3 control-label">{{ trans('general.notes') }}</label>
                    <div class="col-md-7">
                        <textarea class="form-control" name="notes" id="notes" rows="4">{{ old('notes', $document->notes) }}</textarea>
                        <p class="help-block">{!! trans('general.markdown') !!}</p>
                        {!! $errors->first('notes', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>
            </div>
        </fieldset>
    </div>

@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(function () {
        function bindToggle(toggleId, contentId, iconId) {
            $(toggleId).on('click', function () {
                $(contentId).slideToggle('fast');
                $(iconId).toggleClass('fa-caret-right fa-caret-down');
            });
        }

        bindToggle('#document_governance_toggle', '#document_governance_details', '#document_governance_icon');
        bindToggle('#document_content_toggle', '#document_content_details', '#document_content_icon');
        bindToggle('#document_framework_requirements_toggle', '#document_framework_requirements_details', '#document_framework_requirements_icon');

        const requirementMap = @json($frameworkRequirementOptionsByFramework);
        const primarySelect = $('#primary_requirement_ids');
        const supportingSelect = $('#supporting_requirement_ids');
        const selectedPrimary = (@json(old('primary_requirement_ids', $selectedPrimaryRequirementIds)) || []).map(String);
        const selectedSupporting = (@json(old('supporting_requirement_ids', $selectedSupportingRequirementIds)) || []).map(String);

        function populateRequirementOptions(frameworkId) {
            const options = requirementMap[String(frameworkId || '')] || [];

            primarySelect.empty();
            supportingSelect.empty();

            $.each(options, function (_, requirement) {
                const label = requirement.code + ' - ' + requirement.title;
                const optionValue = String(requirement.id);
                primarySelect.append(new Option(label, optionValue, false, selectedPrimary.includes(optionValue)));
                supportingSelect.append(new Option(label, optionValue, false, selectedSupporting.includes(optionValue)));
            });

            primarySelect.trigger('change.select2');
            supportingSelect.trigger('change.select2');
        }

        populateRequirementOptions($('#document_framework_id_select').val());

        $('#document_framework_id_select').on('change', function () {
            populateRequirementOptions($(this).val());
        });

        @if ($errors->any())
            $('#document_governance_details, #document_content_details, #document_framework_requirements_details').show();
            $('#document_governance_icon, #document_content_icon, #document_framework_requirements_icon').removeClass('fa-caret-right').addClass('fa-caret-down');
        @endif
    });
</script>
@stop

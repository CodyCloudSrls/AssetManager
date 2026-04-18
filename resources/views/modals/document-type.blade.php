{{-- See snipeit_modals.js for what powers this --}}
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
            <h2 class="modal-title">{{ trans('admin/documenttypes/general.create') }}</h2>
        </div>
        <div class="modal-body">
            <form action="{{ route('api.documenttypes.store') }}" onsubmit="return false">
                <div class="dynamic-form-row">
                    @include('partials.forms.edit.name', ['item' => new \App\Models\DocumentType(), 'translated_name' => trans('admin/documenttypes/table.name')])
                </div>
                <div class="dynamic-form-row">
                    <div class="col-md-4 col-xs-12"><label for="document-type-company_id">{{ trans('general.company') }}:</label></div>
                    <div class="col-md-8 col-xs-12">
                        <x-input.select
                            name="company_id"
                            id="document-type-company_id"
                            :options="\App\Models\Company::orderBy('name')->pluck('name', 'id')->prepend(trans('general.select_company'), '')->all()"
                            :selected="old('company_id')"
                            style="width:100%;"
                        />
                    </div>
                </div>
                <div class="dynamic-form-row">
                    <div class="col-md-4 col-xs-12"><label for="document-type-visibility_type">{{ trans('general.template_visibility.label') }}:</label></div>
                    <div class="col-md-8 col-xs-12">
                        <x-input.select
                            name="visibility_type"
                            id="document-type-visibility_type"
                            :options="\App\Models\DocumentType::visibilityOptions()"
                            :selected="old('visibility_type', \App\Models\DocumentType::VISIBILITY_PRIVATE)"
                            style="width:100%;"
                        />
                    </div>
                </div>
            </form>
        </div>
        <div class="dynamic-form-row">
            @include('modals.partials.footer')
        </div>
    </div>
</div>

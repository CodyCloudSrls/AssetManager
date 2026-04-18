{{-- See snipeit_modals.js for what powers this --}}
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
            <h2 class="modal-title">{{ trans('admin/documentframeworks/general.create') }}</h2>
        </div>
        <div class="modal-body">
            <form action="{{ route('api.documentframeworks.store') }}" onsubmit="return false">
                <div class="dynamic-form-row">
                    @include('partials.forms.edit.name', ['item' => new \App\Models\DocumentFramework(), 'translated_name' => trans('admin/documentframeworks/table.name')])
                </div>
                <div class="dynamic-form-row">
                    <div class="col-md-4 col-xs-12"><label for="document-framework-company_id">{{ trans('general.company') }}:</label></div>
                    <div class="col-md-8 col-xs-12">
                        <x-input.select
                            name="company_id"
                            id="document-framework-company_id"
                            :options="\App\Models\Company::orderBy('name')->pluck('name', 'id')->prepend(trans('general.select_company'), '')->all()"
                            :selected="old('company_id')"
                            style="width:100%;"
                        />
                    </div>
                </div>
                <div class="dynamic-form-row">
                    <div class="col-md-4 col-xs-12"><label for="document-framework-visibility_type">{{ trans('general.template_visibility.label') }}:</label></div>
                    <div class="col-md-8 col-xs-12">
                        <x-input.select
                            name="visibility_type"
                            id="document-framework-visibility_type"
                            :options="\App\Models\DocumentFramework::visibilityOptions()"
                            :selected="old('visibility_type', \App\Models\DocumentFramework::VISIBILITY_PRIVATE)"
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

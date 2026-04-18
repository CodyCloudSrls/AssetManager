{{-- See snipeit_modals.js for what powers this --}}
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h2 class="modal-title">{{ trans('admin/models/table.create') }}</h2>
        </div>
        <div class="modal-body">
            <form action="{{ route('api.models.store') }}" onsubmit="return false">
                <div class="alert alert-danger" id="modal_error_msg" style="display:none">
                </div>
                @include('modals.partials.name', ['required' => 'true'])
                <div class="dynamic-form-row">
                    <div class="col-md-4 col-xs-12"><label for="modal-company_id">{{ trans('general.company') }}:</label></div>
                    <div class="col-md-8 col-xs-12">
                        <x-input.select
                            name="company_id"
                            id="modal-company_id"
                            :options="\App\Models\Company::orderBy('name')->pluck('name', 'id')->prepend(trans('general.select_company'), '')->all()"
                            :selected="old('company_id')"
                            style="width:100%;"
                        />
                    </div>
                </div>
                <div class="dynamic-form-row">
                    <div class="col-md-4 col-xs-12"><label for="modal-visibility_type">{{ trans('general.template_visibility.label') }}:</label></div>
                    <div class="col-md-8 col-xs-12">
                        <x-input.select
                            name="visibility_type"
                            id="modal-visibility_type"
                            :options="\App\Models\AssetModel::visibilityOptions()"
                            :selected="old('visibility_type', \App\Models\AssetModel::VISIBILITY_PRIVATE)"
                            style="width:100%;"
                        />
                    </div>
                </div>
                @include('modals.partials.categories-select', ['required' => 'true'])
                @include('modals.partials.manufacturer-select')
                @include('modals.partials.model-number')
                @include('modals.partials.fieldset-select')
            </form>
        </div>
       @include('modals.partials.footer')
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

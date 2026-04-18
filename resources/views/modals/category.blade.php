{{-- See snipeit_modals.js for what powers this --}}
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
            <h2 class="modal-title">{{ trans('admin/categories/general.create') }}</h2>
        </div>
        <div class="modal-body">
            <form action="{{ route('api.categories.store') }}" onsubmit="return false">
                {{ csrf_field() }}
                <div class="alert alert-danger" id="modal_error_msg" style="display:none">
                </div>
                @include('partials.forms.edit.name', ['item' => new \App\Models\Category(), 'required' => 'true', 'translated_name' => trans('general.name')])
                <div class="dynamic-form-row">
                    @include('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => new \App\Models\Category()])
                </div>
                <div class="dynamic-form-row">
                    @include('partials.forms.edit.template-visibility-select', ['translated_name' => trans('general.template_visibility.label'), 'fieldname' => 'visibility_type', 'item' => new \App\Models\Category()])
                </div>
                <input type="hidden" name='category_type' id="modal-category_type" value="{{ request('category_type') }}" />
            </form>
        </div>
       @include('modals.partials.footer')
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

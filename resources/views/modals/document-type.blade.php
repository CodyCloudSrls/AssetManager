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
            </form>
        </div>
        <div class="dynamic-form-row">
            @include('modals.partials.footer')
        </div>
    </div>
</div>

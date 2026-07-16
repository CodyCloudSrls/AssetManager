{{-- See snipeit_modals.js for what powers this --}}
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h2 class="modal-title">{{ trans('admin/statuslabels/table.create') }}</h2>
        </div>
        <div class="modal-body">
            <form class="form-horizontal" action="{{ route('api.statuslabels.store') }}" onsubmit="return false">
                <div class="alert alert-danger" id="modal_error_msg" style="display:none">
                </div>
                <div class="dynamic-form-row">
                    @include('partials.forms.edit.name', ['required' => 'true', 'item' => new \App\Models\Statuslabel(),'translated_name' => trans('admin/statuslabels/table.name')  ])
                </div>
                <div class="dynamic-form-row">
                    @include('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => new \App\Models\Statuslabel()])
                </div>
                <div class="dynamic-form-row">
                    @include('partials.forms.edit.template-visibility-select', ['translated_name' => trans('general.template_visibility.label'), 'fieldname' => 'visibility_type', 'item' => new \App\Models\Statuslabel()])
                </div>

                <div class="dynamic-form-row">
                    <div class="form-group">
                        <label for="modal-type" class="col-md-3 control-label">{{ trans('admin/statuslabels/table.status_type') }}</label>
                        <div class="col-md-8 col-xs-12">
                            <x-input.select
                                name="type"
                                id="modal-type"
                                :options="$statuslabel_types"
                                required
                                style="width:100%;"
                            />
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="dynamic-form-row">
            @include('modals.partials.footer')
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

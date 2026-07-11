
@extends('layouts/edit-form', [
    'createText' => trans('admin/hardware/form.create'),
    'updateText' => trans('admin/hardware/form.update'),
    'topSubmit' => true,
    'helpText' => trans('help.assets'),
    'helpPosition' => 'right',
    'formAction' => ($item->id) ? route('hardware.update', $item) : route('hardware.store'),
    // Cancel target:
    //  • CREATE forwards the list filters as query params (via the "New" button), so we rebuild
    //    the filtered index from them.
    //  • EDIT has no filters in its URL (/hardware/{id}/edit), so request()->only() would be empty
    //    and Cancel would drop the user on the UNFILTERED list. Instead return to the referring
    //    list we came from (its filters live in the referrer's query string), guarded to a
    //    same-host hardware list URL so we never bounce back to an edit/create/view page.
    'index_route' => $item->id
        ? ((\Illuminate\Support\Str::startsWith(url()->previous(), url('/hardware'))
                && ! \Illuminate\Support\Str::contains(url()->previous(), ['/edit', '/create', '/clone']))
            ? url()->previous()
            : route('hardware.index'))
        : route('hardware.index', request()->only(['category_id', 'status_id', 'model_id', 'location_id', 'fieldset_id', 'cf_column', 'cf_value'])),
    'options' => [
                'back' => trans('admin/hardware/form.redirect_to_type',['type' => trans('general.previous_page')]),
                'index' => trans('admin/hardware/form.redirect_to_all_assets'),
                'item' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.asset')]),
                'other_redirect' => trans('admin/hardware/form.redirect_to_type', [ 'type' => trans('general.asset').' '.trans('general.asset_model')]),
               ]
])


{{-- Page content --}}
@section('inputFields')
    
    @include ('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id'])


  <!-- Asset Tag -->
    <div class="form-group {{ ($errors->has('asset_tag') || $errors->has('asset_tags.1')) ? ' has-error' : '' }}">
      <label for="asset_tag" class="col-md-3 control-label">{{ trans('admin/hardware/form.tag') }}</label>



      @if  ($item->id)
          <!-- we are editing an existing asset,  there will be only one asset tag -->
          <div class="col-md-7 col-sm-12">

          <input class="form-control" type="text" name="asset_tags[1]" id="asset_tag" value="{{ old('asset_tag', $item->asset_tag) }}" required>
              {!! $errors->first('asset_tags', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
              {!! $errors->first('asset_tag', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
          </div>
      @else
          <!-- we are creating a new asset - let people use more than one asset tag -->
          <div class="col-md-7 col-sm-12">
              <input class="form-control"
                     type="text" name="asset_tags[1]" id="asset_tag"
                     value="{{ old('asset_tags.1', \App\Models\Asset::autoincrement_asset()) }}" required>
              {!! $errors->first('asset_tags.1', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
              {!! $errors->first('asset_tag', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
          </div>
          <div class="col-md-2 col-sm-12">
              <button class="add_field_button btn btn-sm btn-theme" name="add_field_button">
                  <x-icon type="plus" />
                  <span class="sr-only">
                      {{ trans('general.new') }}
                  </span>
              </button>
          </div>
      @endif
  </div>

    @include ('partials.forms.edit.serial', ['fieldname'=> 'serials[1]', 'old_val_name' => 'serials.1', 'translated_serial' => trans('admin/hardware/form.serial')])

    <div class="input_fields_wrap">
        {{-- If we're back on this screen for an error, *and* we are doing 'create multiple', then... --}}
        @php
            $old_tags = old('asset_tags',[]);
            /**
            okay, so old() comes back as:
              (
                [1] => 1744410541
                [2] => 1744410542
              )
            */
            if(array_key_exists('1',$old_tags)) {
                unset($old_tags[1]); //we already handled 'asset_tag.1' - so unset it
            }
        @endphp
        @foreach(array_keys($old_tags) as $i)
            {{-- This is mostly stolen from the HTML that we add via javascript on the 'add_field_button' handler in the embedded JS below --}}
            {{--                @include ('partials.forms.edit.serial', ['fieldname'=> 'serials['.$loop->iteration.']', 'old_val_name' => 'serials.'.$loop->iteration, 'translated_serial' => trans('admin/hardware/form.serial')])--}}
            <span class="fields_wrapper">
                <div class="form-group {{  $errors->has('asset_tags.'.$i) ? ' has-error' : '' }}"><label for="asset_tag"
                                                                                                         class="col-md-3 control-label">{{ trans('admin/hardware/form.tag') }} {{ $i }}</label>
                    <div class="col-md-7 col-sm-12 required">
                        <input type="text" class="form-control" name="asset_tags[{{ $i }}]"
                               value="{{ old('asset_tags.'.$i) }}"
                               required>
              {!! $errors->first('asset_tags.'.$i, '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
                    </div>
                    <div class="col-md-2 col-sm-12">
                        <a href="#" class="remove_field btn btn-sm btn-theme"><x-icon type="minus"/></a>
                    </div>
                </div>
                @include ('partials.forms.edit.serial', ['fieldname'=> 'serials['.$i.']', 'old_val_name' => 'serials.'.$i, 'translated_serial' => trans('admin/hardware/form.serial')])
            </span>
        @endforeach
    </div>

    @include ('partials.forms.edit.model-select', ['translated_name' => trans('admin/hardware/form.model'), 'fieldname' => 'model_id', 'field_req' => true])


    @include ('partials.forms.edit.status', [ 'required' => 'true'])
    @if (!$item->id)
        @include ('partials.forms.checkout-selector', ['user_select' => 'true','asset_select' => 'true', 'location_select' => 'true', 'style' => 'display:none;'])
        @include ('partials.forms.edit.user-select', ['translated_name' => trans('admin/hardware/form.checkout_to'), 'fieldname' => 'assigned_user', 'style' => 'display:none;', 'required' => 'false'])
        @include ('partials.forms.edit.asset-select', ['translated_name' => trans('admin/hardware/form.checkout_to'), 'fieldname' => 'assigned_asset', 'style' => 'display:none;', 'required' => 'false'])
        @include ('partials.forms.edit.location-select', ['translated_name' => trans('admin/hardware/form.checkout_to'), 'fieldname' => 'assigned_location', 'style' => 'display:none;', 'required' => 'false'])
    @endif

    @include ('partials.forms.edit.notes')
    @include ('partials.forms.edit.location-select', ['translated_name' => trans('admin/hardware/form.default_location'), 'fieldname' => 'rtd_location_id', 'help_text' => trans('general.rtd_location_help')])
    @include ('partials.forms.edit.requestable', ['requestable_text' => trans('admin/hardware/general.requestable')])

    {{-- Cliente + contratto: for domains/IPs and other client-owned assets. The contract
         defaults to the model's default contract; the customer defaults to that contract. --}}
    <fieldset name="customer-links">
        <x-form.legend>{{ trans('admin/hardware/form.customer_section') }}</x-form.legend>

        {{-- "Bene aziendale (nostro)": nessun cliente/contratto. Spuntandolo azzera e blocca i due
             campi sotto. Stato iniziale derivato dal bene (spuntato se non ha cliente) — nessuna
             colonna nuova: in update un cliente vuoto resta vuoto, in create company_owned=1 evita
             l'ereditarietà del contratto di default del modello. --}}
        <div class="form-group">
            <label class="col-md-3 control-label">{{ trans('admin/hardware/form.company_owned') }}</label>
            <div class="col-md-7">
                <label style="font-weight:400; padding-top:7px;">
                    <input type="hidden" name="company_owned" value="0">
                    <input type="checkbox" id="cc_company_owned" name="company_owned" value="1"> {{ trans('admin/hardware/form.company_owned_help') }}
                </label>
            </div>
        </div>
        @push('js')
        <script>
        (function () {
            var chk = document.getElementById('cc_company_owned');
            var cust = document.getElementById('customer_id');
            var contr = document.getElementById('customer_contract_id');
            if (!chk || !cust) { return; }
            function lock(on) {
                [cust, contr].forEach(function (el) {
                    if (!el) { return; }
                    if (on) { el.value = ''; }
                    el.disabled = on;
                    if (window.jQuery && window.jQuery(el).data('select2')) {
                        window.jQuery(el).prop('disabled', on).trigger('change');
                    }
                });
            }
            function init() {
                // Derived initial state: an asset with no customer is treated as "ours".
                if (! cust.value) { chk.checked = true; }
                lock(chk.checked);
                chk.addEventListener('change', function () { lock(chk.checked); });
            }
            if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
        })();
        </script>
        @endpush

        <div class="form-group {{ $errors->has('customer_id') ? ' has-error' : '' }}">
            <label for="customer_id" class="col-md-3 control-label">{{ trans('admin/hardware/form.customer') }}</label>
            <div class="col-md-7">
                <select name="customer_id" id="customer_id" class="form-control select2" data-placeholder="{{ trans('admin/hardware/form.customer') }}" aria-label="customer_id" style="width:100%;">
                    <option value="">{{ trans('general.none') }}</option>
                    @foreach (\App\Models\Customer::orderBy('name')->get(['id', 'name']) as $cust)
                        <option value="{{ $cust->id }}" {{ (int) old('customer_id', $item->customer_id) === (int) $cust->id ? 'selected' : '' }}>{{ $cust->name }}</option>
                    @endforeach
                </select>
                <p class="help-block">{{ trans('admin/hardware/form.customer_help') }}</p>
                {!! $errors->first('customer_id', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
            </div>
        </div>

        <div class="form-group {{ $errors->has('customer_contract_id') ? ' has-error' : '' }}">
            <label for="customer_contract_id" class="col-md-3 control-label">{{ trans('admin/hardware/form.customer_contract') }}</label>
            <div class="col-md-7">
                <select name="customer_contract_id" id="customer_contract_id" class="form-control select2" data-placeholder="{{ trans('admin/hardware/form.customer_contract') }}" aria-label="customer_contract_id" style="width:100%;">
                    <option value="">{{ trans('admin/hardware/form.customer_contract_inherit') }}</option>
                    @foreach (\App\Models\CustomerContract::orderBy('name')->get(['id', 'name', 'contract_number']) as $ct)
                        <option value="{{ $ct->id }}" {{ (int) old('customer_contract_id', $item->customer_contract_id) === (int) $ct->id ? 'selected' : '' }}>{{ $ct->name }}{{ $ct->contract_number ? ' ('.$ct->contract_number.')' : '' }}</option>
                    @endforeach
                </select>
                <p class="help-block">{{ trans('admin/hardware/form.customer_contract_help') }}</p>
                {!! $errors->first('customer_contract_id', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
            </div>
        </div>

        {{-- Solo per i domini: collega l'Indirizzo IP per ereditarne lo stato Hetrix. Il campo
             appare SOLO quando il modello selezionato è nella categoria "Dominio" (id 77). Server-side
             per l'asset corrente, e via JS quando si cambia modello (mappa modello→dominio sotto). --}}
        @php
            $ccDomainCategoryName = 'Dominio';
            $ccIsDomain = $item->model && $item->model->category
                && mb_strtolower(trim($item->model->category->name)) === mb_strtolower($ccDomainCategoryName);
            $ccDomainModelIds = \App\Models\AssetModel::whereHas('category', fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($ccDomainCategoryName)]))
                ->pluck('id')->map(fn ($id) => (string) $id)->values();
        @endphp
        <div id="cc-linked-ip-wrap" @unless($ccIsDomain) style="display:none;" @endunless>
            @include('partials.forms.edit.asset-select', [
                'translated_name' => trans('admin/hardware/form.linked_ip'),
                'fieldname' => 'linked_ip_asset_id',
                'item' => $item,
                'asset_selector_div_id' => 'linked-ip-asset',
                'select_id' => 'linked_ip_asset_select',
                'required' => 'false',
            ])
            <div class="form-group" style="margin-top:-10px;">
                <div class="col-md-7 col-md-offset-3">
                    <p class="help-block">{{ trans('admin/hardware/form.linked_ip_help') }}</p>
                </div>
            </div>
        </div>
        @push('js')
        <script>
        (function () {
            var domainModels = @json($ccDomainModelIds);
            function modelSel() { return document.querySelector('[name="model_id"]'); }
            function wrap() { return document.getElementById('cc-linked-ip-wrap'); }
            function apply() {
                var m = modelSel(), w = wrap();
                if (!m || !w) { return; }
                var isDomain = domainModels.indexOf(String(m.value)) !== -1;
                w.style.display = isDomain ? '' : 'none';
                // If it stops being a domain, clear any stale IP link so it isn't saved.
                if (!isDomain) {
                    var sel = document.getElementById('linked_ip_asset_select');
                    if (sel && sel.value) { sel.value = ''; if (window.jQuery) { window.jQuery(sel).trigger('change'); } }
                }
            }
            function init() {
                var m = modelSel();
                if (m) {
                    m.addEventListener('change', apply);
                    if (window.jQuery) { window.jQuery(m).on('change', apply); }
                }
                apply();
            }
            if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
        })();
        </script>
        @endpush
    </fieldset>

    <fieldset name="nis-inventory-asset">
        <x-form.legend help_text="{{ trans('admin/hardware/form.nis_inventory_help') }}">
            {{ trans('admin/hardware/form.nis_inventory_section') }}
        </x-form.legend>

        <div class="form-group">
            <div class="col-md-7 col-md-offset-3">
                <label class="form-control">
                    <input type="hidden" name="nis_relevant" value="0">
                    <input type="checkbox" value="1" name="nis_relevant" {{ old('nis_relevant', $item->nis_relevant) ? ' checked="checked"' : '' }} aria-label="nis_relevant">
                    {{ trans('admin/hardware/form.nis_relevant') }}
                </label>
            </div>
        </div>

        <div class="form-group {{ $errors->has('nis_inventory_scope') ? ' has-error' : '' }}">
            <label for="nis_inventory_scope" class="col-md-3 control-label">{{ trans('admin/hardware/form.nis_inventory_scope') }}</label>
            <div class="col-md-4">
                <select class="form-control select2" name="nis_inventory_scope" id="nis_inventory_scope" aria-label="nis_inventory_scope">
                    <option value="">{{ trans('general.none') }}</option>
                    @foreach (\App\Models\Category::nisInventoryScopeOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('nis_inventory_scope', $item->nis_inventory_scope) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                {!! $errors->first('nis_inventory_scope', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
            </div>
        </div>

        <div class="form-group {{ $errors->has('nis_service_impact') ? ' has-error' : '' }}">
            <label for="nis_service_impact" class="col-md-3 control-label">{{ trans('admin/hardware/form.nis_service_impact') }}</label>
            <div class="col-md-4">
                <select class="form-control select2" name="nis_service_impact" id="nis_service_impact" aria-label="nis_service_impact">
                    @foreach (\App\Models\Asset::nisServiceImpactOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('nis_service_impact', $item->nis_service_impact ?: 'unknown') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                {!! $errors->first('nis_service_impact', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
            </div>
        </div>

        <div class="form-group {{ $errors->has('nis_notes') ? ' has-error' : '' }}">
            <label for="nis_notes" class="col-md-3 control-label">{{ trans('admin/hardware/form.nis_notes') }}</label>
            <div class="col-md-7">
                <textarea class="form-control" name="nis_notes" id="nis_notes" rows="3">{{ old('nis_notes', $item->nis_notes) }}</textarea>
                {!! $errors->first('nis_notes', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
            </div>
        </div>

        <div class="form-group {{ $errors->has('tenant_service_ids') ? ' has-error' : '' }}">
            <label for="tenant_service_ids" class="col-md-3 control-label">{{ trans('admin/hardware/form.nis_tenant_services') }}</label>
            <div class="col-md-7">
                @php
                    $selectedAssetTenantServiceIds = array_map('intval', old('tenant_service_ids', ($item->exists ? $item->tenantServices->pluck('id')->all() : [])));
                @endphp
                <input type="hidden" name="tenant_service_ids_present" value="1">
                <select class="js-data-ajax" data-endpoint="tenantservices" data-placeholder="{{ trans('admin/hardware/form.nis_tenant_services') }}" multiple name="tenant_service_ids[]" id="tenant_service_ids" aria-label="tenant_service_ids" style="width: 100%" data-company-id="{{ old('company_id', $item->company_id ?? '') }}">
                    @foreach (\App\Models\TenantService::whereIn('id', $selectedAssetTenantServiceIds)->get() as $svc)
                        <option value="{{ $svc->id }}" selected>{{ $svc->name }}</option>
                    @endforeach
                </select>
                <p class="help-block">{{ trans('admin/hardware/form.nis_tenant_services_help') }}</p>
                {!! $errors->first('tenant_service_ids', '<span class="alert-msg"><i class="fas fa-times"></i> :message</span>') !!}
            </div>
        </div>
    </fieldset>

    {{-- NIS2 relevance/scope are governed by the asset's category: when the selected model's
         category is flagged for the NIS2 inventory, "relevant" and "scope" are inherited and
         locked (grey). The controller enforces the same rule server-side. --}}
    @php
        $ccModelNis = \App\Models\AssetModel::whereHas('category', fn ($q) => $q->where('nis_inventory_required', true))
            ->with('category:id,name,nis_inventory_scope')
            ->get()
            ->mapWithKeys(fn ($m) => [(string) $m->id => ['scope' => (string) $m->category->nis_inventory_scope, 'category' => (string) $m->category->name]]);
    @endphp
    @push('js')
    <script>
    (function () {
        var ccModelNis = @json($ccModelNis);
        var lockedTpl = @json(trans('admin/hardware/form.nis_from_category'));

        function modelSelect() { return document.querySelector('[name="model_id"]'); }
        function relevantChk() { return document.querySelector('input[type="checkbox"][name="nis_relevant"]'); }
        function scopeSelect() { return document.getElementById('nis_inventory_scope'); }

        function setDisabled(el, on) {
            el.disabled = on;
            if (window.jQuery && window.jQuery(el).data('select2')) { window.jQuery(el).prop('disabled', on); }
        }

        function ensureNote(scope) {
            var note = document.getElementById('cc-nis-lock-note');
            if (!note) {
                note = document.createElement('p');
                note.id = 'cc-nis-lock-note';
                note.className = 'help-block';
                note.style.display = 'none';
                (scope.parentNode || scope).appendChild(note);
            }
            return note;
        }

        function applyLock(setValues) {
            var model = modelSelect(), chk = relevantChk(), scope = scopeSelect();
            if (!model || !chk || !scope) return;
            var gov = ccModelNis[String(model.value)];
            var note = ensureNote(scope);
            if (gov) {
                if (setValues) {
                    if (!chk.checked) { chk.checked = true; }
                    if (String(scope.value) !== String(gov.scope)) {
                        scope.value = String(gov.scope);
                        if (window.jQuery) { window.jQuery(scope).trigger('change'); }
                    }
                }
                setDisabled(chk, true);
                setDisabled(scope, true);
                if (scope.style.background !== 'rgb(238, 238, 238)') { scope.style.background = '#eee'; }
                var msg = lockedTpl.replace(':name', gov.category || '');
                if (note.textContent !== msg) { note.textContent = msg; }
                if (note.style.display !== '') { note.style.display = ''; }
            } else {
                setDisabled(chk, false);
                setDisabled(scope, false);
                if (scope.style.background !== '') { scope.style.background = ''; }
                if (note.style.display !== 'none') { note.style.display = 'none'; }
            }
        }

        function init() {
            applyLock(true);
            var model = modelSelect();
            if (model) {
                model.addEventListener('change', function () { applyLock(true); });
                if (window.jQuery) { window.jQuery(model).on('change', function () { applyLock(true); }); }
            }
        }
        if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
    })();
    </script>
    @endpush



    @include ('partials.forms.edit.image-upload', ['image_path' => app('assets_upload_path')])


    <div id='custom_fields_content'>
        <!-- Custom Fields -->
        @if ($item->model && $item->model->fieldset)
        <?php $model = $item->model; ?>
        @endif
        @if (old('model_id'))
            @php
                $model = \App\Models\AssetModel::find(old('model_id'));
            @endphp
        @elseif (isset($selected_model))
            @php
                $model = $selected_model;
            @endphp
        @endif
        @if (isset($model) && $model)
        @include("models/custom_fields_form",["model" => $model])
        @endif
    </div>


    <div class="col-md-12 col-sm-12">

        <fieldset name="optional-details">

            <x-form.legend>
                <a id="optional_info">
                    <x-icon type="caret-right" class="fa-fw" id="optional_info_icon" />
                    {{ trans('admin/hardware/form.optional_infos') }}
                </a>
            </x-form.legend>

            <div id="optional_details" class="col-md-12" style="display:none">
                @include ('partials.forms.edit.name', ['translated_name' => trans('admin/hardware/form.name')])
                @include ('partials.forms.edit.warranty')
                @include ('partials.forms.edit.datepicker', ['translated_name' => trans('admin/hardware/form.expected_checkin'),'fieldname' => 'expected_checkin'])
                @include ('partials.forms.edit.datepicker', ['translated_name' => trans('general.next_audit_date'),'fieldname' => 'next_audit_date', 'help_text' => trans('general.next_audit_date_help')])
                <!-- byod checkbox -->
                <div class="form-group byod">
                    <div class="col-md-7 col-md-offset-3">
                        <label class="form-control">
                            <input type="checkbox" value="1" name="byod" {{ (old('remote', $item->byod)) == '1' ? ' checked="checked"' : '' }} aria-label="byod">
                            {{ trans('general.byod') }}
                        </label>
                        <p class="help-block">
                            {{ trans('general.byod_help') }}
                        </p>
                    </div>
                </div>

            </div> <!-- end optional details -->
        </fieldset>

    </div><!-- end col-md-12 col-sm-12-->



    <div class="col-md-12 col-sm-12">
        <fieldset name="order-info">
            {{-- Whole section (rinnovi + ordine + fornitore + costo) is ONE collapsible unit,
                 EXPANDED by default so Fornitore/Data acquisto/Costo are visible without hunting
                 for a caret (era il caso del "fornitore mancante sui domini": stava nascosto qui).
                 Il cookie order_info_open ricorda la scelta dell'utente. --}}
            <x-form.legend>
                <a id="order_info">
                    <x-icon type="caret-down" class="fa-fw" id="order_info_icon" />
                    {{ trans('admin/hardware/form.order_details') }}
                </a>
            </x-form.legend>

            <div id='order_details' class="col-md-12">
                {{-- Scadenze / rinnovi (per asset virtuali: domini, IP, monitoraggio, certificati). --}}
                @include ('partials.forms.edit.datepicker', ['translated_name' => trans('admin/hardware/form.renewal_date'),'fieldname' => 'renewal_date', 'help_text' => trans('admin/hardware/form.renewal_date_help')])
                <div class="form-group {{ $errors->has('auto_renewal') ? ' has-error' : '' }}">
                    <label for="auto_renewal" class="col-md-3 control-label">{{ trans('admin/hardware/form.auto_renewal') }}</label>
                    <div class="col-md-7">
                        <label style="font-weight:400; margin:0; padding-top:7px;">
                            <input type="hidden" name="auto_renewal" value="0">
                            <input type="checkbox" id="auto_renewal" name="auto_renewal" value="1" {{ old('auto_renewal', $item->auto_renewal) ? 'checked' : '' }}> {{ trans('admin/hardware/form.auto_renewal_help') }}
                        </label>
                        {!! $errors->first('auto_renewal', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                @include ('partials.forms.edit.order_number')
                @include ('partials.forms.edit.datepicker', ['translated_name' => trans('general.purchase_date'),'fieldname' => 'purchase_date'])
                @include ('partials.forms.edit.datepicker', ['translated_name' => trans('admin/hardware/form.eol_date'),'fieldname' => 'asset_eol_date'])
                @include ('partials.forms.edit.supplier-select', ['translated_name' => trans('general.supplier'), 'fieldname' => 'supplier_id'])

                @php
                    $currency_type = null;
                    if ($item->id && $item->location) {
                        $currency_type = $item->location->currency;
                    }
                @endphp

                @include ('partials.forms.edit.purchase_cost', ['currency_type' => $currency_type])

            </div> <!-- end order details -->
        </fieldset>
    </div><!-- end col-md-12 col-sm-12-->
   
@stop

@section('moar_scripts')



<script nonce="{{ csrf_token() }}">

    $(function () {
        // Make the "Cliente" select clearable (X) so a customer can be removed from the asset
        // (plain .select2 has no allowClear). Re-inits over snipeit.js's default init.
        $('#customer_id').select2({ allowClear: true, placeholder: '{{ trans('general.none') }}', width: '100%' });

        // The select2 AJAX reads the company via jQuery's .data('company-id') cache, so
        // updating only the attribute isn't enough — set BOTH the attribute and .data().
        function scopeServicesToCompany(companyId) {
            $('#tenant_service_ids')
                .attr('data-company-id', companyId || '')
                .data('company-id', companyId || '');
        }

        // On load: scope to the company already selected on the form (e.g. the default
        // company on the CREATE form) so the tenant-services list isn't empty until the
        // company is re-picked.
        scopeServicesToCompany($('select[name="company_id"]').val());

        // Re-scope the linked NIS2 tenant services when the asset's company changes.
        $(document).on('change', 'select[name="company_id"]', function () {
            scopeServicesToCompany($(this).val());
            $('#tenant_service_ids').val(null).trigger('change');
        });
    });

    @if(Request::has('model_id'))
        //TODO: Refactor custom fields to use Livewire, populate from server on page load when requested with model_id
    $(document).ready(function() {
        fetchCustomFields()
    });
    @endif

    var transformed_oldvals={};

    function fetchCustomFields() {
        //save custom field choices
        var oldvals = $('#custom_fields_content').find('input,select,textarea').serializeArray();
        for(var i in oldvals) {
            transformed_oldvals[oldvals[i].name]=oldvals[i].value;
        }

        var modelid = $('#model_select_id').val();
        if (modelid == '') {
            $('#custom_fields_content').html("");
        } else {

            $.ajax({
                type: 'GET',
                url: "{{ config('app.url') }}/models/" + modelid + "/custom_fields",
                headers: {
                    "X-Requested-With": 'XMLHttpRequest',
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                },
                _token: "{{ csrf_token() }}",
                dataType: 'html',
                success: function (data) {
                    $('#custom_fields_content').html(data);
                    $('#custom_fields_content select').select2(); //enable select2 on any custom fields that are select-boxes
                    //now re-populate the custom fields based on the previously saved values
                    $('#custom_fields_content').find('input,select,textarea').each(function (index,elem) {
                        if(transformed_oldvals[elem.name]) {
                            if (elem.type === 'checkbox' || elem.type === 'radio'){
                                let shouldBeChecked = oldvals.find(oldValElement => {
                                    return oldValElement.name === elem.name && oldValElement.value === $(elem).val();
                                });

                                if (shouldBeChecked){
                                    $(elem).prop('checked', true);
                                }

                                return;
                            }
                             {{-- If there already *is* is a previously-input 'transformed_oldvals' handy,
                                  overwrite with that previously-input value *IF* this is an edit of an existing item *OR*
                                  if there is no new default custom field value coming from the model --}}
                            if({{ $item->id ? 'true' : 'false' }} || $(elem).val() == '') {
                                $(elem).val(transformed_oldvals[elem.name]).trigger('change'); //the trigger is for select2-based objects, if we have any
                            }
                        }

                    });
                }
            });
        }
    }

    function user_add(status_id) {

        if (status_id != '') {
            $(".status_spinner").css("display", "inline");
            $.ajax({
                url: "{{config('app.url') }}/api/v1/statuslabels/" + status_id + "/deployable",
                headers: {
                    "X-Requested-With": 'XMLHttpRequest',
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data) {
                    $(".status_spinner").css("display", "none");
                    $("#selected_status_status").fadeIn();

                    if (data == true) {
                        $("#assignto_selector").show();
                        $("#assigned_user").show();

                        $("#selected_status_status").removeClass('text-danger');
                        $("#selected_status_status").addClass('text-success');
                        $("#selected_status_status").html('<x-icon type="checkmark" /> {{ trans_choice('admin/hardware/form.asset_deployable', 1)}}');


                    } else {
                        $("#assignto_selector").hide();
                        $("#selected_status_status").removeClass('text-success');
                        $("#selected_status_status").addClass('text-danger');
                        $("#selected_status_status").html('<x-icon type="warning" /> {{ (($item->assigned_to!='') && ($item->assigned_type!='') && ($item->deleted_at == '')) ? trans('admin/hardware/form.asset_not_deployable_checkin') : trans('admin/hardware/form.asset_not_deployable')  }} ');
                    }
                }
            });
        }
    }


    $(function () {
        //grab custom fields for this model whenever model changes.
        $('#model_select_id').on("change", fetchCustomFields);

        //initialize assigned user/loc/asset based on statuslabel's statustype
        user_add($(".status_id option:selected").val());

        //whenever statuslabel changes, update assigned user/loc/asset
        $(".status_id").on("change", function () {
            user_add($(".status_id").val());
        });

    });


    // Add another asset tag + serial combination if the plus sign is clicked
    $(document).ready(function() {

        var max_fields      = 100; //maximum input boxes allowed
        var wrapper         = $(".input_fields_wrap"); //Fields wrapper
        var add_button      = $(".add_field_button"); //Add button ID
        var x = {{ old('asset_tags') ? count(old('asset_tags')) : 1  /* If we have old() data, use that to determine the 'next' number for 'Asset Tag 2' etc. Otherwise, use 1 */ }}; //initial text box count




        $(add_button).click(function(e){ //on add input button click

            e.preventDefault();

            var auto_tag = $("#asset_tag").val().replace(/^{{ preg_quote(App\Models\Setting::getSettings()->auto_increment_prefix, '/') }}/g, '');
            var box_html        = '';
			const zeroPad 		= (num, places) => String(num).padStart(places, '0');

            // Check that we haven't exceeded the max number of asset fields
            if (x < max_fields) {

                if (auto_tag!='') {
                     auto_tag = zeroPad(parseInt(auto_tag) + parseInt(x),auto_tag.length);
                } else {
                     auto_tag = '';
                }

                x++; //text box increment

                // NOTE - this is duplicated in the blade itself in order to re-display an attempt to insert multiple assets
                // So if this changes, that needs to change too.
                box_html += '<span class="fields_wrapper">';
                box_html += '<div class="form-group"><label for="asset_tag" class="col-md-3 control-label">{{ trans('admin/hardware/form.tag') }} ' + x + '</label>';
                box_html += '<div class="col-md-7 col-sm-12 required">';
                box_html += '<input type="text"  class="form-control" name="asset_tags[' + x + ']" value="{{ (($snipeSettings->auto_increment_prefix!='') && ($snipeSettings->auto_increment_assets=='1')) ? $snipeSettings->auto_increment_prefix : '' }}'+ auto_tag +'" required>';
                box_html += '</div>';
                box_html += '<div class="col-md-2 col-sm-12">';
                box_html += '<a href="#" class="remove_field btn btn-sm btn-theme"><x-icon type="minus" /></a>';
                box_html += '</div>';
                // box_html += '</div>';
                box_html += '</div>';
                box_html += '<div class="form-group"><label for="serial" class="col-md-3 control-label">{{ trans('admin/hardware/form.serial') }} ' + x + '</label>';
                box_html += '<div class="col-md-7 col-sm-12">';
                box_html += '<input type="text"  class="form-control" name="serials[' + x + ']">';
                box_html += '</div>';
                box_html += '</div>';
                box_html += '</span>';
                $(wrapper).append(box_html);

            // We have reached the maximum number of extra asset fields, so disable the button
            } else {
                $(".add_field_button").attr('disabled');
                $(".add_field_button").addClass('disabled');
            }
        });

        $(wrapper).on("click",".remove_field", function(e){ //user clicks on remove text
            $(".add_field_button").removeAttr('disabled');
            $(".add_field_button").removeClass('disabled');
            e.preventDefault();
            //console.log(x);

            $(this).parent('div').parent('div').parent('span').remove();
            x--;
        });


        $('.expand').click(function(){
            id = $(this).attr('id');
            fields = $(this).text();
            if (txt == '+'){
                $(this).text('-');
            }
            else{
                $(this).text('+');
            }
            $("#"+id).toggle();

        });

        {{-- TODO: Clean up some of the duplication in here. Not too high of a priority since we only copied it once. --}}
        $("#optional_info").on("click",function(){
            $('#optional_details').fadeToggle(100);
            $('#optional_info_icon').toggleClass('fa-caret-right fa-caret-down');
            var optional_info_open = $('#optional_info_icon').hasClass('fa-caret-down');
            document.cookie = "optional_info_open="+optional_info_open+'; path=/';
        });

        $("#order_info").on("click",function(){
            $('#order_details').fadeToggle(100);
            $("#order_info_icon").toggleClass('fa-caret-right fa-caret-down');
            var order_info_open = $('#order_info_icon').hasClass('fa-caret-down');
            document.cookie = "order_info_open="+order_info_open+'; path=/';
        });

        var all_cookies = document.cookie.split(';')
        for(var i in all_cookies) {
            var trimmed_cookie = all_cookies[i].trim(' ')
            if (trimmed_cookie.startsWith('optional_info_open=')) {
                elems = all_cookies[i].split('=', 2)
                if (elems[1] == 'true') {
                    $('#optional_info').trigger('click')
                }
            }
            if (trimmed_cookie.startsWith('order_info_open=')) {
                elems = all_cookies[i].split('=', 2)
                // #order_details now defaults to OPEN, so only collapse it if the user
                // explicitly closed it before (cookie == 'false').
                if (elems[1] == 'false') {
                    $('#order_info').trigger('click')
                }
            }
        }

    });




</script>
@stop

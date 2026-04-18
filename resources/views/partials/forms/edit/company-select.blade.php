<!-- Company -->
@if (!\App\Models\Company::canCurrentUserSelectCompany())
    <div class="form-group">
        <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>
        <div class="col-md-6">
            @php
                $lockedCompanyId = old($fieldname, (isset($item) && $item->{$fieldname}) ? $item->{$fieldname} : (\App\Models\Company::preferredCompanySelectionId() ?? auth()->user()?->company_id));
            @endphp
            <input type="hidden" name="{{ $fieldname }}" value="{{ $lockedCompanyId }}">
            <select class="js-data-ajax" disabled data-endpoint="companies"
                    data-placeholder="{{ trans('general.select_company') }}" name="{{ $fieldname }}" style="width: 100%"
                    aria-label="{{ $fieldname }}"{{ (isset($multiple) && ($multiple=='true')) ? " multiple='multiple'" : '' }}>
                @if ($company_id = $lockedCompanyId)
                    <option value="{{ $company_id }}" selected="selected" role="option" aria-selected="true"  role="option">
                        {{ (\App\Models\Company::find($company_id)) ? \App\Models\Company::find($company_id)->name : '' }}
                    </option>
                @else
                    {!! (!isset($multiple) || ($multiple=='false')) ? '<option value="" role="option">'.trans('general.select_company').'</option>' : ''  !!}
                @endif
            </select>
        </div>
    </div>

@else
    <div id="{{ $fieldname }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}">
        <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>
        <div class="col-md-8">
            <select class="js-data-ajax" data-endpoint="companies" data-placeholder="{{ trans('general.select_company') }}" name="{{ $fieldname }}" style="width: 100%"{{ (isset($multiple) && ($multiple=='true')) ? " multiple='multiple'" : '' }}>
                @isset ($selected)
                    @foreach ($selected as $company_id)
                        <option value="{{ $company_id }}" selected="selected" role="option" aria-selected="true">
                            {{ \App\Models\Company::find($company_id)->name }}
                        </option>
                    @endforeach
                @endisset
                @if ($company_id = old($fieldname, (isset($item) && $item->{$fieldname}) ? $item->{$fieldname} : (\App\Models\Company::preferredCompanySelectionId() ?? '')))
                    <option value="{{ $company_id }}" selected="selected">
                        {{ (\App\Models\Company::find($company_id)) ? \App\Models\Company::find($company_id)->name : '' }}
                    </option>
                @else
                    {!! (!isset($multiple) || ($multiple=='false')) ? '<option value="" role="option">'.trans('general.select_company').'</option>' : ''  !!}
                @endif
            </select>
        </div>
        {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
    </div>
@endif

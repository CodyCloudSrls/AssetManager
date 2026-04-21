<div id="{{ $wrapper_id ?? 'assigned_user' }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}"{!!  (isset($style)) ? ' style="'.e($style).'"' : ''  !!}>
    @php
        $userSelectId = $select_id ?? 'assigned_user_select';
        $selectedUserId = old($fieldname, $selected_id ?? ((isset($item) && isset($item->{$fieldname})) ? $item->{$fieldname} : ''));
    @endphp

    <label for="{{ $fieldname }}" class="col-md-3 control-label">{{ $translated_name }}</label>

    <div class="col-md-7">
        <select class="js-data-ajax" data-endpoint="users" data-placeholder="{{ trans('general.select_user') }}" name="{{ $fieldname }}" style="width: 100%" id="{{ $userSelectId }}" aria-label="{{ $fieldname }}"{{ !empty($company_id) ? ' data-company-id="'.e($company_id).'"' : '' }}{{  ((isset($required)) && ($required=='true')) ? ' required' : '' }}>
            <option value="" role="option">{{ trans('general.select_user') }}</option>
            @if ($user_id = $selectedUserId)
                <option value="{{ $user_id }}" selected="selected" role="option" aria-selected="true"  role="option">
                    {{ (\App\Models\User::find($user_id)) ? \App\Models\User::find($user_id)->present()->fullName : '' }}
                </option>
            @endif
        </select>
    </div>

    <div class="col-md-1 col-sm-1 text-left">
        @can('create', \App\Models\User::class)
            @if ((!isset($hide_new)) || ($hide_new!='true'))
                <a href='{{ route('modal.show', 'user') }}' data-toggle="modal"  data-target="#createModal" data-select='{{ $userSelectId }}' class="btn btn-sm btn-theme">{{ trans('button.new') }}</a>
            @endif
        @endcan
    </div>

    {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}

</div>

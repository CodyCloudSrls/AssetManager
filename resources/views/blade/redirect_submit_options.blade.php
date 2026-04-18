<!-- begin redirect submit options -->
@props([
    'index_route',
    'button_label',
    'disabled_select' => false,
    'options' => [],
    'id' => 'submit_button',
])

<div class="box-footer">
    <div class="row">

        <div class="col-md-3">
            @php
                $cancelUrl = url()->previous();

                if ($index_route) {
                    $cancelUrl = str_contains($index_route, '/')
                        ? $index_route
                        : route($index_route);
                }
            @endphp

            <a class="btn btn-link" href="{{ $cancelUrl }}">{{ trans('button.cancel') }}</a>
        </div>

        <div class="col-md-9 text-right">
            <div class="btn-group text-left">

                @if (($options) && (count($options) > 0))
                <select class="redirect-options form-control select2" data-minimum-results-for-search="Infinity" name="redirect_option" style="min-width: 250px"{{ ($disabled_select ? ' disabled' : '') }}>
                    @foreach ($options as $key => $value)
                        <option value="{{ $key }}"{{ session('redirect_option') == $key ? ' selected' : ''}}>
                            {{ $value }}
                        </option>
                    @endforeach
                </select>
                @endif

                <button type="submit" id="{{ $id }}" class="btn btn-success pull-right{{ ($disabled_select ? ' disabled' : '') }}" style="margin-left:5px; border-radius: 3px;"{!! ($disabled_select ? ' data-tooltip="true" title="'.trans('admin/hardware/general.edit').'" disabled' : '') !!}>
                    <x-icon type="checkmark" />
                    {{ $button_label }}
                </button>

            </div><!-- /.btn-group -->
        </div><!-- /.col-md-9 -->
    </div><!-- /.row -->
</div> <!-- /.box-footer -->
<!-- end redirect submit options -->

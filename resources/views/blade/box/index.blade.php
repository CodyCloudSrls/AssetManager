@props([
    'box_style' => 'default',
    'header' => false,
    'footer' => false,
])
@aware(['name', 'route'])


<!-- Start box component -->
<div {{ $attributes->merge(['class' => 'box box-'.$box_style]) }}>

    @if ($header)
        <div class="box-header with-border">
            <h2 class="box-title">
                {{ $header }}
            </h2>
        </div>
    @endif

    <div class="box-body">

        @if (isset($table_header))
            <h3 class="box-title{{ (!isset($bulkactions)) ? ' pull-left' : '' }}">
                {{ $table_header }}
            </h3>
        @endif


        @if (isset($bulkactions))
            <div id="{{ Illuminate\Support\Str::camel($name) }}Toolbar" class="pull-left" style="min-width:0; padding-top: 10px; margin-right: 8px;">
                {{ $bulkactions }}
            </div>
        @endif

            @if (($slot) && (!$slot->isEmpty()))
                {{ $slot }}
            @endif

    </div>

    @if ($route)
        <x-box.footer />
    @endif
</div>

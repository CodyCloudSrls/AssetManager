@extends('layouts/default')

@section('title')
    {{ trans('admin/hardware/general.overview_title') }}
    @parent
@stop

@section('content')
    @php($ccColors = ['bg-aqua', 'bg-green', 'bg-yellow', 'bg-red', 'bg-blue', 'bg-navy', 'bg-teal', 'bg-olive', 'bg-purple', 'bg-maroon'])

    <style>
        /* Whole card is one clickable link; the box keeps a fixed size so the barcode-icon
           zoom on hover stays clipped inside and never shifts the neighbouring cards. */
        .cc-overview-card { display:block; overflow:hidden; }
        .cc-overview-card:hover, .cc-overview-card:focus { text-decoration:none; }
        .cc-overview-card .small-box-footer { pointer-events:none; }
    </style>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ trans('admin/hardware/general.overview_title') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ route('hardware.index') }}" class="btn btn-default btn-sm">{{ trans('admin/hardware/general.overview_full_list') }}</a>
                    </div>
                </div>
                <div class="box-body">
                    <p class="help-block">{{ trans('admin/hardware/general.overview_intro') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────── Categorie ─────────── --}}
    <h3 style="margin:6px 4px 10px;">{{ trans('general.categories') }}</h3>
    <div class="row">
        @forelse ($categories as $i => $cat)
            <div class="col-lg-3 col-sm-6 col-xs-12">
                <a href="{{ route('hardware.index', ['category_id' => $cat->id]) }}" class="small-box cc-overview-card {{ $ccColors[$i % count($ccColors)] }}">
                    <div class="inner">
                        <h3>{{ number_format($cat->assets_count) }}</h3>
                        <p>{{ $cat->name }}</p>
                    </div>
                    <div class="icon"><x-icon type="assets" /></div>
                    <span class="small-box-footer">
                        {{ trans('admin/hardware/general.overview_view_assets') }} <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>
                    </span>
                </a>
            </div>
        @empty
            <div class="col-md-12"><p class="text-muted">{{ trans('admin/hardware/general.overview_empty') }}</p></div>
        @endforelse
    </div>

    {{-- ─────────── Campi (fieldset) ─────────── --}}
    @if ($fieldsets->isNotEmpty())
        <hr>
        <h3 style="margin:6px 4px 10px;">{{ trans('admin/hardware/general.overview_fields') }}</h3>
        <p class="help-block" style="margin:0 4px 10px;">{{ trans('admin/hardware/general.overview_fields_help') }}</p>
        <div class="row">
            @foreach ($fieldsets as $i => $row)
                <div class="col-lg-3 col-sm-6 col-xs-12">
                    <a href="{{ route('hardware.index', ['fieldset_id' => $row->fieldset->id]) }}" class="small-box cc-overview-card {{ $ccColors[($i + 3) % count($ccColors)] }}">
                        <div class="inner">
                            <h3>{{ number_format($row->count) }}</h3>
                            <p>{{ $row->fieldset->name }}</p>
                        </div>
                        <div class="icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></div>
                        <span class="small-box-footer">
                            {{ trans('admin/hardware/general.overview_view_assets') }} <i class="fa fa-arrow-circle-right" aria-hidden="true"></i>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
@stop

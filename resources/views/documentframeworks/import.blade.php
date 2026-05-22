@extends('layouts/default')

@section('title')
    {{ trans('admin/documentframeworks/general.import') }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1 col-sm-12 col-sm-offset-0">
            <form class="form-horizontal" method="post" action="{{ route('documentframeworks.import.store') }}" enctype="multipart/form-data" role="form">
                {{ csrf_field() }}

                <div class="box box-default">
                    <div class="box-header with-border">
                        <h2 class="box-title">{{ trans('admin/documentframeworks/general.import') }}</h2>
                    </div>

                    <div class="box-body">
                        <fieldset name="document-framework-import">
                            <x-form.legend>{{ trans('admin/documentframeworks/general.import_section') }}</x-form.legend>

                            <div class="form-group {{ $errors->has('file') ? ' has-error' : '' }}">
                                <label for="file" class="col-md-3 control-label">{{ trans('admin/documentframeworks/general.import_file') }}</label>
                                <div class="col-md-6">
                                    <input class="form-control" type="file" name="file" id="file" accept=".csv,.tsv,.txt,.xlsx,.docx">
                                    {!! $errors->first('file', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                                </div>
                            </div>

                            @include('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $item])
                            @include('partials.forms.edit.template-visibility-select', [
                                'translated_name' => trans('general.template_visibility.label'),
                                'fieldname' => 'visibility_type',
                                'item' => $item,
                                'visibilityOptions' => $visibilityOptions,
                                'defaultVisibility' => \App\Models\DocumentFramework::VISIBILITY_PRIVATE,
                            ])
                        </fieldset>
                    </div>

                    <div class="box-footer text-right">
                        <a class="btn btn-link" href="{{ route('documentframeworks.index') }}">{{ trans('button.cancel') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-file-import" aria-hidden="true"></i>
                            {{ trans('admin/documentframeworks/general.import') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

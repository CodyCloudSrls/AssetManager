@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_delete') }} {{ trans('general.licenses') }}
    @parent
@stop

@section('header_right')
    <a href="{{ URL::previous() }}" class="btn btn-sm btn-theme pull-right">{{ trans('general.back') }}</a>
@stop

@section('content')
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <form method="post" action="{{ route('licenses.bulkdelete') }}" autocomplete="off" role="form">
                @csrf
                @foreach ($licenses as $license)
                    <input type="hidden" name="ids[]" value="{{ $license->id }}">
                @endforeach

                <div class="box box-danger">
                    <div class="box-header with-border"><h2 class="box-title">{{ trans('general.bulk_delete') }} {{ trans('general.licenses') }}</h2></div>
                    <div class="box-body">
                        <div class="callout callout-warning">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ trans('admin/licenses/bulk.delete_warn', ['count' => $licenses->count()]) }}
                            @if ($valid_count < $licenses->count())
                                <br>{{ trans('admin/licenses/bulk.delete_skip_note', ['skipped' => $licenses->count() - $valid_count]) }}
                            @endif
                        </div>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('general.name') }}</th>
                                    <th>{{ trans('admin/licenses/form.to_email') }}</th>
                                    <th class="text-center">{{ trans('admin/licenses/form.seats') }}</th>
                                    <th class="text-center">{{ trans('admin/licenses/bulk.assigned') }}</th>
                                    <th>{{ trans('general.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($licenses as $license)
                                    @php($ccBlocked = (int) $license->assigned_seats_count !== 0)
                                    <tr @class(['text-muted' => $ccBlocked])>
                                        <td>{{ $license->name }}</td>
                                        <td>{{ $license->license_email }}</td>
                                        <td class="text-center">{{ $license->seats }}</td>
                                        <td class="text-center">{{ $license->assigned_seats_count }}</td>
                                        <td>
                                            @if ($ccBlocked)
                                                <span class="label label-warning">{{ trans('admin/licenses/bulk.will_skip') }}</span>
                                            @else
                                                <span class="label label-danger">{{ trans('admin/licenses/bulk.will_delete') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer text-right">
                        <a href="{{ URL::previous() }}" class="btn btn-link">{{ trans('button.cancel') }}</a>
                        <button type="submit" class="btn btn-danger" @disabled($valid_count === 0)>
                            <x-icon type="delete"/> {{ trans('admin/licenses/bulk.confirm_delete', ['count' => $valid_count]) }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

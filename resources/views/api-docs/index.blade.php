@extends('layouts/default')

@section('title')
    {{ trans('general.api_docs') ?? 'API' }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">CodyCloud Asset — API</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ route('api-docs.spec') }}" class="btn btn-default btn-sm" download="openapi.yaml">openapi.yaml</a>
                    </div>
                </div>
                <div class="box-body">
                    <div id="swagger-ui"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Swagger UI is loaded from a CDN for this gated, superadmin-only page.
         To remove the external dependency, vendor swagger-ui-dist into /public. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.addEventListener('load', function () {
            if (typeof SwaggerUIBundle === 'undefined') { return; }
            window.ui = SwaggerUIBundle({
                url: '{{ route('api-docs.spec') }}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [SwaggerUIBundle.presets.apis],
                layout: 'BaseLayout',
            });
        });
    </script>
@stop

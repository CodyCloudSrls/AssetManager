<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the OpenAPI specification and an interactive Swagger UI for the API.
 * Restricted to superadmins — the spec describes admin-level write endpoints.
 */
class ApiDocsController extends Controller
{
    private function authorizeSuperuser(): void
    {
        abort_unless(auth()->user()?->isSuperUser(), 403);
    }

    public function index(): View
    {
        $this->authorizeSuperuser();

        return view('api-docs.index');
    }

    public function spec(): BinaryFileResponse|Response
    {
        $this->authorizeSuperuser();

        $path = resource_path('openapi/openapi.yaml');
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Content-Type' => 'application/yaml; charset=UTF-8']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * SwaggerController
 *
 * Serves the Swagger UI and the OpenAPI spec file.
 *
 * Routes (web — no auth required):
 *   GET /api/docs      → Swagger UI HTML
 *   GET /api/docs/spec → dental-clinic-api.yaml raw file
 */
class SwaggerController extends Controller
{
    public function ui(): Response
    {
        return response(view('swagger')->render(), 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    public function spec(): Response
    {
        $specPath = base_path('dental-clinic-api.yaml');

        if (! file_exists($specPath)) {
            abort(404, 'OpenAPI spec file not found.');
        }

        return response(file_get_contents($specPath), 200, [
            'Content-Type'        => 'application/x-yaml',
            'Content-Disposition' => 'inline; filename="dental-clinic-api.yaml"',
        ]);
    }
}

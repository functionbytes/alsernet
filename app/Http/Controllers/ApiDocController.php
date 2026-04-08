<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ApiDocController extends Controller
{
    /**
     * Serve the Swagger UI for the API documentation.
     *
     * Publicly accessible — no auth required.
     * The UI loads openapi.yaml from the same /api-docs/ directory.
     */
    public function index(): Response
    {
        $html = file_get_contents(public_path('api-docs/index.html'));

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Serve the raw OpenAPI YAML spec for tooling consumption.
     */
    public function spec(): Response
    {
        $yaml = file_get_contents(public_path('api-docs/openapi.yaml'));

        return response($yaml, 200)
            ->header('Content-Type', 'application/yaml')
            ->header('Content-Disposition', 'inline; filename="openapi.yaml"');
    }
}

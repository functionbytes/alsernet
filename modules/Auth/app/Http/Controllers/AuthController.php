<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function index(): View
    {
        return view('auth::index');
    }

    public function create(): View
    {
        return view('auth::create');
    }

    public function store(Request $request): never
    {
        abort(501, 'Not implemented.');
    }

    public function show(string $id): View
    {
        return view('auth::show');
    }

    public function edit(string $id): View
    {
        return view('auth::edit');
    }

    public function update(Request $request, string $id): never
    {
        abort(501, 'Not implemented.');
    }

    public function destroy(string $id): never
    {
        abort(501, 'Not implemented.');
    }
}

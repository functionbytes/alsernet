<?php

namespace Modules\Database\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DatabaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('database::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('database::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): never
    {
        abort(501, 'Not implemented');
    }

    /**
     * Show the specified resource.
     */
    public function show($id): View
    {
        return view('database::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        return view('database::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): never
    {
        abort(501, 'Not implemented');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): never
    {
        abort(501, 'Not implemented');
    }
}

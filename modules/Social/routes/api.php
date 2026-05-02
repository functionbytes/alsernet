<?php

use Illuminate\Support\Facades\Route;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group.
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Social module API endpoints are exposed via dedicated API controllers
    // under their respective resource routes in routes/web.php
});

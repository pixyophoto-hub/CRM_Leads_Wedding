<?php

use Illuminate\Support\Facades\Route;

// Serve the CRM dashboard (DC static app), injecting the API token at runtime.
Route::get('/', function () {
    $html = file_get_contents(resource_path('dashboard.html'));
    $html = str_replace('__API_TOKEN__', config('wedding.api_token'), $html);

    return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
});

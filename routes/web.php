<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation routes
Route::get('/docs/api', function () {
    return view('api-docs');
});

Route::get('/docs/openapi.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/yaml',
        'Access-Control-Allow-Origin' => '*'
    ]);
});

<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Article routes
Route::prefix('articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::get('/search', [ArticleController::class, 'search']);
    Route::get('/{slug}', [ArticleController::class, 'show']);
});

// Source routes
Route::get('/sources', [SourceController::class, 'index']);

// Category routes
Route::get('/categories', [CategoryController::class, 'index']);
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\BrandsController;
use App\Http\Controllers\Admin\CategoriesController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// categories
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoriesController::class, 'index']);
    Route::post('/', [CategoriesController::class, 'store']);
    Route::get('/{id}', [CategoriesController::class, 'show']);
    Route::post('/{id}', [CategoriesController::class, 'update']);
    Route::delete('/{id}', [CategoriesController::class, 'destroy']);
});
Route::prefix('brands')->group(function () {
    Route::get('/', [BrandsController::class, 'index']);
    Route::post('/', [BrandsController::class, 'store']);
    Route::get('/{id}', [BrandsController::class, 'show']);
    Route::post('/{id}', [BrandsController::class, 'update']);
    Route::delete('/{id}', [BrandsController::class, 'destroy']);
});
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::post('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});
// Route::get('categories', [CategoriesController::class, 'index']);
// Route::get('categories/{id}', [CategoriesController::class, 'show']);
// Route::post('categories', [CategoriesController::class, 'store']);
// Route::delete('categories/{id}', [CategoriesController::class, 'destroy']);
// Route::post('categories/{id}', [CategoriesController::class, 'update']);
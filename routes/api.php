<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\BrandsController;
use App\Http\Controllers\Admin\PromotionsController;
use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\IndexController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// categories
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoriesController::class, 'index']);
    Route::post('/', [CategoriesController::class, 'store']);
    Route::get('/{id}', [CategoriesController::class, 'show']);
    Route::put('/{id}', [CategoriesController::class, 'update']);
    Route::delete('/{id}', [CategoriesController::class, 'destroy']);
});
Route::prefix('brands')->group(function () {
    Route::get('/', [BrandsController::class, 'index']);
    Route::post('/', [BrandsController::class, 'store']);
    Route::get('/{id}', [BrandsController::class, 'show']);
    Route::put('/{id}', [BrandsController::class, 'update']);
    Route::delete('/{id}', [BrandsController::class, 'destroy']);
});
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
    Route::get('promotional/list', [ProductController::class, 'getPromotionalProducts']);

});
Route::prefix('promotions')->group(function () {
    Route::get('/', [PromotionsController::class, 'index']);
    Route::get('/active', [PromotionsController::class, 'getActivePromotions']);
    Route::get('/{id}', [PromotionsController::class, 'show']);
    Route::post('/', [PromotionsController::class, 'store']);
    Route::put('/{id}', [PromotionsController::class, 'update']);
    Route::delete('/{id}', [PromotionsController::class, 'destroy']);
    Route::patch('/{id}/status', [PromotionsController::class, 'updateStatus']);
});
// Colors Routes
Route::prefix('colors')->group(function () {
    Route::get('/', [ColorController::class, 'index']);
    Route::post('/', [ColorController::class, 'store']);
    Route::get('/{id}', [ColorController::class, 'show']);
    Route::put('/{id}', [ColorController::class, 'update']);
    Route::delete('/{id}', [ColorController::class, 'destroy']);
});

// Sizes Routes
Route::prefix('sizes')->group(function () {
    Route::get('/', [SizeController::class, 'index']);
    Route::post('/', [SizeController::class, 'store']);
    Route::get('/{id}', [SizeController::class, 'show']);
    Route::put('/{id}', [SizeController::class, 'update']);
    Route::delete('/{id}', [SizeController::class, 'destroy']);
});

Route::prefix('auth')->group(function () {
    // Routes cho Google
    Route::get('google/url', [AuthController::class, 'getGoogleSignInUrl']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
      // Routes cho Facebook
      Route::get('facebook/url', [AuthController::class, 'getFacebookSignInUrl']);
      Route::get('facebook/callback', [AuthController::class, 'handleFacebookCallback']);
});
//Home
Route::prefix('home')->group(function () {
   Route::get('/', [IndexController::class, 'getHome']);
   Route::get('/flash-sale', [IndexController::class, 'getFlashSale']);
   Route::get('getDetail/{id}', [IndexController::class, 'getDetailProduct']);
});
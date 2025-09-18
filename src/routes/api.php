<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
    Route::post('products/upload', [\App\Http\Controllers\Api\ProductController::class, 'upload']);
});

<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
    Route::post('products/upload', [\App\Http\Controllers\Api\ProductController::class, 'upload']);
    Route::post('products/import', [\App\Http\Controllers\Api\ProductController::class, 'import']);

    Route::apiResource('categories', \App\Http\Controllers\Api\CategoryController::class);

    Route::apiResource('suppliers', \App\Http\Controllers\Api\SupplierController::class);

    Route::get('changes', [\App\Http\Controllers\Api\ChangeLogController::class, 'index'])->name('changes.index');
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Provide a named login route to satisfy route('login') calls from auth middleware
Route::get('login', function () {
    return response()->json([
        'message' => 'Unauthenticated',
        'data' => null,
        'timestamp' => now()->toISOString(),
        'success' => false,
    ], 401);
})->name('login');

<?php

use App\Http\Controllers\UrlShortenerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// URL Shortener endpoints with rate limiting and validation middleware
Route::middleware(['throttle:60,1'])
    ->controller(UrlShortenerController::class)
    ->group(function () {
        Route::post('/encode', 'encode');
        Route::post('/decode', 'decode');
    });

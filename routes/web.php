<?php

use App\Http\Controllers\UrlShortenerController;
use Illuminate\Support\Facades\Route;

Route::get('/s/{code}', [UrlShortenerController::class, 'redirect']);

require __DIR__.'/auth.php';

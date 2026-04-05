<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpotifyAuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [SpotifyAuthController::class, 'redirect']);
Route::get('/callback', [SpotifyAuthController::class, 'callback']);

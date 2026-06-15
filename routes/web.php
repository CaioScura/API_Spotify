<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpotifyAuthController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [SpotifyAuthController::class, 'redirect']);
Route::get('/callback', [SpotifyAuthController::class, 'callback']);
Route::get('/logout', [SpotifyAuthController::class, 'logout']);
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/mood/{mood}/play', [DashboardController::class, 'playMood']);

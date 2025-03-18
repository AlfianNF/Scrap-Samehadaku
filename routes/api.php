<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScrapController;

Route::get('/', [ScrapController::class, 'index']);
Route::get('/anime/{slug}',[ScrapController::class, 'show']);
Route::get('/anime-terbaru', [ScrapController::class, 'latestAnime']);
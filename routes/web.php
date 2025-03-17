<?php

use App\Http\Controllers\ScrapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/scrape',[ScrapController::class,'index']);
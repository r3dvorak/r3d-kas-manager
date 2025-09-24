<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasClientController;

Route::get('/', function () {
    return view('welcome');
});

// Kas Client management
Route::resource('kas-clients', KasClientController::class);

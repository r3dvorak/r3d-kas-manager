<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasClientController;

//Route::get('/', function () {
//    return view('welcome');
//});
Route::get('/test-layout', function () {
    return view('test');
});

// Kas Client management
Route::resource('kas-clients', KasClientController::class);

<?php

use Illuminate\Support\Facades\Route;

Route::post('/webhook/issuer-platform', [\App\Http\Controllers\WebhookController::class, 'handle']);

Route::get('/', function () {
    return view('welcome');
});


Route::get('/demo', function () {
    return view('demo');
});

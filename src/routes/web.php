<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/inquiry', function () {
    return view('inquiry');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome');
    });
});

require __DIR__.'/auth.php';

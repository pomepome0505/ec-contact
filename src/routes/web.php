<?php

use App\Http\Controllers\InquiryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/inquiry', function () {
    return view('inquiry');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    });

    Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
});

require __DIR__.'/auth.php';

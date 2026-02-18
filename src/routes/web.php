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
    Route::get('/inquiries/{inquiry_id}', [InquiryController::class, 'show'])->name('inquiries.show');
    Route::patch('/inquiries/{inquiry_id}', [InquiryController::class, 'update'])->name('inquiries.update');
    Route::post('/inquiries/{inquiry_id}/reply', [InquiryController::class, 'reply'])->name('inquiries.reply');
});

require __DIR__.'/auth.php';

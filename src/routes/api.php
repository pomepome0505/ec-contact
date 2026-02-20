<?php

use App\Http\Controllers\Api\InquiryController;
use App\Models\InquiryCategory;
use Illuminate\Support\Facades\Route;

Route::post('/inquiries', [InquiryController::class, 'store']);

Route::get('/categories', function () {
    return InquiryCategory::where('is_active', true)
        ->orderBy('display_order')
        ->get(['id', 'name']);
});

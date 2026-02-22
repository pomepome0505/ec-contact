<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InquiryCategoryController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ヘルスチェックエンドポイント（ALB用、認証不要）
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/inquiry', function () {
    return view('inquiry');
});

Route::middleware('auth')->group(function () {
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::patch('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('require_password_change')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Dashboard');
        });

        Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('/inquiries/{inquiry_id}', [InquiryController::class, 'show'])->name('inquiries.show');
        Route::patch('/inquiries/{inquiry_id}', [InquiryController::class, 'update'])->name('inquiries.update');
        Route::post('/inquiries/{inquiry_id}/reply', [InquiryController::class, 'reply'])->name('inquiries.reply');

        Route::middleware('admin')->group(function () {
            Route::get('/categories', [InquiryCategoryController::class, 'index'])->name('categories.index');
            Route::get('/categories/create', [InquiryCategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [InquiryCategoryController::class, 'store'])->name('categories.store');
            Route::get('/categories/{category_id}/edit', [InquiryCategoryController::class, 'edit'])->name('categories.edit');
            Route::patch('/categories/{category_id}', [InquiryCategoryController::class, 'update'])->name('categories.update');
            Route::patch('/categories/{category_id}/toggle-active', [InquiryCategoryController::class, 'toggleActive'])->name('categories.toggleActive');
            Route::delete('/categories/{category_id}', [InquiryCategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::get('/employees/{employee_id}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::patch('/employees/{employee_id}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::patch('/employees/{employee_id}/toggle-active', [EmployeeController::class, 'toggleActive'])->name('employees.toggleActive');
            Route::delete('/employees/{employee_id}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
            Route::post('/employees/{employee_id}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.resetPassword');
        });
    });
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpItemController;
use App\Http\Controllers\ItemDocumentController;
use App\Http\Controllers\ItemTransferController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : app(AuthController::class)->create();
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

    Route::get('/password/reset', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/email', [PasswordResetController::class, 'sendLink'])->middleware('throttle:3,1')->name('password.request.send');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/items', [FollowUpItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [FollowUpItemController::class, 'create'])->name('items.create');
    Route::post('/items', [FollowUpItemController::class, 'store'])->name('items.store');
    Route::get('/items/{item}', [FollowUpItemController::class, 'show'])->name('items.show');
    Route::put('/items/{item}', [FollowUpItemController::class, 'update'])->name('items.update');

    Route::post('/items/{item}/transfer', [ItemTransferController::class, 'transfer'])->name('items.transfer');
    Route::post('/items/{item}/close', [ItemTransferController::class, 'close'])->name('items.close');

    Route::post('/items/{item}/documents', [ItemDocumentController::class, 'store'])->name('items.documents.store');
    Route::get('/items/{item}/documents/{document}', [ItemDocumentController::class, 'download'])->name('items.documents.download');
    Route::get('/items/{item}/documents/{document}/preview', [ItemDocumentController::class, 'preview'])->name('items.documents.preview');
    Route::post('/items/{item}/documents/{document}/primary', [ItemDocumentController::class, 'setPrimary'])->name('items.documents.primary');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('sections', SectionController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
    });
});

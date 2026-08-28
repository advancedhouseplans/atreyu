<?php

use App\Http\Controllers\AttachmentController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/reports/{report}/attachments/{attachment}',
        [AttachmentController::class, 'show']
    )->name('reports.attachments.download');
});

require __DIR__.'/settings.php';

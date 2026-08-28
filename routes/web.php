<?php

use App\Http\Controllers\AttachmentController;
use App\Livewire\Reports\Index;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reports')->name('home');

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/reports', Index::class)->name('reports.index');
    Route::get('/reports/{report}', fn () => '')->name('reports.show'); // stub — Task 12 replaces this
    Route::get('/reports/{report}/attachments/{attachment}',
        [AttachmentController::class, 'show']
    )->name('reports.attachments.download');
});

require __DIR__.'/settings.php';

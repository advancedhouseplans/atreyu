<?php

use App\Http\Controllers\Api\ReportsController;
use Illuminate\Support\Facades\Route;

Route::middleware('bug.token')->group(function () {
    Route::post('/reports', [ReportsController::class, 'store'])->name('api.reports.store');
});

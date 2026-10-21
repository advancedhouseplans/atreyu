<?php

use BugCompanion\Http\Controllers\ActivateTelemetryController;
use BugCompanion\Http\Controllers\DeactivateTelemetryController;
use BugCompanion\Http\Controllers\DebugContextController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:10,1'])
    ->get('/_atreyu/activate', ActivateTelemetryController::class)
    ->name('atreyu.activate');

Route::middleware(['web', 'throttle:10,1'])
    ->get('/_atreyu/deactivate', DeactivateTelemetryController::class)
    ->name('atreyu.deactivate');

Route::middleware('bugs.callback')
    ->post('/_atreyu/context', DebugContextController::class)
    ->name('atreyu.context');

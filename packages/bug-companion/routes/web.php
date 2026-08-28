<?php

use BugCompanion\Http\Controllers\DebugContextController;
use Illuminate\Support\Facades\Route;

Route::middleware('bugs.callback')
    ->get('/_bug-debug/{ids}', [DebugContextController::class, 'show'])
    ->where('ids', '.*');

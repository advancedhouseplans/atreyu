<?php

use App\Http\Controllers\AttachmentController;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Show as ProjectsShow;
use App\Livewire\Reports\Index;
use App\Livewire\Reports\Show;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Livewire\Tasks\Show as TasksShow;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reports')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/projects', ProjectsIndex::class)->name('projects.index');
    Route::get('/notifications', NotificationsIndex::class)->name('notifications.index');
    Route::get('/projects/{project}', ProjectsShow::class)->name('projects.show');
    Route::get('/tasks', TasksIndex::class)->name('tasks.index');
    Route::get('/tasks/{task}', TasksShow::class)->name('tasks.show');
    Route::get('/reports', Index::class)->name('reports.index');
    Route::get('/reports/{report}', Show::class)->name('reports.show');
    Route::get('/reports/{report}/attachments/{attachment}',
        [AttachmentController::class, 'show']
    )->name('reports.attachments.download');
});

require __DIR__.'/settings.php';

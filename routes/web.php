<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\OnlyOfficeController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\RecurringTaskController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TrashController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', HomeController::class)->name('dashboard');
    Route::get('search', SearchController::class)->name('search');
    Route::get('calendar', CalendarController::class)->name('calendar');

    // Cây thư mục
    Route::get('folders/{folder}', [FolderController::class, 'show'])->name('folders.show');
    Route::post('folders/{folder}/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::patch('folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::post('folders/{folder}/pin', [PinController::class, 'toggle'])->name('folders.pin');
    Route::post('companies', [DirectoryController::class, 'storeCompany'])->name('companies.store');
    Route::post('companies/{company}/projects', [DirectoryController::class, 'storeProject'])->name('projects.store');

    // Tài liệu và phiên bản
    Route::post('folders/{folder}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::post('folders/{folder}/documents/blank', [DocumentController::class, 'storeBlank'])->name('documents.blank');
    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::patch('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{document}/versions', [DocumentVersionController::class, 'index'])->name('documents.versions.index');
    Route::get('documents/{document}/versions/{version}/download', [DocumentController::class, 'downloadVersion'])->name('documents.versions.download');
    Route::post('documents/{document}/versions/{version}/restore', [DocumentVersionController::class, 'restore'])->name('documents.versions.restore');

    Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('trash/{document}/restore', [TrashController::class, 'restore'])->name('trash.restore');

    // Bảng tin và công việc cá nhân
    Route::get('notices', [NoticeController::class, 'index'])->name('notices.index');
    Route::post('notices', [NoticeController::class, 'store'])->name('notices.store');
    Route::delete('notices/{notice}', [NoticeController::class, 'destroy'])->name('notices.destroy');
    Route::post('todos', [TodoController::class, 'store'])->name('todos.store');
    Route::patch('todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
    Route::delete('todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('recurring-tasks', [RecurringTaskController::class, 'store'])->name('recurring-tasks.store');
    Route::delete('recurring-tasks/{recurringTask}', [RecurringTaskController::class, 'destroy'])->name('recurring-tasks.destroy');
    Route::post('recurring-tasks/{recurringTask}/complete', [RecurringTaskController::class, 'complete'])->name('recurring-tasks.complete');
});

// Chỉ Document Server gọi: link tải có chữ ký và callback có JWT.
Route::get('onlyoffice/download/{version}', [OnlyOfficeController::class, 'download'])
    ->middleware('signed:relative')
    ->name('onlyoffice.download');
Route::post('onlyoffice/callback/{key}', [OnlyOfficeController::class, 'callback'])
    ->middleware('throttle:120,1')
    ->name('onlyoffice.callback');

require __DIR__.'/settings.php';

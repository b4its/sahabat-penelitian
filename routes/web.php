<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document Workspace Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [DocumentController::class, 'index'])->name('editor.home');
Route::get('/editor', [DocumentController::class, 'index'])->name('editor.index');

/*
|--------------------------------------------------------------------------
| Document API Endpoints
|--------------------------------------------------------------------------
*/
Route::prefix('api/documents')->name('documents.')->group(function () {
    Route::post('/import', [DocumentController::class, 'import'])->name('import');
    Route::post('/export', [DocumentController::class, 'export'])->name('export');
    Route::post('/ai-assistant', [DocumentController::class, 'aiAssistant'])->name('ai-assistant');
});

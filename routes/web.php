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
    Route::post('/search-sources', [DocumentController::class, 'searchSources'])->name('search-sources');
    Route::post('/format-bibliography', [DocumentController::class, 'formatBibliography'])->name('format-bibliography');
    Route::post('/generate-toc', [DocumentController::class, 'generateToc'])->name('generate-toc');
    Route::post('/update-toc', [DocumentController::class, 'updateToc'])->name('update-toc');
    Route::post('/model/parse', [DocumentController::class, 'parseModel'])->name('model.parse');
    Route::post('/model/render', [DocumentController::class, 'renderModel'])->name('model.render');
    Route::post('/model/migrate', [DocumentController::class, 'migrateModel'])->name('model.migrate');
    Route::post('/model/validate', [DocumentController::class, 'validateModelEndpoint'])->name('model.validate');
    Route::post('/transactions/apply', [DocumentController::class, 'applyTransaction'])->name('transactions.apply');
    Route::post('/transactions/invert', [DocumentController::class, 'invertTransaction'])->name('transactions.invert');
    Route::post('/search-replace', [DocumentController::class, 'searchReplace'])->name('search-replace');
    Route::post('/accessibility-audit', [DocumentController::class, 'accessibilityAudit'])->name('accessibility-audit');
});

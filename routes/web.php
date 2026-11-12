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
    Route::post('/review/revisions', [DocumentController::class, 'listRevisions'])->name('review.revisions');
    Route::post('/review/accept', [DocumentController::class, 'acceptRevision'])->name('review.accept');
    Route::post('/review/reject', [DocumentController::class, 'rejectRevision'])->name('review.reject');
    Route::post('/review/accept-all', [DocumentController::class, 'acceptAllRevisions'])->name('review.accept-all');
    Route::post('/review/reject-all', [DocumentController::class, 'rejectAllRevisions'])->name('review.reject-all');
    Route::post('/review/compare', [DocumentController::class, 'compareDocuments'])->name('review.compare');
    Route::post('/review/comments', [DocumentController::class, 'listComments'])->name('review.comments');

    // Server Collaboration & Versions (W09 & W10)
    Route::post('/server/create', [DocumentController::class, 'createServerDocument'])->name('server.create');
    Route::get('/server/{uuid}', [DocumentController::class, 'getServerDocument'])->name('server.show');
    Route::get('/server/{uuid}/versions', [DocumentController::class, 'listVersions'])->name('server.versions');
    Route::post('/server/{uuid}/versions', [DocumentController::class, 'createVersion'])->name('server.versions.create');
    Route::post('/server/{uuid}/versions/{version}/restore', [DocumentController::class, 'restoreVersion'])->name('server.versions.restore');
    Route::post('/server/{uuid}/operations', [DocumentController::class, 'applyCollaborativeOperation'])->name('server.operations');
    Route::post('/server/{uuid}/presence', [DocumentController::class, 'heartbeatPresence'])->name('server.presence.heartbeat');
    Route::get('/server/{uuid}/presence', [DocumentController::class, 'getPresence'])->name('server.presence.get');
    Route::get('/server/{uuid}/audit', [DocumentController::class, 'getAuditEvents'])->name('server.audit');

    // Proofing, Mail Merge & Protection (W11, W12, W13)
    Route::post('/proofing/check', [DocumentController::class, 'checkProofing'])->name('proofing.check');
    Route::post('/proofing/speech-segments', [DocumentController::class, 'speechSegments'])->name('proofing.speech-segments');
    Route::post('/mail-merge/preview', [DocumentController::class, 'previewMailMerge'])->name('mail-merge.preview');
    Route::post('/mail-merge/batch', [DocumentController::class, 'batchMailMerge'])->name('mail-merge.batch');
    Route::get('/mail-merge/presets', [DocumentController::class, 'getMailMergePresets'])->name('mail-merge.presets');
    Route::post('/server/{uuid}/protection', [DocumentController::class, 'setProtection'])->name('server.protection.set');
    Route::post('/server/{uuid}/protection/verify', [DocumentController::class, 'verifyProtection'])->name('server.protection.verify');
});

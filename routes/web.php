<?php

use App\Http\Controllers\AiStudioController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document Workspace & Landing Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [DocumentController::class, 'landing'])->name('landing');
Route::get('/landing', [DocumentController::class, 'landing'])->name('landing.page');
Route::get('/editor', [DocumentController::class, 'index'])->name('editor.index');
Route::get('/editor/workspace', [DocumentController::class, 'index'])->name('editor.home');

/*
|--------------------------------------------------------------------------
| Panel Routes: Dashboard & Document Management
|--------------------------------------------------------------------------
*/
Route::prefix('panel')->name('panel.')->group(function () {
    Route::get('/', [PanelController::class, 'dashboard'])->name('home');
    Route::get('/dashboard', [PanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/documents', [PanelController::class, 'documents'])->name('documents');
    Route::post('/documents', [PanelController::class, 'storeDocument'])->name('documents.store');
    Route::post('/documents/{uuid}/clone', [PanelController::class, 'cloneDocument'])->name('documents.clone');
    Route::delete('/documents/{uuid}', [PanelController::class, 'destroyDocument'])->name('documents.destroy');
    Route::post('/documents/bulk-clone', [PanelController::class, 'bulkCloneDocuments'])->name('documents.bulk-clone');
    Route::post('/documents/bulk-destroy', [PanelController::class, 'bulkDestroyDocuments'])->name('documents.bulk-destroy');
    Route::post('/profile', [PanelController::class, 'updateProfile'])->name('profile.update');
    Route::post('/logout', [PanelController::class, 'logout'])->name('logout');

    // AI Studio Routes
    Route::get('/ai-studio', [AiStudioController::class, 'index'])->name('ai-studio.index');
    Route::get('/ai-studio/connections', [AiStudioController::class, 'connections'])->name('ai-studio.connections');
    Route::get('/ai-studio/chat', [AiStudioController::class, 'chatView'])->name('ai-studio.chat');
    Route::get('/ai-studio/providers/list', [AiStudioController::class, 'getProvidersJson'])->name('ai-studio.providers.list');
    Route::post('/ai-studio/providers', [AiStudioController::class, 'storeProvider'])->name('ai-studio.providers.store');
    Route::post('/ai-studio/providers/{uuid}/update', [AiStudioController::class, 'updateProvider'])->name('ai-studio.providers.update');
    Route::delete('/ai-studio/providers/{uuid}', [AiStudioController::class, 'destroyProvider'])->name('ai-studio.providers.destroy');
    Route::post('/ai-studio/providers/{uuid}/test', [AiStudioController::class, 'testProvider'])->name('ai-studio.providers.test');
    Route::post('/ai-studio/providers/{uuid}/toggle', [AiStudioController::class, 'toggleProvider'])->name('ai-studio.providers.toggle');
    Route::post('/ai-studio/chat/send', [AiStudioController::class, 'sendChatMessage'])->name('ai-studio.chat.send');
    Route::post('/ai-studio/chat/clear', [AiStudioController::class, 'clearChatSession'])->name('ai-studio.chat.clear');
});

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

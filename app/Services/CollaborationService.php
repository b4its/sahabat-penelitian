<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\Document;
use App\Models\DocumentOperation;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Cache;

class CollaborationService
{
    public function __construct(
        protected ?DocumentTransactionService $transactionService = null,
        protected ?DocumentModelService $modelService = null,
    ) {
        $this->transactionService = $transactionService ?? new DocumentTransactionService;
        $this->modelService = $modelService ?? new DocumentModelService;
    }

    /**
     * Create an immutable snapshot version of a document.
     */
    public function createVersionSnapshot(Document $document, string $author = 'User', ?string $summary = null): DocumentVersion
    {
        $newVersionNumber = $document->current_version + 1;

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $newVersionNumber,
            'title' => $document->title,
            'content_html' => $document->content_html ?? '',
            'model_json' => $document->model_json,
            'author' => $author,
            'change_summary' => $summary ?? "Snapshot versi {$newVersionNumber}",
        ]);

        $document->update([
            'current_version' => $newVersionNumber,
        ]);

        $this->logAuditEvent($document, 'version_created', $author, [
            'version' => $newVersionNumber,
            'summary' => $summary,
        ]);

        return $version;
    }

    /**
     * Restore document to a prior version without destroying current head history.
     */
    public function restoreVersion(Document $document, int $versionNumber, string $author = 'User'): DocumentVersion
    {
        $targetVersion = $document->versions()->where('version_number', $versionNumber)->firstOrFail();

        // Update document head with prior version contents
        $document->update([
            'title' => $targetVersion->title,
            'content_html' => $targetVersion->content_html,
            'model_json' => $targetVersion->model_json,
        ]);

        // Create a new snapshot capturing the restoration as the latest head
        $newVersion = $this->createVersionSnapshot(
            $document,
            $author,
            "Dipulihkan dari versi {$versionNumber}"
        );

        $this->logAuditEvent($document, 'version_restored', $author, [
            'from_version' => $versionNumber,
            'new_version' => $newVersion->version_number,
        ]);

        return $newVersion;
    }

    /**
     * Check if a user has sufficient permission role on document.
     */
    public function checkPermission(Document $document, string $userEmail, string $requiredRole): bool
    {
        $rolesOrder = [
            'viewer' => 1,
            'reviewer' => 2,
            'editor' => 3,
            'owner' => 4,
        ];

        $requiredLevel = $rolesOrder[$requiredRole] ?? 1;

        $permission = $document->permissions()->where('user_email', $userEmail)->first();
        if (! $permission) {
            // Document owner fallback
            return false;
        }

        $userLevel = $rolesOrder[$permission->role] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Apply an atomic collaborative operation to the document.
     */
    public function applyServerOperation(Document $document, array $op, string $actor = 'User'): array
    {
        $currentModel = $document->model_json ?? $this->modelService->htmlToModel($document->content_html ?? '');
        $updatedModel = $this->transactionService->applyOperation($currentModel, $op);
        $updatedHtml = $this->modelService->modelToHtml($updatedModel);

        $newVersion = $document->current_version + 1;

        $document->update([
            'content_html' => $updatedHtml,
            'model_json' => $updatedModel,
            'current_version' => $newVersion,
        ]);

        $loggedOp = DocumentOperation::create([
            'document_id' => $document->id,
            'version' => $newVersion,
            'op_type' => $op['type'] ?? 'unknown',
            'payload' => $op,
            'actor' => $actor,
        ]);

        return [
            'success' => true,
            'version' => $newVersion,
            'operation' => $loggedOp,
            'model' => $updatedModel,
            'html' => $updatedHtml,
        ];
    }

    /**
     * Log an audit event.
     */
    public function logAuditEvent(?Document $document, string $eventType, string $actor, array $details = [], ?string $ip = null): AuditEvent
    {
        return AuditEvent::create([
            'document_id' => $document?->id,
            'event_type' => $eventType,
            'actor' => $actor,
            'details' => $details,
            'ip_address' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Register or update user presence for real-time collaboration.
     */
    public function heartbeatPresence(string $docUuid, string $userId, string $userName, array $cursor = []): array
    {
        $cacheKey = "presence:{$docUuid}";
        $current = Cache::get($cacheKey, []);

        $current[$userId] = [
            'id' => $userId,
            'name' => $userName,
            'user_name' => $userName,
            'cursor' => $cursor,
            'lastSeen' => now()->timestamp,
        ];

        // Expire inactive users after 30 seconds
        $now = now()->timestamp;
        $current = array_filter($current, fn ($u) => ($now - $u['lastSeen']) < 30);

        Cache::put($cacheKey, $current, 60);

        return array_values($current);
    }

    /**
     * Get active collaborators on document.
     */
    public function getActivePresence(string $docUuid): array
    {
        $cacheKey = "presence:{$docUuid}";
        $current = Cache::get($cacheKey, []);

        $now = now()->timestamp;
        $active = array_filter($current, fn ($u) => ($now - $u['lastSeen']) < 30);

        return array_values($active);
    }
}

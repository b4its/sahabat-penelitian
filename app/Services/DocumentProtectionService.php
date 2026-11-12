<?php

namespace App\Services;

use App\Models\Document;

class DocumentProtectionService
{
    /**
     * Set password protection and restricted mode on document.
     */
    public function setProtection(Document $document, string $mode, ?string $password = null): Document
    {
        $hash = (! empty($password)) ? password_hash($password, PASSWORD_BCRYPT) : null;

        $document->update([
            'restricted_mode' => $mode,
            'password_hash' => $hash,
        ]);

        return $document;
    }

    /**
     * Verify password for protected document.
     */
    public function verifyPassword(Document $document, string $password): bool
    {
        if (empty($document->password_hash)) {
            return true;
        }

        return password_verify($password, $document->password_hash);
    }

    /**
     * Remove protection from document.
     */
    public function removeProtection(Document $document, string $currentPassword): bool
    {
        if (! $this->verifyPassword($document, $currentPassword)) {
            return false;
        }

        $document->update([
            'restricted_mode' => 'none',
            'password_hash' => null,
        ]);

        return true;
    }
}

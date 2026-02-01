<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action for a document.
     *
     * @param Document $document
     * @param string $action (e.g., 'VIEWED', 'SIGNED', 'COMPLETED', 'SENT')
     * @param int|null $userId (Null if public/guest)
     * @param array $metadata
     * @return AuditLog
     */
    public function log(Document $document, string $action, ?int $userId = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'document_id' => $document->id,
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'metadata' => $metadata,
        ]);
    }
}

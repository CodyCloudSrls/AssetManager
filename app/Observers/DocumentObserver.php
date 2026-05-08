<?php

namespace App\Observers;

use App\Models\Actionlog;
use App\Models\Document;
use App\Models\DocumentAssignmentEvent;
use App\Models\User;
use App\Support\Documents\DocumentAssignmentManager;

class DocumentObserver
{
    public function updated(Document $document)
    {
        $logAction = new Actionlog;
        $logAction->item_type = Document::class;
        $logAction->item_id = $document->id;
        $logAction->target_type = $document->owner_id ? User::class : null;
        $logAction->target_id = $document->owner_id;
        $logAction->created_at = date('Y-m-d H:i:s');
        $logAction->action_date = date('Y-m-d H:i:s');
        $logAction->created_by = auth()->id();

        if ($document->imported) {
            $logAction->setActionSource('importer');
        }

        $logAction->logaction('update');
    }

    public function created(Document $document)
    {
        $logAction = new Actionlog;
        $logAction->item_type = Document::class;
        $logAction->item_id = $document->id;
        $logAction->target_type = $document->owner_id ? User::class : null;
        $logAction->target_id = $document->owner_id;
        $logAction->created_at = date('Y-m-d H:i:s');
        $logAction->action_date = date('Y-m-d H:i:s');
        $logAction->created_by = auth()->id();

        if ($document->imported) {
            $logAction->setActionSource('importer');
        }

        $logAction->logaction('create');
    }

    public function deleting(Document $document)
    {
        $document->documentAssignments()->get()->each(function ($assignment) use ($document) {
            DocumentAssignmentManager::logAssignmentEvent(
                $document,
                $assignment,
                DocumentAssignmentEvent::EVENT_DELETED,
                DocumentAssignmentManager::auditSnapshot($assignment),
                []
            );

            $assignment->delete();
        });

        $logAction = new Actionlog;
        $logAction->item_type = Document::class;
        $logAction->item_id = $document->id;
        $logAction->target_type = $document->owner_id ? User::class : null;
        $logAction->target_id = $document->owner_id;
        $logAction->created_at = date('Y-m-d H:i:s');
        $logAction->action_date = date('Y-m-d H:i:s');
        $logAction->created_by = auth()->id();
        $logAction->logaction('delete');
    }

    public function restored(Document $document)
    {
        $document->documentAssignments()->withTrashed()->restore();

        $logAction = new Actionlog;
        $logAction->item_type = Document::class;
        $logAction->item_id = $document->id;
        $logAction->target_type = $document->owner_id ? User::class : null;
        $logAction->target_id = $document->owner_id;
        $logAction->created_at = date('Y-m-d H:i:s');
        $logAction->action_date = date('Y-m-d H:i:s');
        $logAction->created_by = auth()->id();
        $logAction->logaction('restore');
    }
}

<?php

namespace App\Observers;

use App\Enums\ActionType;
use App\Models\Ticket;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $ticket->logCreate(trans('admin/tickets/message.create.success'));
    }

    public function updated(Ticket $ticket): void
    {
        $changes = $ticket->getChanges();
        unset($changes['updated_at']);

        if (! empty($changes)) {
            $logAction = new \App\Models\Actionlog;
            $logAction->item_type = Ticket::class;
            $logAction->item_id = $ticket->id;
            $logAction->target_type = $ticket->assignee_id ? \App\Models\User::class : null;
            $logAction->target_id = $ticket->assignee_id;
            $logAction->created_at = date('Y-m-d H:i:s');
            $logAction->action_date = date('Y-m-d H:i:s');
            $logAction->created_by = auth()->id();
            $logAction->log_meta = json_encode(collect($changes)->mapWithKeys(function ($newValue, $key) use ($ticket) {
                return [$key => [
                    'old' => $ticket->getOriginal($key),
                    'new' => $newValue,
                ]];
            })->all());
            $logAction->logaction(ActionType::Update->value);
        }
    }

    public function deleting(Ticket $ticket): void
    {
        $logAction = new \App\Models\Actionlog;
        $logAction->item_type = Ticket::class;
        $logAction->item_id = $ticket->id;
        $logAction->target_type = $ticket->assignee_id ? \App\Models\User::class : null;
        $logAction->target_id = $ticket->assignee_id;
        $logAction->created_at = date('Y-m-d H:i:s');
        $logAction->action_date = date('Y-m-d H:i:s');
        $logAction->created_by = auth()->id();
        $logAction->logaction(ActionType::Delete->value);
    }
}

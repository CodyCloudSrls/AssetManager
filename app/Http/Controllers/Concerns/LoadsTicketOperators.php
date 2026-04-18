<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

trait LoadsTicketOperators
{
    protected function ticketAssignableUsers(Ticket $ticket): Collection
    {
        $query = User::withoutGlobalScopes()
            ->whereNull('deleted_at');

        if (! auth()->user()?->isSuperUser()) {
            $query->withoutPlatformSuperAdmins();
        }

        $ticketCompany = Company::withoutGlobalScopes()->find($ticket->company_id);

        if ($ticketCompany && $ticketCompany->tenant_id) {
            $companyIds = Company::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('tenant_id', $ticketCompany->tenant_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $query->whereIn('company_id', $companyIds);
        } elseif ($ticket->company_id) {
            $query->where('company_id', $ticket->company_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->orderBy('display_name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('username')
            ->get();
    }
}

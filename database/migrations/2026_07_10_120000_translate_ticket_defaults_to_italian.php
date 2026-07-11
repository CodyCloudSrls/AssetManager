<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The ticket module seeds its default statuses/priorities/types in English. This app is
 * Italian, so translate the DEFAULT rows (matched by their exact English name, so any
 * customised value is left untouched). Only the display `name` changes — slug/color/flags
 * stay put, so ticket references (status_id, etc.) are unaffected. Runs after the create+seed
 * migration, so it corrects both existing installs and fresh ones.
 */
return new class extends Migration
{
    private array $map = [
        'ticket_statuses' => [
            'New' => 'Nuovo',
            'Triage' => 'Presa in carico',
            'In Progress' => 'In lavorazione',
            'Waiting Customer' => 'In attesa cliente',
            'Waiting Vendor' => 'In attesa fornitore',
            'Resolved' => 'Risolto',
            'Closed' => 'Chiuso',
            'Cancelled' => 'Annullato',
        ],
        'ticket_priorities' => [
            'Low' => 'Bassa',
            'Medium' => 'Media',
            'High' => 'Alta',
            'Critical' => 'Critica',
        ],
        'ticket_types' => [
            'Incident' => 'Incidente',
            'Service Request' => 'Richiesta di servizio',
            'Access Request' => 'Richiesta di accesso',
            'Change Request' => 'Richiesta di modifica',
        ],
    ];

    public function up(): void
    {
        $this->rename(false);
    }

    public function down(): void
    {
        $this->rename(true);
    }

    private function rename(bool $reverse): void
    {
        foreach ($this->map as $table => $pairs) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($pairs as $en => $it) {
                [$from, $to] = $reverse ? [$it, $en] : [$en, $it];
                DB::table($table)->where('name', $from)->update(['name' => $to]);
            }
        }
    }
};

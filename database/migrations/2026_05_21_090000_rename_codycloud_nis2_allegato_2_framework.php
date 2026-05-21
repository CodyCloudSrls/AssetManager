<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_frameworks')) {
            return;
        }

        DB::table('document_frameworks')
            ->where('slug', 'nis2')
            ->where('name', 'NIS2')
            ->whereNull('deleted_at')
            ->update(['name' => 'NIS2 IT - Allegato 2']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_frameworks')) {
            return;
        }

        DB::table('document_frameworks')
            ->where('slug', 'nis2')
            ->where('name', 'NIS2 IT - Allegato 2')
            ->whereNull('deleted_at')
            ->update(['name' => 'NIS2']);
    }
};

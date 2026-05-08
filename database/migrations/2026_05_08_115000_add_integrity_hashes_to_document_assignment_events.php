<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_assignment_events', function (Blueprint $table) {
            if (! Schema::hasColumn('document_assignment_events', 'hash_algorithm')) {
                $table->string('hash_algorithm', 20)->nullable()->after('user_agent');
            }

            if (! Schema::hasColumn('document_assignment_events', 'previous_hash')) {
                $table->string('previous_hash', 64)->nullable()->after('hash_algorithm');
            }

            if (! Schema::hasColumn('document_assignment_events', 'payload_hash')) {
                $table->string('payload_hash', 64)->nullable()->after('previous_hash');
            }

            if (! Schema::hasColumn('document_assignment_events', 'event_hash')) {
                $table->string('event_hash', 64)->nullable()->after('payload_hash')->index('doc_assignment_events_event_hash_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_assignment_events', function (Blueprint $table) {
            foreach (['event_hash', 'payload_hash', 'previous_hash', 'hash_algorithm'] as $column) {
                if (Schema::hasColumn('document_assignment_events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

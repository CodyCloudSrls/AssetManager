<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('document_assignments', 'approval_status')) {
                $table->string('approval_status', 40)->default('pending')->after('status')->index();
            }

            if (! Schema::hasColumn('document_assignments', 'reviewer_id')) {
                $table->unsignedInteger('reviewer_id')->nullable()->after('issuer_id');
                $table->foreign('reviewer_id')->references('id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('document_assignments', 'reviewed_at')) {
                $table->dateTime('reviewed_at')->nullable()->after('reviewer_id')->index();
            }

            if (! Schema::hasColumn('document_assignments', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_at');
            }
        });

        if (! Schema::hasTable('document_assignment_events')) {
            Schema::create('document_assignment_events', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('document_assignment_id');
                $table->unsignedInteger('document_id');
                $table->unsignedInteger('company_id');
                $table->string('event_type', 60);
                $table->string('approval_status', 40)->nullable();
                $table->unsignedInteger('actor_id')->nullable();
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->text('note')->nullable();
                $table->ipAddress('remote_ip')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['document_assignment_id', 'created_at'], 'doc_assignment_events_assignment_idx');
                $table->index(['document_id', 'created_at'], 'doc_assignment_events_document_idx');
                $table->index(['company_id', 'created_at'], 'doc_assignment_events_company_idx');
                $table->index(['event_type', 'created_at'], 'doc_assignment_events_type_idx');
                $table->index(['approval_status', 'created_at'], 'doc_assignment_events_approval_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_assignment_events');

        Schema::table('document_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('document_assignments', 'reviewer_id')) {
                $table->dropForeign(['reviewer_id']);
            }

            foreach (['review_notes', 'reviewed_at', 'reviewer_id', 'approval_status'] as $column) {
                if (Schema::hasColumn('document_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_assignments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('company_id');
            $table->string('assignable_type');
            $table->unsignedInteger('assignable_id');
            $table->string('relation_type', 40);
            $table->string('status', 40)->default('active');
            $table->unsignedInteger('issuer_id')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('effective_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->date('renewal_due_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->date('revoked_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['document_id', 'deleted_at']);
            $table->index(['company_id', 'deleted_at']);
            $table->index(['assignable_type', 'assignable_id'], 'doc_assignments_assignable_idx');
            $table->index(['status', 'deleted_at']);
            $table->index('expires_at');
            $table->index('renewal_due_at');

            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('issuer_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_assignments');
    }
};

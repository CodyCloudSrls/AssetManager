<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalized mirror of Fatture in Cloud documents (issued + received). Fatture in
 * Cloud remains the fiscal source of truth; this read-only mirror feeds the ERP
 * analytical layer (revenue, receivables/payables, VAT, deadlines) without duplicating
 * fiscal logic. Rows are upserted idempotently on (fic_company_id, direction, fic_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fic_documents')) {
            return;
        }

        Schema::create('fic_documents', function (Blueprint $table) {
            $table->id();
            $table->string('fic_company_id', 32);
            $table->string('direction', 16);              // issued | received
            $table->unsignedBigInteger('fic_id');
            $table->string('doc_type', 64)->nullable();   // invoice, credit_note, expense, ...
            $table->string('number', 191)->nullable();
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->string('entity_name', 191)->nullable();
            $table->string('entity_vat', 64)->nullable();
            $table->decimal('amount_net', 15, 2)->default(0);
            $table->decimal('amount_vat', 15, 2)->default(0);
            $table->decimal('amount_gross', 15, 2)->default(0);
            $table->string('currency', 8)->default('EUR');
            $table->boolean('paid')->default(false);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->unsignedInteger('company_id')->nullable(); // local CodyCloud company mapping
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['fic_company_id', 'direction', 'fic_id'], 'fic_documents_unique');
            $table->index(['direction', 'paid']);
            $table->index('due_on');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fic_documents');
    }
};

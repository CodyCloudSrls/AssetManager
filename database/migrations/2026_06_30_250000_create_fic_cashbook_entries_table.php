<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persists individual FiC cashbook (prima nota) movements so incassi/pagamenti can be
 * reconciled per channel (TS Pay, Carta, PayPal, banche, ...) against the FiC document
 * each movement settled. Read-only mirror, upserted idempotently on (company, entry id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fic_cashbook_entries')) {
            return;
        }

        Schema::create('fic_cashbook_entries', function (Blueprint $table) {
            $table->id();
            $table->string('fic_company_id', 32);
            $table->string('fic_id', 64);                 // cashbook entry id (e.g. IDP...)
            $table->date('entry_date')->nullable();
            $table->string('direction', 8)->nullable();   // in | out
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('account_name', 191)->nullable();
            $table->string('account_id', 32)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('entity_name', 191)->nullable();
            $table->string('kind', 64)->nullable();
            $table->unsignedBigInteger('document_fic_id')->nullable();
            $table->string('document_type', 32)->nullable();
            $table->unsignedInteger('company_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['fic_company_id', 'fic_id']);
            $table->index(['fic_company_id', 'account_name']);
            $table->index('document_fic_id');
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fic_cashbook_entries');
    }
};

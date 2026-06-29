<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Notule" register: tracks amounts owed to / paid to professionals who have NOT yet
 * issued a fiscal invoice (compensi / pro-forma). They feed the management-control cost
 * picture as accruals; when the matching FiC received invoice arrives, the notula is
 * linked to it (fic_document_id) and marked "invoiced" so the cost is NOT double-counted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notule')) {
            return;
        }

        Schema::create('notule', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();      // optional link to a fornitore/professional
            $table->string('professional_name', 191)->nullable();    // free-text when no supplier
            $table->string('description', 191)->nullable();
            $table->decimal('amount', 15, 2)->default(0);            // compenso / imponibile
            $table->date('competence_date')->nullable();            // data di competenza
            $table->date('expected_invoice_date')->nullable();
            $table->string('status', 16)->default('pending');       // pending | invoiced | paid
            $table->date('paid_at')->nullable();
            $table->unsignedBigInteger('fic_document_id')->nullable(); // link to the real FiC invoice
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index('competence_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notule');
    }
};

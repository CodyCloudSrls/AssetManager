<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real bank/cash accounts (conti correnti) with balances derived from the FiC cashbook
 * (prima nota): balance = sum of all-time inflows − outflows per account. Feeds the
 * cassa / Posizione Finanziaria Netta with real numbers instead of a manual figure.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fic_payment_accounts')) {
            return;
        }

        Schema::create('fic_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('fic_company_id', 32);
            $table->string('name', 191);
            $table->decimal('balance', 15, 2)->default(0);
            $table->unsignedInteger('company_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['fic_company_id', 'name'], 'fic_payment_accounts_unique');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fic_payment_accounts');
    }
};

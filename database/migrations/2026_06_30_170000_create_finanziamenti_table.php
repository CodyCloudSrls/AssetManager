<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Financing / installment loans (e.g. the "telematica" rate-financing). Residuo is
 * computed = (rate_totali - rate_pagate) × rata_mensile. Feeds the Posizione
 * Finanziaria Netta (PFN) in the Fotografia Finanziaria. Manually maintained.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finanziamenti')) {
            return;
        }

        Schema::create('finanziamenti', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('nome', 191);
            $table->decimal('rata_mensile', 15, 2)->default(0);
            $table->unsignedSmallInteger('rate_totali')->default(0);
            $table->unsignedSmallInteger('rate_pagate')->default(0);
            $table->string('stato', 16)->default('confermato'); // confermato | da_confermare
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finanziamenti');
    }
};

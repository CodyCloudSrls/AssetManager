<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Official (deposited) yearly accounts — "storico bilanci". These are the AUTHORITATIVE
 * figures: when a deposited bilancio exists for a year, its values win over FiC actuals
 * and over the ×1,53 payroll estimates (the precedence rule from the Fotografia
 * Finanziaria). Entered manually from the deposited bilancio — never seeded with real
 * data (sensitive; the repo is public).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bilanci_ufficiali')) {
            return;
        }

        Schema::create('bilanci_ufficiali', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedSmallInteger('anno');
            $table->decimal('ricavi', 15, 2)->default(0);
            $table->decimal('costi', 15, 2)->default(0);
            $table->decimal('costo_personale', 15, 2)->default(0);
            $table->decimal('ammortamenti', 15, 2)->default(0);
            $table->decimal('utile', 15, 2)->default(0);
            $table->decimal('imposte', 15, 2)->default(0);
            $table->boolean('is_deposited')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'anno'], 'bilanci_ufficiali_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bilanci_ufficiali');
    }
};

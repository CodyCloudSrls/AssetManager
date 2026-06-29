<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Economic forecast (previsionale) per year — the FORECAST sheet of the Gestionale.
 * Manually maintained, retuned on the real run-rate. EBIT atteso is derived.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('previsionali')) {
            return;
        }

        Schema::create('previsionali', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedSmallInteger('anno');
            $table->decimal('ricavi', 15, 2)->default(0);
            $table->decimal('ricavi_ricorrente', 15, 2)->default(0);
            $table->decimal('cogs', 15, 2)->default(0);
            $table->decimal('opex', 15, 2)->default(0);
            $table->decimal('personale', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'anno'], 'previsionali_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previsionali');
    }
};

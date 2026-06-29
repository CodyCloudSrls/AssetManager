<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual financial inputs that cannot be derived from FiC (e.g. cassa/banca attuale for
 * the PFN). Simple key/value per company. Listed as "Per chiudere al 100%" in the
 * Fotografia Finanziaria.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('management_inputs')) {
            return;
        }

        Schema::create('management_inputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('key', 80);
            $table->decimal('value', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'key'], 'management_inputs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_inputs');
    }
};

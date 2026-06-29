<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reclassification map: FiC expense category -> management bucket (COGS / OPEX / LABOR
 * / MIXED). Drives the Conto Economico riclassificato. Editable ("Modifica Bucket per
 * ritarare" in the legacy Gestionale). Seeded with the real CodyCloud mapping; MIXED =
 * 70% COGS / 30% OPEX (the "Spese materiali" rule).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fic_cost_categories')) {
            return;
        }

        Schema::create('fic_cost_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('category', 191);
            $table->string('bucket', 16)->default('opex'); // cogs | opex | labor | mixed
            $table->timestamps();

            $table->unique(['company_id', 'category'], 'fic_cost_categories_unique');
        });

        $defaults = [
            'cogs' => ['Vendita Ingrosso', 'Spedizioni', 'Costi di vendita', 'Rifornimento per rivendita'],
            'mixed' => ['Spese materiali'],
            'labor' => ['Stipendi e salari'],
            'opex' => [
                'Spese Immateriali', 'Telefono e internet', 'Pranzo di lavoro',
                'Spese legali e contabili', 'Servizi aziendali', 'Servizi ed edifici',
                'Spese di marketing', 'Licenze', 'Assicurazioni e quote',
                'Auto ed altri veicoli', 'Spese bancarie', 'Costituzione',
            ],
        ];

        $rows = [];
        foreach ($defaults as $bucket => $categories) {
            foreach ($categories as $category) {
                $rows[] = ['company_id' => null, 'category' => $category, 'bucket' => $bucket, 'created_at' => now(), 'updated_at' => now()];
            }
        }
        DB::table('fic_cost_categories')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('fic_cost_categories');
    }
};

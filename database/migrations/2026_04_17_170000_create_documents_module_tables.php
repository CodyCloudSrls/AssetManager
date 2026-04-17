<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('document_frameworks');

        Schema::create('document_frameworks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('owner_id')->nullable();
            $table->unsignedInteger('document_type_id')->nullable();
            $table->unsignedInteger('document_framework_id')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->string('reference')->nullable();
            $table->string('version', 50)->nullable();
            $table->string('status', 40)->default('draft');
            $table->string('classification', 100)->nullable();
            $table->string('retention_period', 100)->nullable();
            $table->string('scope', 150)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('effective_at')->nullable();
            $table->date('next_review_at')->nullable();
            $table->string('control_url', 2048)->nullable();
            $table->text('summary')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'deleted_at']);
            $table->index('next_review_at');
            $table->index('document_number');

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('document_type_id')->references('id')->on('document_types')->nullOnDelete();
            $table->foreign('document_framework_id')->references('id')->on('document_frameworks')->nullOnDelete();
        });

        DB::table('document_frameworks')->insert([
            ['name' => 'Generale', 'slug' => 'general', 'description' => 'Framework o famiglia documentale generica.', 'sort_order' => 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'D.Lgs. 81/2008', 'slug' => 'dlgs-81-2008', 'description' => 'Documentazione salute e sicurezza sul lavoro.', 'sort_order' => 20, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GDPR', 'slug' => 'gdpr', 'description' => 'Documentazione privacy e protezione dati.', 'sort_order' => 30, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'NIS2', 'slug' => 'nis2', 'description' => 'Documentazione per governance e cybersicurezza NIS2.', 'sort_order' => 40, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AI Act', 'slug' => 'ai-act', 'description' => 'Documentazione per sistemi e processi AI.', 'sort_order' => 50, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('document_types')->insert([
            ['name' => 'Policy', 'slug' => 'policy', 'description' => 'Politiche aziendali o di controllo.', 'sort_order' => 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Procedura', 'slug' => 'procedure', 'description' => 'Procedure operative o di conformita.', 'sort_order' => 20, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Registro', 'slug' => 'register', 'description' => 'Registri obbligatori o di controllo.', 'sort_order' => 30, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Valutazione', 'slug' => 'assessment', 'description' => 'Assessment, analisi o valutazioni di rischio.', 'sort_order' => 40, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Piano', 'slug' => 'plan', 'description' => 'Piani di adeguamento, risposta o continuita.', 'sort_order' => 50, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Informativa', 'slug' => 'notice', 'description' => 'Informative e comunicazioni formali.', 'sort_order' => 60, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nomina', 'slug' => 'appointment', 'description' => 'Nomine, lettere di incarico e designazioni.', 'sort_order' => 70, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Verbale', 'slug' => 'minutes', 'description' => 'Verbali, registrazioni o consuntivi.', 'sort_order' => 80, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Evidenza', 'slug' => 'evidence', 'description' => 'Evidenze documentali e prove di controllo.', 'sort_order' => 90, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Inventario', 'slug' => 'inventory', 'description' => 'Inventari, elenchi o repertori documentali.', 'sort_order' => 100, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('document_frameworks');
    }
};

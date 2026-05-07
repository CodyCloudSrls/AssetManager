<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cpv_codes')) {
            Schema::create('cpv_codes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 10)->unique();
                $table->string('division_code', 2)->index();
                $table->string('description', 255);
                $table->string('source', 255)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'nis_assessment_method')) {
                $table->string('nis_assessment_method', 40)->default('not_assessed')->after('nis_assessment_status');
            }

            if (! Schema::hasColumn('suppliers', 'nis_assessment_outcome')) {
                $table->string('nis_assessment_outcome', 40)->default('not_assessed')->after('nis_assessment_method');
            }

            if (! Schema::hasColumn('suppliers', 'nis_assessment_scope')) {
                $table->text('nis_assessment_scope')->nullable()->after('nis_assessment_outcome');
            }
        });

        if (Schema::hasTable('cpv_codes')) {
            $now = now();
            $source = 'Commission Regulation (EC) No 213/2008 - CPV 2008';
            $rows = [
                ['code' => '30000000-9', 'division_code' => '30', 'description' => 'Office and computing machinery, equipment and supplies except furniture and software packages'],
                ['code' => '32000000-3', 'division_code' => '32', 'description' => 'Radio, television, communication, telecommunication and related equipment'],
                ['code' => '35000000-4', 'division_code' => '35', 'description' => 'Security, fire-fighting, police and defence equipment'],
                ['code' => '45000000-7', 'division_code' => '45', 'description' => 'Construction work'],
                ['code' => '48000000-8', 'division_code' => '48', 'description' => 'Software package and information systems'],
                ['code' => '48730000-4', 'division_code' => '48', 'description' => 'Security software package'],
                ['code' => '48800000-6', 'division_code' => '48', 'description' => 'Information systems and servers'],
                ['code' => '48900000-7', 'division_code' => '48', 'description' => 'Miscellaneous software package and computer systems'],
                ['code' => '50000000-5', 'division_code' => '50', 'description' => 'Repair and maintenance services'],
                ['code' => '64000000-6', 'division_code' => '64', 'description' => 'Postal and telecommunications services'],
                ['code' => '72000000-5', 'division_code' => '72', 'description' => 'IT services: consulting, software development, Internet and support'],
                ['code' => '72200000-7', 'division_code' => '72', 'description' => 'Software programming and consultancy services'],
                ['code' => '72300000-8', 'division_code' => '72', 'description' => 'Data services'],
                ['code' => '72400000-4', 'division_code' => '72', 'description' => 'Internet services'],
                ['code' => '72500000-0', 'division_code' => '72', 'description' => 'Computer-related services'],
                ['code' => '72600000-6', 'division_code' => '72', 'description' => 'Computer support and consultancy services'],
                ['code' => '72700000-7', 'division_code' => '72', 'description' => 'Computer network services'],
                ['code' => '72800000-8', 'division_code' => '72', 'description' => 'Computer audit and testing services'],
                ['code' => '72900000-9', 'division_code' => '72', 'description' => 'Computer back-up and catalogue conversion services'],
                ['code' => '79000000-4', 'division_code' => '79', 'description' => 'Business services: law, marketing, consulting, recruitment, printing and security'],
            ];

            DB::table('cpv_codes')->upsert(
                array_map(fn ($row) => $row + [
                    'source' => $source,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $rows),
                ['code'],
                ['division_code', 'description', 'source', 'is_active', 'updated_at']
            );
        }
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            foreach (['nis_assessment_scope', 'nis_assessment_outcome', 'nis_assessment_method'] as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('cpv_codes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_framework_requirement_parents')) {
            Schema::create('document_framework_requirement_parents', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('child_requirement_id');
                $table->unsignedInteger('parent_requirement_id');
                $table->timestamps();

                $table->unique(['child_requirement_id', 'parent_requirement_id'], 'doc_fw_req_parent_unique');
                $table->index('child_requirement_id', 'doc_fw_req_parent_child_idx');
                $table->index('parent_requirement_id', 'doc_fw_req_parent_parent_idx');

                $table->foreign('child_requirement_id', 'doc_fw_req_parent_child_fk')
                    ->references('id')->on('document_framework_requirements')->cascadeOnDelete();
                $table->foreign('parent_requirement_id', 'doc_fw_req_parent_parent_fk')
                    ->references('id')->on('document_framework_requirements')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('document_framework_requirements', 'parent_id')) {
            return;
        }

        DB::table('document_framework_requirements')
            ->whereNotNull('parent_id')
            ->whereColumn('id', '<>', 'parent_id')
            ->select(['id', 'parent_id'])
            ->orderBy('id')
            ->chunkById(500, function ($requirements) {
                $now = now();
                $rows = [];

                foreach ($requirements as $requirement) {
                    $rows[] = [
                        'child_requirement_id' => (int) $requirement->id,
                        'parent_requirement_id' => (int) $requirement->parent_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('document_framework_requirement_parents')->insertOrIgnore($rows);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_framework_requirement_parents');
    }
};

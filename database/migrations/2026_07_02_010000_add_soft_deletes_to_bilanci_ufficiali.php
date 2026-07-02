<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft-deletes for bilanci ufficiali, so they behave like every other file-supporting model
 * (the shared uploads mechanism resolves objects with withTrashed()). The uniqueness of
 * (company_id, anno) is folded to include deleted_at, so a year can be re-created after a
 * soft delete. Additive and nullable — existing data is untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bilanci_ufficiali', function (Blueprint $table) {
            if (! Schema::hasColumn('bilanci_ufficiali', 'deleted_at')) {
                $table->softDeletes();
            }
            $table->dropUnique('bilanci_ufficiali_unique');
            $table->unique(['company_id', 'anno', 'deleted_at'], 'bilanci_ufficiali_company_anno_deleted_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bilanci_ufficiali', function (Blueprint $table) {
            $table->dropUnique('bilanci_ufficiali_company_anno_deleted_unique');
            $table->unique(['company_id', 'anno'], 'bilanci_ufficiali_unique');
            if (Schema::hasColumn('bilanci_ufficiali', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

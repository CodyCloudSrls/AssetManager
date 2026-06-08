<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_services') || Schema::hasColumn('tenant_services', 'acn_subject_basis')) {
            return;
        }

        Schema::table('tenant_services', function (Blueprint $table) {
            $table->text('acn_subject_basis')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_services') || ! Schema::hasColumn('tenant_services', 'acn_subject_basis')) {
            return;
        }

        Schema::table('tenant_services', function (Blueprint $table) {
            $table->dropColumn('acn_subject_basis');
        });
    }
};

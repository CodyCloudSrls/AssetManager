<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A default custom fieldset per category: models under the category inherit it, so a
 * fieldset like "Asset Virtuali" (IP, domini, monitoraggio) is set once on the category
 * instead of on every model (IPv4, IPv6, .com, .eu, .it). Non-destructive: nullable, and
 * the asset custom-field resolution keeps using the model's own fieldset.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories') || Schema::hasColumn('categories', 'fieldset_id')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('fieldset_id')->nullable()->after('category_type');
            $table->index('fieldset_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'fieldset_id')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['fieldset_id']);
            $table->dropColumn('fieldset_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional link from a "Dominio" asset to the "Indirizzo IP" asset it lives on, so the
 * domain can inherit the IP's Hetrix custom-field status (one-way IP -> domain). Additive,
 * nullable: has no effect on any existing asset.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'linked_ip_asset_id')) {
                $table->unsignedBigInteger('linked_ip_asset_id')->nullable();
                $table->index('linked_ip_asset_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'linked_ip_asset_id')) {
                $table->dropIndex(['linked_ip_asset_id']);
                $table->dropColumn('linked_ip_asset_id');
            }
        });
    }
};

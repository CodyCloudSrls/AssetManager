<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_tenant_service')) {
            Schema::create('asset_tenant_service', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('asset_id');
                $table->unsignedInteger('tenant_service_id');
                $table->timestamps();

                $table->unique(['asset_id', 'tenant_service_id'], 'asset_tenant_service_unique');
                $table->index('tenant_service_id', 'asset_tenant_service_service_idx');

                $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
                $table->foreign('tenant_service_id')->references('id')->on('tenant_services')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_tenant_service');
    }
};

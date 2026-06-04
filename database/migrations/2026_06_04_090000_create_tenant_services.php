<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_services')) {
            Schema::create('tenant_services', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('tenant_id');
                $table->string('macro_area', 80);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('relevance_override', 40)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'macro_area', 'name', 'deleted_at'], 'tenant_services_tenant_macro_name_deleted_unique');
                $table->index(['tenant_id', 'is_active', 'deleted_at'], 'tenant_services_tenant_active_deleted_idx');
                $table->index(['macro_area', 'relevance_override'], 'tenant_services_macro_relevance_idx');

                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('document_tenant_service')) {
            Schema::create('document_tenant_service', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('document_id');
                $table->unsignedInteger('tenant_service_id');
                $table->timestamps();

                $table->unique(['document_id', 'tenant_service_id'], 'document_tenant_service_unique');
                $table->index('tenant_service_id', 'document_tenant_service_service_idx');

                $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
                $table->foreign('tenant_service_id')->references('id')->on('tenant_services')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('customer_contract_tenant_service')) {
            Schema::create('customer_contract_tenant_service', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('customer_contract_id');
                $table->unsignedInteger('tenant_service_id');
                $table->timestamps();

                $table->unique(['customer_contract_id', 'tenant_service_id'], 'customer_contract_tenant_service_unique');
                $table->index('tenant_service_id', 'customer_contract_tenant_service_service_idx');

                $table->foreign('customer_contract_id')->references('id')->on('customer_contracts')->cascadeOnDelete();
                $table->foreign('tenant_service_id')->references('id')->on('tenant_services')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contract_tenant_service');
        Schema::dropIfExists('document_tenant_service');
        Schema::dropIfExists('tenant_services');
    }
};

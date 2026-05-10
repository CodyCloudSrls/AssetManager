<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->string('name');
                $table->string('customer_number', 100)->nullable();
                $table->string('status', 40)->default('active');
                $table->string('vat_number', 50)->nullable();
                $table->string('tax_code', 50)->nullable();
                $table->string('address')->nullable();
                $table->string('address2')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country', 191)->nullable();
                $table->string('zip', 20)->nullable();
                $table->string('contact', 100)->nullable();
                $table->string('phone', 35)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('security_contact', 150)->nullable();
                $table->string('security_email', 150)->nullable();
                $table->string('url', 250)->nullable();
                $table->string('sector', 150)->nullable();
                $table->string('nis_profile', 60)->default('not_assessed');
                $table->string('nis_service_role', 60)->default('not_assessed');
                $table->string('nis_criticality', 40)->default('not_assessed');
                $table->text('nis_obligations')->nullable();
                $table->text('incident_notification_terms')->nullable();
                $table->text('sla_terms')->nullable();
                $table->text('audit_rights')->nullable();
                $table->date('nis_last_assessment_at')->nullable();
                $table->date('nis_next_review_at')->nullable();
                $table->string('image')->nullable();
                $table->string('tag_color', 10)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['company_id', 'name', 'deleted_at'], 'customers_company_name_deleted_unique');
                $table->index(['company_id', 'deleted_at'], 'customers_company_deleted_idx');
                $table->index(['status', 'deleted_at'], 'customers_status_deleted_idx');
                $table->index(['nis_profile', 'nis_next_review_at'], 'customers_nis_review_idx');

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('customer_contracts')) {
            Schema::create('customer_contracts', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('customer_id');
                $table->unsignedInteger('document_id')->nullable();
                $table->unsignedInteger('owner_id')->nullable();
                $table->string('name');
                $table->string('contract_number', 100)->nullable();
                $table->string('status', 40)->default('draft');
                $table->string('currency', 3)->default('EUR');
                $table->date('signed_at')->nullable();
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->date('renewal_due_at')->nullable();
                $table->date('notice_due_at')->nullable();
                $table->text('scope')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'deleted_at'], 'customer_contracts_company_deleted_idx');
                $table->index(['customer_id', 'deleted_at'], 'customer_contracts_customer_deleted_idx');
                $table->index(['status', 'ends_at'], 'customer_contracts_status_ends_idx');
                $table->index('renewal_due_at', 'customer_contracts_renewal_idx');

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
                $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
                $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('contract_subscriptions')) {
            Schema::create('contract_subscriptions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('customer_contract_id');
                $table->string('name');
                $table->string('service_code', 100)->nullable();
                $table->text('description')->nullable();
                $table->decimal('quantity', 14, 4)->default(1);
                $table->decimal('unit_price', 20, 4)->default(0);
                $table->string('billing_frequency', 40)->default('monthly');
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'deleted_at'], 'contract_subscriptions_company_deleted_idx');
                $table->index(['customer_contract_id', 'deleted_at'], 'contract_subscriptions_contract_deleted_idx');
                $table->index(['billing_frequency', 'is_active'], 'contract_subscriptions_frequency_active_idx');

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('customer_contract_id')->references('id')->on('customer_contracts')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('contract_cost_lines')) {
            Schema::create('contract_cost_lines', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('contract_subscription_id');
                $table->unsignedInteger('supplier_id')->nullable();
                $table->string('description');
                $table->decimal('quantity', 14, 4)->default(1);
                $table->decimal('unit_cost', 20, 4)->default(0);
                $table->string('cost_frequency', 40)->default('monthly');
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'deleted_at'], 'contract_cost_lines_company_deleted_idx');
                $table->index(['contract_subscription_id', 'deleted_at'], 'contract_cost_lines_subscription_deleted_idx');
                $table->index(['supplier_id', 'deleted_at'], 'contract_cost_lines_supplier_deleted_idx');

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('contract_subscription_id')->references('id')->on('contract_subscriptions')->cascadeOnDelete();
                $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('customer_contract_events')) {
            Schema::create('customer_contract_events', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('customer_contract_id');
                $table->unsignedInteger('company_id');
                $table->string('event_type', 60);
                $table->unsignedInteger('actor_id')->nullable();
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->text('note')->nullable();
                $table->ipAddress('remote_ip')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('hash_algorithm', 20)->nullable();
                $table->string('previous_hash', 64)->nullable();
                $table->string('payload_hash', 64)->nullable();
                $table->string('event_hash', 64)->nullable()->index('customer_contract_events_hash_idx');
                $table->timestamp('created_at')->nullable();

                $table->index(['customer_contract_id', 'created_at'], 'customer_contract_events_contract_idx');
                $table->index(['company_id', 'created_at'], 'customer_contract_events_company_idx');
                $table->index(['event_type', 'created_at'], 'customer_contract_events_type_idx');

                $table->foreign('customer_contract_id')->references('id')->on('customer_contracts')->cascadeOnDelete();
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contract_events');
        Schema::dropIfExists('contract_cost_lines');
        Schema::dropIfExists('contract_subscriptions');
        Schema::dropIfExists('customer_contracts');
        Schema::dropIfExists('customers');
    }
};

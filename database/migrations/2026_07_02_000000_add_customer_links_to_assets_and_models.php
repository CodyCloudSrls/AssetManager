<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links assets to a customer (the client that owns a domain/IP/virtual asset) and both
 * assets and models to a customer contract. New assets inherit the model's contract.
 * Additive and nullable, so existing production data is untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'customer_id')) {
                $table->unsignedInteger('customer_id')->nullable()->after('supplier_id');
                $table->index('customer_id', 'assets_customer_id_idx');
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            }
            if (! Schema::hasColumn('assets', 'customer_contract_id')) {
                $table->unsignedInteger('customer_contract_id')->nullable()->after('customer_id');
                $table->index('customer_contract_id', 'assets_customer_contract_id_idx');
                $table->foreign('customer_contract_id')->references('id')->on('customer_contracts')->nullOnDelete();
            }
        });

        Schema::table('models', function (Blueprint $table) {
            if (! Schema::hasColumn('models', 'customer_contract_id')) {
                $table->unsignedInteger('customer_contract_id')->nullable()->after('fieldset_id');
                $table->index('customer_contract_id', 'models_customer_contract_id_idx');
                $table->foreign('customer_contract_id')->references('id')->on('customer_contracts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'customer_contract_id')) {
                $table->dropForeign(['customer_contract_id']);
                $table->dropIndex('assets_customer_contract_id_idx');
                $table->dropColumn('customer_contract_id');
            }
            if (Schema::hasColumn('assets', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropIndex('assets_customer_id_idx');
                $table->dropColumn('customer_id');
            }
        });

        Schema::table('models', function (Blueprint $table) {
            if (Schema::hasColumn('models', 'customer_contract_id')) {
                $table->dropForeign(['customer_contract_id']);
                $table->dropIndex('models_customer_contract_id_idx');
                $table->dropColumn('customer_contract_id');
            }
        });
    }
};

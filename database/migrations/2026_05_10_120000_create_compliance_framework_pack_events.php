<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compliance_framework_pack_events')) {
            Schema::create('compliance_framework_pack_events', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('tenant_id')->nullable();
                $table->unsignedInteger('company_id')->nullable();
                $table->unsignedInteger('document_framework_id')->nullable();
                $table->string('scope', 40);
                $table->string('event_type', 60);
                $table->string('pack_key', 100);
                $table->string('pack_version', 80)->nullable();
                $table->string('pack_checksum', 64);
                $table->unsignedInteger('actor_id')->nullable();
                $table->longText('diff_before')->nullable();
                $table->longText('diff_after')->nullable();
                $table->longText('result_summary')->nullable();
                $table->ipAddress('remote_ip')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('hash_algorithm', 20)->nullable();
                $table->string('previous_hash', 64)->nullable();
                $table->string('payload_hash', 64)->nullable();
                $table->string('event_hash', 64)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['pack_key', 'created_at'], 'compliance_pack_events_pack_idx');
                $table->index(['tenant_id', 'created_at'], 'compliance_pack_events_tenant_idx');
                $table->index(['scope', 'event_type', 'created_at'], 'compliance_pack_events_scope_idx');
                $table->index('event_hash', 'compliance_pack_events_hash_idx');

                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                $table->foreign('document_framework_id')->references('id')->on('document_frameworks')->nullOnDelete();
                $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_framework_pack_events');
    }
};

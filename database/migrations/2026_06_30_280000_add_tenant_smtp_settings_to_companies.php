<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant SMTP on the tenant's root company: when configured, tenant emails go out
 * through the tenant's own mail server; otherwise they fall back to the platform mailer.
 * The password is stored encrypted (cast on the model). All nullable + non-destructive.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'tenant_mail_host')) {
                $table->string('tenant_mail_host', 191)->nullable()->after('tenant_mail_from_name');
                $table->unsignedSmallInteger('tenant_mail_port')->nullable()->after('tenant_mail_host');
                $table->string('tenant_mail_username', 191)->nullable()->after('tenant_mail_port');
                $table->text('tenant_mail_password')->nullable()->after('tenant_mail_username');
                $table->string('tenant_mail_encryption', 12)->nullable()->after('tenant_mail_password');
                $table->string('tenant_mail_from_email', 191)->nullable()->after('tenant_mail_encryption');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            foreach ([
                'tenant_mail_host', 'tenant_mail_port', 'tenant_mail_username',
                'tenant_mail_password', 'tenant_mail_encryption', 'tenant_mail_from_email',
            ] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

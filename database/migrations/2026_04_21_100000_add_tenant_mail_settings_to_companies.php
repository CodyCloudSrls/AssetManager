<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('tenant_notification_email', 500)->nullable()->after('helpdesk_contact_phone');
            $table->string('tenant_mail_reply_to_email', 150)->nullable()->after('tenant_notification_email');
            $table->string('tenant_mail_reply_to_name', 150)->nullable()->after('tenant_mail_reply_to_email');
            $table->string('tenant_mail_from_name', 150)->nullable()->after('tenant_mail_reply_to_name');
            $table->text('tenant_mail_notification_events')->nullable()->after('tenant_mail_from_name');
            $table->unsignedInteger('tenant_document_review_warning_days')->default(30)->after('tenant_mail_notification_events');
        });

        $defaultEvents = json_encode([
            Tenant::MAIL_EVENT_TICKET_CREATED,
            Tenant::MAIL_EVENT_TICKET_PUBLIC_REPLY,
            Tenant::MAIL_EVENT_TICKET_ASSIGNED,
            Tenant::MAIL_EVENT_TICKET_SLA_ALERT,
            Tenant::MAIL_EVENT_DOCUMENT_REVIEW_DUE,
        ]);

        $rootCompanies = DB::table('companies')
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->whereNotNull('tenant_id')
            ->get(['id', 'name', 'email', 'helpdesk_contact_email']);

        foreach ($rootCompanies as $company) {
            $notificationEmail = $company->helpdesk_contact_email ?: $company->email;

            DB::table('companies')
                ->where('id', $company->id)
                ->update([
                    'tenant_notification_email' => $notificationEmail,
                    'tenant_mail_reply_to_email' => $notificationEmail,
                    'tenant_mail_reply_to_name' => $company->name,
                    'tenant_mail_from_name' => $company->name,
                    'tenant_mail_notification_events' => $defaultEvents,
                    'tenant_document_review_warning_days' => 30,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'tenant_notification_email',
                'tenant_mail_reply_to_email',
                'tenant_mail_reply_to_name',
                'tenant_mail_from_name',
                'tenant_mail_notification_events',
                'tenant_document_review_warning_days',
            ]);
        });
    }
};

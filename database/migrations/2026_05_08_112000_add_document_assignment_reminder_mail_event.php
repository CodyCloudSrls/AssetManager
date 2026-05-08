<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'tenant_mail_notification_events')) {
            return;
        }

        $companies = DB::table('companies')
            ->whereNotNull('tenant_mail_notification_events')
            ->get(['id', 'tenant_mail_notification_events']);

        foreach ($companies as $company) {
            $events = json_decode((string) $company->tenant_mail_notification_events, true);

            if (! is_array($events)) {
                continue;
            }

            if (in_array(Tenant::MAIL_EVENT_DOCUMENT_ASSIGNMENT_REMINDER, $events, true)) {
                continue;
            }

            if (! in_array(Tenant::MAIL_EVENT_DOCUMENT_REVIEW_DUE, $events, true)) {
                continue;
            }

            $events[] = Tenant::MAIL_EVENT_DOCUMENT_ASSIGNMENT_REMINDER;

            DB::table('companies')
                ->where('id', $company->id)
                ->update(['tenant_mail_notification_events' => json_encode(array_values(array_unique($events)))]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'tenant_mail_notification_events')) {
            return;
        }

        $companies = DB::table('companies')
            ->whereNotNull('tenant_mail_notification_events')
            ->get(['id', 'tenant_mail_notification_events']);

        foreach ($companies as $company) {
            $events = json_decode((string) $company->tenant_mail_notification_events, true);

            if (! is_array($events)) {
                continue;
            }

            $events = array_values(array_filter(
                $events,
                fn ($event) => $event !== Tenant::MAIL_EVENT_DOCUMENT_ASSIGNMENT_REMINDER
            ));

            DB::table('companies')
                ->where('id', $company->id)
                ->update(['tenant_mail_notification_events' => json_encode($events)]);
        }
    }
};

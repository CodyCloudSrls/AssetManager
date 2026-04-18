<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('helpdesk_enabled')->default(false)->after('custom_css');
            $table->boolean('helpdesk_allow_attachments')->default(true)->after('helpdesk_enabled');
            $table->string('helpdesk_contact_email', 150)->nullable()->after('helpdesk_allow_attachments');
            $table->string('helpdesk_contact_phone', 35)->nullable()->after('helpdesk_contact_email');
            $table->text('helpdesk_intro')->nullable()->after('helpdesk_contact_phone');
            $table->text('helpdesk_privacy_note')->nullable()->after('helpdesk_intro');
        });

        Schema::create('tenant_helpdesk_ticket_types', function (Blueprint $table) {
            $table->unsignedInteger('tenant_id');
            $table->unsignedInteger('ticket_type_id');
            $table->timestamps();

            $table->primary(['tenant_id', 'ticket_type_id']);
            $table->index('ticket_type_id');
        });

        $now = now();
        $publicTypeIds = DB::table('ticket_types')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->where('is_public', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $rootCompanies = DB::table('companies')
            ->whereNull('deleted_at')
            ->whereNotNull('tenant_id')
            ->whereNull('parent_id')
            ->get(['id', 'tenant_id', 'email', 'phone']);

        foreach ($rootCompanies as $rootCompany) {
            DB::table('companies')
                ->where('id', $rootCompany->id)
                ->update([
                    'helpdesk_enabled' => true,
                    'helpdesk_allow_attachments' => true,
                    'helpdesk_contact_email' => $rootCompany->email,
                    'helpdesk_contact_phone' => $rootCompany->phone,
                ]);

            foreach ($publicTypeIds as $ticketTypeId) {
                DB::table('tenant_helpdesk_ticket_types')->updateOrInsert(
                    [
                        'tenant_id' => (int) $rootCompany->tenant_id,
                        'ticket_type_id' => (int) $ticketTypeId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_helpdesk_ticket_types');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'helpdesk_enabled',
                'helpdesk_allow_attachments',
                'helpdesk_contact_email',
                'helpdesk_contact_phone',
                'helpdesk_intro',
                'helpdesk_privacy_note',
            ]);
        });
    }
};

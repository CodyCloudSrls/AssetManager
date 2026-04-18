<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'helpdesk_slug')) {
                $table->string('helpdesk_slug', 80)->nullable()->unique()->after('helpdesk_allow_attachments');
            }
        });

        Company::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->whereNotNull('tenant_id')
            ->orderBy('id')
            ->get()
            ->each(function (Company $company) {
                if (blank($company->helpdesk_slug)) {
                    $company->helpdesk_slug = Company::generateUniqueHelpdeskSlug($company->name, $company->id);
                    $company->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'helpdesk_slug')) {
                $table->dropUnique(['helpdesk_slug']);
                $table->dropColumn('helpdesk_slug');
            }
        });
    }
};

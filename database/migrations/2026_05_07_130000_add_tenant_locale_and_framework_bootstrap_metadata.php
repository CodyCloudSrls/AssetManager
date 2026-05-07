<?php

use App\Helpers\Helper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultLocale = Helper::normalizeSupportedLocale(config('app.locale', 'en-US'));

        Schema::table('tenants', function (Blueprint $table) use ($defaultLocale) {
            if (! Schema::hasColumn('tenants', 'default_locale')) {
                $table->string('default_locale', 20)->default($defaultLocale)->after('uuid');
            }
        });

        DB::table('tenants')
            ->whereNull('default_locale')
            ->orWhere('default_locale', '')
            ->update(['default_locale' => $defaultLocale]);

        Schema::table('document_frameworks', function (Blueprint $table) {
            if (! Schema::hasColumn('document_frameworks', 'is_system_template')) {
                $table->boolean('is_system_template')->default(false)->after('visibility_type');
            }

            if (! Schema::hasColumn('document_frameworks', 'source_framework_id')) {
                $table->unsignedInteger('source_framework_id')->nullable()->after('is_system_template')->index();
            }

            if (! Schema::hasColumn('document_frameworks', 'source_pack_key')) {
                $table->string('source_pack_key', 80)->nullable()->after('source_framework_id')->index();
            }

            if (! Schema::hasColumn('document_frameworks', 'locale')) {
                $table->string('locale', 20)->nullable()->after('source_pack_key')->index();
            }
        });

        DB::table('document_frameworks')
            ->whereNull('company_id')
            ->where('slug', 'nis2-it-self-assessment')
            ->update([
                'is_system_template' => true,
                'visibility_type' => 'global',
                'source_pack_key' => 'nis2_it',
                'locale' => 'it-IT',
            ]);

        DB::table('document_frameworks')
            ->whereNull('company_id')
            ->where('slug', 'nis2-en-self-assessment')
            ->update([
                'is_system_template' => true,
                'visibility_type' => 'global',
                'source_pack_key' => 'nis2_en',
                'locale' => 'en-US',
            ]);

        DB::table('document_frameworks')
            ->whereNull('company_id')
            ->where('slug', 'gdpr-eu-evidence')
            ->update([
                'is_system_template' => true,
                'visibility_type' => 'global',
                'source_pack_key' => 'gdpr_eu',
                'locale' => 'it-IT',
            ]);

        DB::table('document_frameworks')
            ->whereNull('company_id')
            ->where('slug', 'gdpr-en-evidence')
            ->update([
                'is_system_template' => true,
                'visibility_type' => 'global',
                'source_pack_key' => 'gdpr_en',
                'locale' => 'en-US',
            ]);
    }

    public function down(): void
    {
        Schema::table('document_frameworks', function (Blueprint $table) {
            foreach (['locale', 'source_pack_key', 'source_framework_id', 'is_system_template'] as $column) {
                if (Schema::hasColumn('document_frameworks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'default_locale')) {
                $table->dropColumn('default_locale');
            }
        });
    }
};

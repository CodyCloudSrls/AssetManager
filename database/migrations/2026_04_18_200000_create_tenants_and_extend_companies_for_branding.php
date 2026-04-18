<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique();
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'tenant_id')) {
                $table->unsignedInteger('tenant_id')->nullable()->after('parent_id');
                $table->index('tenant_id', 'companies_tenant_id_index');
            }

            if (! Schema::hasColumn('companies', 'brand')) {
                $table->unsignedTinyInteger('brand')->nullable()->after('image');
            }

            if (! Schema::hasColumn('companies', 'brand_logo')) {
                $table->string('brand_logo')->nullable()->after('brand');
            }

            if (! Schema::hasColumn('companies', 'favicon')) {
                $table->string('favicon')->nullable()->after('brand_logo');
            }

            if (! Schema::hasColumn('companies', 'header_color')) {
                $table->string('header_color', 16)->nullable()->after('favicon');
            }

            if (! Schema::hasColumn('companies', 'nav_link_color')) {
                $table->string('nav_link_color', 16)->nullable()->after('header_color');
            }

            if (! Schema::hasColumn('companies', 'link_light_color')) {
                $table->string('link_light_color', 16)->nullable()->after('nav_link_color');
            }

            if (! Schema::hasColumn('companies', 'link_dark_color')) {
                $table->string('link_dark_color', 16)->nullable()->after('link_light_color');
            }

            if (! Schema::hasColumn('companies', 'footer_text')) {
                $table->text('footer_text')->nullable()->after('link_dark_color');
            }

            if (! Schema::hasColumn('companies', 'privacy_policy_link')) {
                $table->string('privacy_policy_link')->nullable()->after('footer_text');
            }

            if (! Schema::hasColumn('companies', 'custom_css')) {
                $table->longText('custom_css')->nullable()->after('privacy_policy_link');
            }
        });

        $settings = DB::table('settings')->first();
        $companies = DB::table('companies')->select('id', 'parent_id')->orderBy('id')->get();
        $parentMap = $companies->pluck('parent_id', 'id')->map(fn ($value) => is_null($value) ? null : (int) $value)->all();
        $tenantByRoot = [];

        foreach ($companies as $company) {
            $rootId = $this->resolveRootCompanyId((int) $company->id, $parentMap);

            if (! array_key_exists($rootId, $tenantByRoot)) {
                $tenantByRoot[$rootId] = DB::table('tenants')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach ($companies as $company) {
            $rootId = $this->resolveRootCompanyId((int) $company->id, $parentMap);
            $payload = [
                'tenant_id' => $tenantByRoot[$rootId],
            ];

            if ((int) $company->id === (int) $rootId && $settings) {
                $payload['brand'] = $settings->brand;
                $payload['brand_logo'] = $settings->logo;
                $payload['favicon'] = $settings->favicon;
                $payload['header_color'] = $settings->header_color;
                $payload['nav_link_color'] = $settings->nav_link_color;
                $payload['link_light_color'] = $settings->link_light_color;
                $payload['link_dark_color'] = $settings->link_dark_color;
                $payload['footer_text'] = $settings->footer_text;
                $payload['privacy_policy_link'] = $settings->privacy_policy_link;
                $payload['custom_css'] = $settings->custom_css;
            }

            DB::table('companies')->where('id', $company->id)->update($payload);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'custom_css')) {
                $table->dropColumn('custom_css');
            }

            if (Schema::hasColumn('companies', 'privacy_policy_link')) {
                $table->dropColumn('privacy_policy_link');
            }

            if (Schema::hasColumn('companies', 'footer_text')) {
                $table->dropColumn('footer_text');
            }

            if (Schema::hasColumn('companies', 'link_dark_color')) {
                $table->dropColumn('link_dark_color');
            }

            if (Schema::hasColumn('companies', 'link_light_color')) {
                $table->dropColumn('link_light_color');
            }

            if (Schema::hasColumn('companies', 'nav_link_color')) {
                $table->dropColumn('nav_link_color');
            }

            if (Schema::hasColumn('companies', 'header_color')) {
                $table->dropColumn('header_color');
            }

            if (Schema::hasColumn('companies', 'favicon')) {
                $table->dropColumn('favicon');
            }

            if (Schema::hasColumn('companies', 'brand_logo')) {
                $table->dropColumn('brand_logo');
            }

            if (Schema::hasColumn('companies', 'brand')) {
                $table->dropColumn('brand');
            }

            if (Schema::hasColumn('companies', 'tenant_id')) {
                $table->dropIndex('companies_tenant_id_index');
                $table->dropColumn('tenant_id');
            }
        });

        Schema::dropIfExists('tenants');
    }

    private function resolveRootCompanyId(int $companyId, array $parentMap): int
    {
        $current = $companyId;
        $visited = [];

        while (isset($parentMap[$current]) && ! is_null($parentMap[$current]) && ! in_array($current, $visited, true)) {
            $visited[] = $current;
            $parentId = $parentMap[$current];

            if (! array_key_exists($parentId, $parentMap)) {
                break;
            }

            $current = $parentId;
        }

        return $current;
    }
};

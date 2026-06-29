<?php

namespace Tests\Feature\Tenants;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantFeaturesTest extends TestCase
{
    public function test_null_features_means_every_module_enabled(): void
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);

        $this->assertNull($tenant->enabled_features);
        foreach (Tenant::featureKeys() as $key) {
            $this->assertTrue($tenant->hasFeature($key), "NULL features should enable {$key}");
        }
    }

    public function test_explicit_feature_list_gates_modules(): void
    {
        $tenant = Tenant::create([
            'uuid' => (string) Str::uuid(),
            'enabled_features' => [Tenant::FEATURE_NIS2],
        ]);

        $this->assertTrue($tenant->hasFeature(Tenant::FEATURE_NIS2));
        $this->assertTrue($tenant->hasFeature(Tenant::FEATURE_ASSETS), 'core assets is always on');
        $this->assertFalse($tenant->hasFeature(Tenant::FEATURE_DOCUMENTS));
        $this->assertFalse($tenant->hasFeature(Tenant::FEATURE_TICKETS));
        $this->assertFalse($tenant->hasFeature(Tenant::FEATURE_ERP));
    }

    public function test_config_save_persists_features_and_keeps_assets(): void
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        Company::factory()->create(['tenant_id' => $tenant->id, 'parent_id' => null, 'name' => 'Root '.Str::random(5)]);
        $this->actingAs(User::factory()->superuser()->create());

        $this->put(route('tenants.config.update', $tenant), [
            'default_locale' => 'en-US',
            'default_compliance_jurisdiction' => 'IT',
            'features' => [Tenant::FEATURE_ERP, Tenant::FEATURE_NIS2],
        ])->assertRedirect(route('tenants.config.edit', $tenant));

        $tenant->refresh();

        // assets is force-included even though the checkbox is disabled.
        $this->assertEqualsCanonicalizing(
            [Tenant::FEATURE_ASSETS, Tenant::FEATURE_NIS2, Tenant::FEATURE_ERP],
            $tenant->enabled_features
        );
        $this->assertTrue($tenant->hasFeature(Tenant::FEATURE_ERP));
        $this->assertFalse($tenant->hasFeature(Tenant::FEATURE_DOCUMENTS));
    }

    public function test_erp_landing_renders_when_feature_available(): void
    {
        // Superadmin has no single tenant context -> all features available.
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.index'))
            ->assertOk()
            ->assertSee(trans('erp/general.title'));
    }
}

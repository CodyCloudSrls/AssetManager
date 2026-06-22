<?php

namespace Tests\Feature\Companies\Ui;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChildCompanyJoinsParentTenantTest extends TestCase
{
    public function test_creating_a_company_with_a_parent_joins_the_parent_tenant(): void
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $mainCompany = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'parent_id' => null,
            'name' => 'Suez Italy',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('companies.store'), [
                'name' => 'Ecosistem',
                'parent_id' => $mainCompany->id,
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors();

        $child = Company::where('name', 'Ecosistem')->firstOrFail();

        // The child lands in the SAME tenant as its parent, so the tenant now has
        // two companies and per-company service categorization becomes available.
        $this->assertSame((int) $tenant->id, (int) $child->tenant_id);
        $this->assertSame((int) $mainCompany->id, (int) $child->parent_id);
    }
}

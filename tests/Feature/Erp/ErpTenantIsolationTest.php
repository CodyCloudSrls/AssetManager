<?php

namespace Tests\Feature\Erp;

use App\Models\BilancioUfficiale;
use App\Models\Company;
use App\Models\Notula;
use App\Models\User;
use Tests\TestCase;

/**
 * Guards against the cross-tenant IDOR on ERP financial records: route-model binding
 * resolves by primary key only, and the class-level authorize('update', CustomerContract)
 * check is NOT company-scoped, so without an explicit ownership guard a user holding the
 * contracts ability for their own company could edit/delete another company's records,
 * or forge company_id on write. See AppliesTenantCompanyFilter::assertCompanyAccessible /
 * resolveScopedCompanyId.
 */
class ErpTenantIsolationTest extends TestCase
{
    private function editorForCompany(int $companyId): User
    {
        // A normal ERP editor: holds the contracts ability but is NOT a superuser and is
        // pinned to a single company (so companyIds() resolves to [companyId]).
        return User::factory()->create([
            'company_id' => $companyId,
            'permissions' => json_encode(['contracts.view' => '1', 'contracts.edit' => '1']),
        ]);
    }

    public function test_user_cannot_edit_update_or_destroy_another_companys_bilancio(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editorA = $this->editorForCompany($companyA->id);

        $bilancioB = BilancioUfficiale::create(['company_id' => $companyB->id, 'anno' => 2024, 'ricavi' => 100]);

        $this->actingAs($editorA)->get(route('erp.bilanci.edit', $bilancioB))->assertForbidden();
        $this->actingAs($editorA)->put(route('erp.bilanci.update', $bilancioB), ['anno' => 2024, 'ricavi' => 999])->assertForbidden();
        $this->actingAs($editorA)->delete(route('erp.bilanci.destroy', $bilancioB))->assertForbidden();

        // Record belongs to B, untouched and not deleted.
        $this->assertDatabaseHas('bilanci_ufficiali', ['id' => $bilancioB->id, 'company_id' => $companyB->id, 'ricavi' => 100]);
    }

    public function test_company_id_is_clamped_to_callers_scope_on_store(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editorA = $this->editorForCompany($companyA->id);

        // The editor forges company_id = B; it must be clamped to their own company A.
        // (store() redirects to the edit page so the PDF can be attached right away.)
        $this->actingAs($editorA)->post(route('erp.bilanci.store'), [
            'anno' => 2024, 'ricavi' => 50, 'company_id' => $companyB->id,
        ])->assertStatus(302);

        $this->assertDatabaseHas('bilanci_ufficiali', ['anno' => 2024, 'company_id' => $companyA->id]);
        $this->assertDatabaseMissing('bilanci_ufficiali', ['anno' => 2024, 'company_id' => $companyB->id]);
    }

    public function test_notula_company_id_is_clamped_and_cross_tenant_edit_is_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editorA = $this->editorForCompany($companyA->id);

        // Forged company_id on store is clamped to A.
        $this->actingAs($editorA)->post(route('erp.notule.store'), [
            'professional_name' => 'Avv. Rossi', 'amount' => 200, 'company_id' => $companyB->id, 'status' => Notula::STATUS_UNPAID,
        ])->assertRedirect(route('erp.notule.index'));
        $this->assertDatabaseHas('notule', ['professional_name' => 'Avv. Rossi', 'company_id' => $companyA->id]);

        // Cannot touch another company's notula.
        $notulaB = Notula::create(['professional_name' => 'Dott. B', 'amount' => 300, 'company_id' => $companyB->id, 'status' => Notula::STATUS_UNPAID]);
        $this->actingAs($editorA)->get(route('erp.notule.edit', $notulaB))->assertForbidden();
        $this->actingAs($editorA)->delete(route('erp.notule.destroy', $notulaB))->assertForbidden();
        $this->assertDatabaseHas('notule', ['id' => $notulaB->id, 'company_id' => $companyB->id]);
    }

    public function test_superuser_is_not_restricted(): void
    {
        $companyB = Company::factory()->create();
        $bilancioB = BilancioUfficiale::create(['company_id' => $companyB->id, 'anno' => 2024, 'ricavi' => 100]);

        // A superuser has an unrestricted scope (null) and may manage any company's record.
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('erp.bilanci.edit', $bilancioB))
            ->assertOk();
    }
}

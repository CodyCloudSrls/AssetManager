<?php

namespace Tests\Feature\Contracts\Ui;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Tests\TestCase;

class BulkEditContractsTest extends TestCase
{
    private function contract(Company $company, User $creator, string $name, string $status = CustomerContract::STATUS_DRAFT, string $currency = 'EUR'): CustomerContract
    {
        $customer = new Customer([
            'company_id' => $company->id,
            'name' => $name.' Customer',
        ]);
        $customer->created_by = $creator->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $contract = new CustomerContract([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => $name,
            'status' => $status,
            'currency' => $currency,
        ]);
        $contract->created_by = $creator->id;
        $this->assertTrue($contract->save(), $contract->getErrors()->toJson());

        return $contract;
    }

    public function test_bulk_edit_applies_status_and_logs_an_audit_event_per_contract(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $a = $this->contract($company, $user, 'Bulk A');
        $b = $this->contract($company, $user, 'Bulk B');

        $this->actingAs($user)
            ->post(route('contracts.bulkeditsave'), [
                'ids' => [$a->id, $b->id],
                'apply_status' => '1',
                'status' => CustomerContract::STATUS_ACTIVE,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contracts.index'));

        $this->assertSame(CustomerContract::STATUS_ACTIVE, $a->fresh()->status);
        $this->assertSame(CustomerContract::STATUS_ACTIVE, $b->fresh()->status);

        foreach ([$a, $b] as $contract) {
            $this->assertDatabaseHas('customer_contract_events', [
                'customer_contract_id' => $contract->id,
                'event_type' => 'updated',
            ]);
        }
    }

    public function test_unapplied_fields_are_left_unchanged(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $user, 'Keep Currency', CustomerContract::STATUS_DRAFT, 'EUR');

        $this->actingAs($user)
            ->post(route('contracts.bulkeditsave'), [
                'ids' => [$contract->id],
                'apply_status' => '1',
                'status' => CustomerContract::STATUS_ACTIVE,
                // currency provided but NOT applied -> must stay EUR
                'currency' => 'USD',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $contract->fresh();
        $this->assertSame(CustomerContract::STATUS_ACTIVE, $fresh->status);
        $this->assertSame('EUR', $fresh->currency);
    }

    public function test_nothing_selected_returns_error_and_changes_nothing(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $user, 'No Apply');

        $this->actingAs($user)
            ->post(route('contracts.bulkeditsave'), [
                'ids' => [$contract->id],
                'status' => CustomerContract::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('bulk_actions');

        $this->assertSame(CustomerContract::STATUS_DRAFT, $contract->fresh()->status);
    }

    public function test_bulk_edit_form_renders_with_selected_contracts(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $a = $this->contract($company, $user, 'Render A');
        $b = $this->contract($company, $user, 'Render B');

        $this->actingAs($user)
            ->post(route('contracts.bulkedit'), [
                'ids' => [$a->id, $b->id],
                'bulk_actions' => 'edit',
            ])
            ->assertOk()
            ->assertSeeText('Render A')
            ->assertSeeText('Render B')
            ->assertSee('name="apply_status"', false)
            ->assertSee('name="apply_currency"', false);
    }

    public function test_contracts_index_renders_with_status_filter_and_bulk_toolbar(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $this->contract($company, $user, 'Index Render');

        $this->actingAs($user)
            ->get(route('contracts.index'))
            ->assertOk()
            ->assertSee('contractForm', false)
            ->assertSee('name="status"', false);
    }

    public function test_bulk_edit_requires_update_permission(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $owner, 'Locked');

        $restricted = User::factory()->for($company)->create([
            'permissions' => json_encode(['contracts.view' => '1']),
        ]);

        $this->actingAs($restricted)
            ->post(route('contracts.bulkeditsave'), [
                'ids' => [$contract->id],
                'apply_status' => '1',
                'status' => CustomerContract::STATUS_ACTIVE,
            ])
            ->assertForbidden();

        $this->assertSame(CustomerContract::STATUS_DRAFT, $contract->fresh()->status);
    }
}

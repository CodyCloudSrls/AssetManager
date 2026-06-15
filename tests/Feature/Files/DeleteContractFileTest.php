<?php

namespace Tests\Feature\Files;

use App\Models\Actionlog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DeleteContractFileTest extends TestCase
{
    private function contractWithFile(User $user, Company $company): array
    {
        $customer = new Customer(['company_id' => $company->id, 'name' => 'Del Customer']);
        $customer->created_by = $user->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $contract = new CustomerContract([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Del Contract',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ]);
        $contract->created_by = $user->id;
        $this->assertTrue($contract->save(), $contract->getErrors()->toJson());

        $this->actingAs($user)
            ->post(route('ui.files.store', ['object_type' => 'contracts', 'id' => $contract->id]), [
                'file' => [UploadedFile::fake()->create('to-delete.pdf', 40)],
            ]);

        $fileId = (int) Actionlog::query()
            ->where('item_type', CustomerContract::class)
            ->where('item_id', $contract->id)
            ->where('action_type', 'uploaded')
            ->latest('id')
            ->value('id');

        return [$contract, $fileId];
    }

    public function test_contract_file_can_be_deleted_via_the_contracts_route(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        [$contract, $fileId] = $this->contractWithFile($user, $company);

        $this->actingAs($user)
            ->delete(route('ui.files.destroy', ['object_type' => 'contracts', 'id' => $contract->id, 'file_id' => $fileId]))
            ->assertRedirect();

        $this->assertSame(0, $contract->fresh()->uploads()->count());
    }

    public function test_the_legacy_customercontracts_object_type_is_not_a_valid_route(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        [$contract, $fileId] = $this->contractWithFile($user, $company);

        // The delete button used to build /customercontracts/... which 404s;
        // the fix maps it to /contracts/...
        $this->actingAs($user)
            ->delete('/customercontracts/'.$contract->id.'/files/'.$fileId.'/delete')
            ->assertNotFound();
    }
}

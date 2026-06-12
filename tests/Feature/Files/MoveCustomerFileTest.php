<?php

namespace Tests\Feature\Files;

use App\Models\Actionlog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use App\Support\Files\FileIntegrity;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MoveCustomerFileTest extends TestCase
{
    private function customer(Company $company, User $creator, string $name): Customer
    {
        $customer = new Customer(['company_id' => $company->id, 'name' => $name]);
        $customer->created_by = $creator->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        return $customer;
    }

    private function contractFor(Customer $customer, User $creator): CustomerContract
    {
        $contract = new CustomerContract([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'name' => 'Move Contract',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ]);
        $contract->created_by = $creator->id;
        $this->assertTrue($contract->save(), $contract->getErrors()->toJson());

        return $contract;
    }

    private function uploadToCustomer(User $user, Customer $customer): int
    {
        $this->actingAs($user)
            ->post(route('ui.files.store', ['object_type' => 'customers', 'id' => $customer->id]), [
                'file' => [UploadedFile::fake()->create('contract-signed.pdf', 60)],
                'notes' => 'SG001/26',
            ]);

        return (int) Actionlog::query()
            ->where('item_type', Customer::class)
            ->where('item_id', $customer->id)
            ->where('action_type', 'uploaded')
            ->latest('id')
            ->value('id');
    }

    public function test_customer_attachment_moves_onto_the_contract(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $customer = $this->customer($company, $user, 'Move Customer');
        $contract = $this->contractFor($customer, $user);
        $fileId = $this->uploadToCustomer($user, $customer);

        $this->actingAs($user)
            ->post(route('contracts.files.move', $contract), ['file_id' => $fileId])
            ->assertRedirect(route('contracts.show', $contract).'#files');

        $this->assertDatabaseHas('action_logs', [
            'id' => $fileId,
            'item_type' => CustomerContract::class,
            'item_id' => $contract->id,
        ]);

        $this->assertSame(1, $contract->fresh()->uploads()->count());
        $this->assertSame(0, $customer->fresh()->uploads()->count());

        // File content is unchanged, so integrity still verifies at the new location.
        $this->assertTrue(FileIntegrity::verificationForLog(Actionlog::find($fileId))['verified']);
    }

    public function test_cannot_move_a_file_from_a_different_customer(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $contractCustomer = $this->customer($company, $user, 'Contract Customer');
        $otherCustomer = $this->customer($company, $user, 'Other Customer');
        $contract = $this->contractFor($contractCustomer, $user);
        $foreignFileId = $this->uploadToCustomer($user, $otherCustomer);

        $this->actingAs($user)
            ->post(route('contracts.files.move', $contract), ['file_id' => $foreignFileId])
            ->assertRedirect(route('contracts.show', $contract).'#files')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('action_logs', [
            'id' => $foreignFileId,
            'item_type' => Customer::class,
            'item_id' => $otherCustomer->id,
        ]);
    }
}

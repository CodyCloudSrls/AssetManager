<?php

namespace Tests\Feature\Contracts\Ui;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractAttachmentTest extends TestCase
{
    private function customer(Company $company, string $name): Customer
    {
        $c = new Customer(['company_id' => $company->id, 'name' => $name]);
        $c->save();

        return $c;
    }

    private function payload(Company $company, Customer $customer, array $extra = []): array
    {
        return array_merge([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Contratto allegato',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ], $extra);
    }

    public function test_contract_can_be_created_with_an_attachment(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();
        $customer = $this->customer($company, 'Cliente allegato');

        $this->post(route('contracts.store'), $this->payload($company, $customer, [
            'file' => [UploadedFile::fake()->create('contratto.pdf', 100, 'application/pdf')],
            'file_notes' => 'firmato',
        ]))->assertRedirect(route('contracts.index'));

        $contract = CustomerContract::where('name', 'Contratto allegato')->firstOrFail();
        $this->assertSame(1, $contract->uploads()->count());
    }

    public function test_attachment_added_on_edit_and_signed_p7m_is_accepted(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();
        $customer = $this->customer($company, 'Cliente edit');
        $contract = CustomerContract::create([
            'company_id' => $company->id, 'customer_id' => $customer->id,
            'name' => 'Contratto edit', 'status' => CustomerContract::STATUS_DRAFT, 'currency' => 'EUR',
        ]);

        // .p7m sniffs as octet-stream — must be accepted by extension from the contract form too.
        $this->put(route('contracts.update', $contract), $this->payload($company, $customer, [
            'name' => 'Contratto edit',
            'file' => [UploadedFile::fake()->create('firma.p7m', 50, 'application/octet-stream')],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, $contract->fresh()->uploads()->count());
    }
}

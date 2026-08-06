<?php

namespace Tests\Feature\Contracts\Ui;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A double/triple click of "Salva" used to create N identical contracts (and re-upload the
 * attachment N times). The one-time _submit_nonce makes the create idempotent server-side.
 */
class ContractDoubleSubmitTest extends TestCase
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
            'name' => 'Contratto test',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ], $extra);
    }

    public function test_same_nonce_twice_creates_a_single_contract_and_one_attachment(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();
        $customer = $this->customer($company, 'Cliente once');

        $payload = $this->payload($company, $customer, [
            '_submit_nonce' => 'nonce-fixed-123',
            'file' => [UploadedFile::fake()->create('contratto.pdf', 120, 'application/pdf')],
        ]);

        // First click creates it.
        $this->post(route('contracts.store'), $payload)->assertRedirect(route('contracts.index'));
        // Second click carries the SAME nonce (same rendered page) -> must be ignored.
        $this->post(route('contracts.store'), $this->payload($company, $customer, [
            '_submit_nonce' => 'nonce-fixed-123',
            'file' => [UploadedFile::fake()->create('contratto.pdf', 120, 'application/pdf')],
        ]))->assertRedirect(route('contracts.index'));

        $contracts = CustomerContract::where('name', 'Contratto test')->get();
        $this->assertCount(1, $contracts, 'the duplicate submit must not create a second contract');
        $this->assertSame(1, $contracts->first()->uploads()->count(), 'the attachment must not be re-uploaded');
        // The streamed file must be physically present at the expected key (a wrong store key
        // would still log the upload but leave a broken/missing attachment).
        $this->assertNotEmpty(
            Storage::disk('local')->files('private_uploads/contracts'),
            'the attachment must be physically stored under private_uploads/contracts'
        );
    }

    public function test_distinct_nonces_still_allow_two_deliberate_creations(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();
        $customer = $this->customer($company, 'Cliente due');

        $this->post(route('contracts.store'), $this->payload($company, $customer, ['name' => 'Uno', '_submit_nonce' => 'n-1']))
            ->assertRedirect(route('contracts.index'));
        $this->post(route('contracts.store'), $this->payload($company, $customer, ['name' => 'Due', '_submit_nonce' => 'n-2']))
            ->assertRedirect(route('contracts.index'));

        $this->assertSame(2, CustomerContract::whereIn('name', ['Uno', 'Due'])->count());
    }

    public function test_missing_nonce_is_never_blocked(): void
    {
        // Backward compatibility: a form/client without the hidden field behaves exactly as before.
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();
        $customer = $this->customer($company, 'Cliente nononce');

        $this->post(route('contracts.store'), $this->payload($company, $customer, ['name' => 'NoNonce']))
            ->assertRedirect(route('contracts.index'));
        $this->post(route('contracts.store'), $this->payload($company, $customer, ['name' => 'NoNonce']))
            ->assertRedirect(route('contracts.index'));

        $this->assertSame(2, CustomerContract::where('name', 'NoNonce')->count());
    }
}

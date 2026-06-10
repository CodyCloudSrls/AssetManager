<?php

namespace Tests\Feature\Contracts\Api;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContractFilesTest extends TestCase
{
    private function contract(Company $company, User $creator): CustomerContract
    {
        $customer = new Customer([
            'company_id' => $company->id,
            'name' => 'Files Customer',
        ]);
        $customer->created_by = $creator->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $contract = new CustomerContract([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Files Contract',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ]);
        $contract->created_by = $creator->id;
        $this->assertTrue($contract->save(), $contract->getErrors()->toJson());

        return $contract;
    }

    public function test_contract_api_accepts_uploads_lists_and_deletes_files(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $user);

        $this->actingAsForApi($user)
            ->post(
                route('api.files.store', ['object_type' => 'contracts', 'id' => $contract->id]),
                ['file' => [UploadedFile::fake()->create('contract.pdf', 100)], 'notes' => 'signed copy']
            )
            ->assertOk()
            ->assertJsonStructure(['status', 'messages']);

        $result = $this->actingAsForApi($user)
            ->getJson(route('api.files.index', ['object_type' => 'contracts', 'id' => $contract->id]))
            ->assertOk()
            ->assertJsonStructure(['rows', 'total'])
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.note', 'signed copy');

        $fileId = $result->decodeResponseJson()->json()['rows'][0]['id'];

        $this->actingAsForApi($user)
            ->get(route('api.files.show', ['object_type' => 'contracts', 'id' => $contract->id, 'file_id' => $fileId]))
            ->assertOk();

        $this->actingAsForApi($user)
            ->delete(route('api.files.destroy', ['object_type' => 'contracts', 'id' => $contract->id, 'file_id' => $fileId]))
            ->assertOk()
            ->assertJsonStructure(['status', 'messages']);
    }

    public function test_contract_file_upload_requires_contracts_files_permission(): void
    {
        $company = Company::factory()->create();
        $creator = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $creator);

        $restricted = User::factory()->for($company)->create([
            'permissions' => json_encode(['contracts.view' => '1']),
        ]);

        $this->actingAsForApi($restricted)
            ->post(
                route('api.files.store', ['object_type' => 'contracts', 'id' => $contract->id]),
                ['file' => [UploadedFile::fake()->create('contract.pdf', 100)]]
            )
            ->assertForbidden();
    }

    public function test_contract_view_shows_files_tab_for_permitted_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $user);

        $this->actingAs($user)
            ->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('uploadFileModal');
    }
}

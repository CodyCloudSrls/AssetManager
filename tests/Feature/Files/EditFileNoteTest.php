<?php

namespace Tests\Feature\Files;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EditFileNoteTest extends TestCase
{
    private function contract(Company $company, User $creator): CustomerContract
    {
        $customer = new Customer(['company_id' => $company->id, 'name' => 'Note Customer']);
        $customer->created_by = $creator->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $contract = new CustomerContract([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Note Contract',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ]);
        $contract->created_by = $creator->id;
        $this->assertTrue($contract->save(), $contract->getErrors()->toJson());

        return $contract;
    }

    private function uploadFileId(User $user, CustomerContract $contract, string $note): int
    {
        $this->actingAsForApi($user)
            ->post(route('api.files.store', ['object_type' => 'contracts', 'id' => $contract->id]), [
                'file' => [UploadedFile::fake()->create('doc.pdf', 50)],
                'notes' => $note,
            ])
            ->assertOk();

        return (int) $this->actingAsForApi($user)
            ->getJson(route('api.files.index', ['object_type' => 'contracts', 'id' => $contract->id]))
            ->assertOk()
            ->json('rows.0.id');
    }

    public function test_file_note_can_be_updated_without_deleting_the_file(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $user);
        $fileId = $this->uploadFileId($user, $contract, 'original note');

        $this->actingAsForApi($user)
            ->patchJson(route('api.files.update', ['object_type' => 'contracts', 'id' => $contract->id, 'file_id' => $fileId]), [
                'notes' => 'corrected note',
            ])
            ->assertOk();

        $this->assertDatabaseHas('action_logs', [
            'id' => $fileId,
            'note' => 'corrected note',
            'action_type' => 'uploaded',
        ]);

        // the file itself still lists (not deleted)
        $this->actingAsForApi($user)
            ->getJson(route('api.files.index', ['object_type' => 'contracts', 'id' => $contract->id]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.note', 'corrected note');
    }

    public function test_file_note_update_requires_files_permission(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->superuser()->for($company)->create();
        $contract = $this->contract($company, $owner);
        $fileId = $this->uploadFileId($owner, $contract, 'original note');

        $restricted = User::factory()->for($company)->create([
            'permissions' => json_encode(['contracts.view' => '1']),
        ]);

        $this->actingAsForApi($restricted)
            ->patchJson(route('api.files.update', ['object_type' => 'contracts', 'id' => $contract->id, 'file_id' => $fileId]), [
                'notes' => 'hacked',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('action_logs', ['id' => $fileId, 'note' => 'original note']);
    }
}

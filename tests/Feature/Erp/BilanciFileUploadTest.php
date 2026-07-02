<?php

namespace Tests\Feature\Erp;

use App\Models\Actionlog;
use App\Models\BilancioUfficiale;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BilanciFileUploadTest extends TestCase
{
    private function bilancio(int $anno = 2024): BilancioUfficiale
    {
        return BilancioUfficiale::create(['anno' => $anno, 'ricavi' => 100, 'is_deposited' => true]);
    }

    public function test_bilanci_index_create_and_edit_pages_render(): void
    {
        $admin = User::factory()->superuser()->create();
        $bilancio = $this->bilancio();

        $this->actingAs($admin)->get(route('erp.bilanci.index'))->assertOk();
        $this->actingAs($admin)->get(route('erp.bilanci.create'))->assertOk();
        $this->actingAs($admin)->get(route('erp.bilanci.edit', $bilancio))->assertOk();
    }

    public function test_creating_a_bilancio_lands_on_edit_so_the_pdf_can_be_attached(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('erp.bilanci.store'), ['anno' => 2023, 'ricavi' => 1000, 'is_deposited' => 1])
            ->assertRedirectContains('/edit');

        $this->assertDatabaseHas('bilanci_ufficiali', ['anno' => 2023]);
    }

    public function test_pdf_can_be_uploaded_to_a_bilancio(): void
    {
        Storage::fake('local');
        $bilancio = $this->bilancio();
        $file = UploadedFile::fake()->create('bilancio-2024.pdf', 120, 'application/pdf');

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('ui.files.store', ['object_type' => 'bilanci', 'id' => $bilancio->id]), [
                'file' => [$file],
                'notes' => 'Bilancio depositato 2024',
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors();

        $this->assertTrue($bilancio->uploads()->exists());
        $log = Actionlog::where('item_type', BilancioUfficiale::class)
            ->where('item_id', $bilancio->id)
            ->where('action_type', 'uploaded')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('Bilancio depositato 2024', $log->note);
    }

    public function test_upload_requires_the_contracts_files_ability(): void
    {
        Storage::fake('local');
        $bilancio = $this->bilancio();
        $file = UploadedFile::fake()->create('x.pdf', 10, 'application/pdf');

        // A user with no permissions cannot attach a bilancio PDF.
        $this->actingAs(User::factory()->create())
            ->post(route('ui.files.store', ['object_type' => 'bilanci', 'id' => $bilancio->id]), ['file' => [$file]])
            ->assertForbidden();

        $this->assertFalse($bilancio->uploads()->exists());
    }

    public function test_uploaded_bilancio_pdf_can_be_deleted(): void
    {
        Storage::fake('local');
        $bilancio = $this->bilancio();
        $admin = User::factory()->superuser()->create();

        $this->actingAs($admin)->post(route('ui.files.store', ['object_type' => 'bilanci', 'id' => $bilancio->id]), [
            'file' => [UploadedFile::fake()->create('bilancio-2024.pdf', 50, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $log = $bilancio->uploads()->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('ui.files.destroy', ['object_type' => 'bilanci', 'id' => $bilancio->id, 'file_id' => $log->id]))
            ->assertStatus(302);

        $this->assertFalse($bilancio->uploads()->where('action_logs.id', $log->id)->exists());
    }
}

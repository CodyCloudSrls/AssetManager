<?php

namespace Tests\Feature\Documents\Ui;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAttachmentTest extends TestCase
{
    public function test_document_can_be_created_with_an_attachment(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->superuser()->create());

        $this->post(route('documents.store'), [
            'name' => 'Doc con allegato',
            'status' => 'draft',
            'file' => [UploadedFile::fake()->create('contratto.pdf', 100, 'application/pdf')],
            'file_notes' => 'firmato',
        ])->assertRedirect();

        $document = Document::where('name', 'Doc con allegato')->firstOrFail();
        $this->assertSame(1, $document->uploads()->count());
    }

    public function test_attachment_can_be_added_when_editing(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->superuser()->create());

        $this->post(route('documents.store'), ['name' => 'Doc edit', 'status' => 'draft'])->assertRedirect();
        $document = Document::where('name', 'Doc edit')->firstOrFail();
        $this->assertSame(0, $document->uploads()->count());

        $this->put(route('documents.update', $document), [
            'name' => 'Doc edit',
            'status' => 'draft',
            'file' => [UploadedFile::fake()->create('extra.pdf', 50, 'application/pdf')],
        ])->assertRedirect();

        $this->assertSame(1, $document->fresh()->uploads()->count());
    }

    public function test_signed_p7m_attachment_is_accepted_in_the_document_form(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->superuser()->create());

        // A signed .p7m sniffs as application/octet-stream; validation must accept it by
        // extension (it is in the allowed list), not by sniffed MIME.
        $this->post(route('documents.store'), [
            'name' => 'Doc firmato p7m',
            'status' => 'draft',
            'file' => [UploadedFile::fake()->create('contratto.p7m', 100, 'application/octet-stream')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Document::where('name', 'Doc firmato p7m')->firstOrFail()->uploads()->count());
    }

    public function test_signed_p7m_is_accepted_by_the_generic_file_upload(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->superuser()->create());

        $this->post(route('documents.store'), ['name' => 'Doc p7m generico', 'status' => 'draft'])->assertRedirect();
        $document = Document::where('name', 'Doc p7m generico')->firstOrFail();

        // The generic "Caricamento file" modal (UploadFileRequest) — the path that errored.
        $this->post(route('ui.files.store', ['object_type' => 'documents', 'id' => $document->id]), [
            'file' => [UploadedFile::fake()->create('firma.p7m', 100, 'application/octet-stream')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $document->fresh()->uploads()->count());
    }

    public function test_disallowed_filetype_is_rejected_and_document_not_created(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->superuser()->create());

        $this->from(route('documents.create'))->post(route('documents.store'), [
            'name' => 'Doc bad file',
            'status' => 'draft',
            'file' => [UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload')],
        ])->assertSessionHasErrors('file.0');

        $this->assertDatabaseMissing('documents', ['name' => 'Doc bad file']);
    }
}

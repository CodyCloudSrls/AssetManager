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

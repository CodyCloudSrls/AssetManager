<?php

namespace Tests\Feature\Documents\Api;

use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentType;
use App\Models\User;
use Tests\TestCase;

class DocumentIndexTest extends TestCase
{
    public function test_api_index_returns_bootstrap_table_payload(): void
    {
        $actor = User::factory()->admin()->create();

        Document::query()->create([
            'name' => 'Registro trattamenti',
            'status' => Document::STATUS_ACTIVE,
            'document_type_id' => DocumentType::query()->firstOrFail()->id,
            'document_framework_id' => DocumentFramework::query()->firstOrFail()->id,
            'created_by' => $actor->id,
        ]);

        $this->actingAsForApi($actor)
            ->getJson(route('api.documents.index'))
            ->assertOk()
            ->assertJsonStructure(['total', 'rows'])
            ->assertJsonPath('total', 1);
    }
}

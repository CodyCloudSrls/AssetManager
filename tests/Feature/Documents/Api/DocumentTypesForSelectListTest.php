<?php

namespace Tests\Feature\Documents\Api;

use App\Models\Company;
use App\Models\DocumentType;
use App\Models\User;
use Tests\TestCase;

class DocumentTypesForSelectListTest extends TestCase
{
    public function test_document_type_selectlist_deduplicates_same_visible_name(): void
    {
        $company = Company::factory()->create();

        DocumentType::factory()->create([
            'name' => 'Policy',
            'slug' => 'policy-global',
            'company_id' => null,
            'visibility_type' => DocumentType::VISIBILITY_GLOBAL,
        ]);

        DocumentType::factory()->create([
            'name' => 'Policy',
            'slug' => 'policy-company',
            'company_id' => $company->id,
            'visibility_type' => DocumentType::VISIBILITY_PRIVATE,
        ]);

        $response = $this->actingAsForApi(User::factory()->superadmin()->create())
            ->getJson(route('api.documenttypes.selectlist', ['company_id' => $company->id]))
            ->assertOk();

        $texts = collect($response->json('results'))->pluck('text');

        $this->assertSame(1, $texts->filter(fn ($text) => $text === 'Policy')->count());
    }
}

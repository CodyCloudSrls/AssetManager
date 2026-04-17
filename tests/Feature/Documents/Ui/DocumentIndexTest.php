<?php

namespace Tests\Feature\Documents\Ui;

use App\Models\User;
use Tests\TestCase;

class DocumentIndexTest extends TestCase
{
    public function test_index_is_accessible_to_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('documentsListingTable');
    }
}

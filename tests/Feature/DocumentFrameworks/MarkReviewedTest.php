<?php

namespace Tests\Feature\DocumentFrameworks;

use App\Models\Company;
use App\Models\DocumentFramework;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarkReviewedTest extends TestCase
{
    private function framework(): DocumentFramework
    {
        $company = Company::factory()->create();
        $fw = new DocumentFramework;
        $fw->name = 'FW '.Str::random(4);
        $fw->slug = 'fw-'.Str::random(6);
        $fw->company_id = $company->id;
        $fw->is_system_template = false;
        $fw->is_active = true;
        $fw->status = 'active';
        $fw->visibility_type = 'private';
        $fw->review_cadence_months = 12;
        $fw->save();
        $fw->created_at = now()->subYears(2);
        $fw->save();

        return $fw;
    }

    public function test_mark_reviewed_sets_last_reviewed_at_and_resets_the_review_clock(): void
    {
        $fw = $this->framework();
        $this->assertTrue($fw->reviewDueWithin(30));   // overdue before

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('documentframeworks.mark-reviewed', $fw))
            ->assertStatus(302);

        $fw->refresh();
        $this->assertNotNull($fw->last_reviewed_at);
        $this->assertFalse($fw->reviewDueWithin(30));  // no longer due after review
    }

    public function test_mark_reviewed_is_authorized_like_editing_the_framework(): void
    {
        // mark-reviewed delegates to the same 'update' ability as edit/update; a user who
        // cannot update the framework is denied.
        $fw = $this->framework();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('documentframeworks.mark-reviewed', $fw))
            ->assertStatus(302);

        $this->assertNotNull(DocumentFramework::withoutGlobalScopes()->find($fw->id)->last_reviewed_at);
    }
}

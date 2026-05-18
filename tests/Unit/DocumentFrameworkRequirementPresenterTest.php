<?php

namespace Tests\Unit;

use App\Presenters\DocumentFrameworkRequirementPresenter;
use Tests\TestCase;

class DocumentFrameworkRequirementPresenterTest extends TestCase
{
    public function test_requirements_table_supports_bulk_selection_and_parent_column()
    {
        $layout = json_decode(DocumentFrameworkRequirementPresenter::dataTableLayout(), true);
        $fields = collect($layout)->pluck('field')->all();

        $this->assertContains('checkbox', $fields);
        $this->assertContains('parent_requirement_codes', $fields);

        $checkbox = collect($layout)->firstWhere('field', 'checkbox');
        $parentColumn = collect($layout)->firstWhere('field', 'parent_requirement_codes');

        $this->assertTrue($checkbox['checkbox']);
        $this->assertFalse($parentColumn['visible']);
    }
}

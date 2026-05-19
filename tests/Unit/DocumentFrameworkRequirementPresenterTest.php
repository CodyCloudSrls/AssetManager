<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DocumentFrameworkRequirementPresenterTest extends TestCase
{
    public function test_requirements_table_supports_bulk_selection_and_parent_column()
    {
        $presenter = $this->repoFile('app/Presenters/DocumentFrameworkRequirementPresenter.php');

        $this->assertStringContainsString("'field' => 'checkbox'", $presenter);
        $this->assertStringContainsString("'checkbox' => true", $presenter);
        $this->assertStringContainsString("'field' => 'parent_requirement_codes'", $presenter);
        $this->assertStringContainsString("'field' => 'minimum_required_documents'", $presenter);
        $this->assertStringContainsString("'formatter' => 'documentFrameworkRequirementDocumentsCountFormatter'", $presenter);
        $this->assertStringContainsString("'visible' => false", $presenter);
    }

    private function repoPath(string $relativePath): string
    {
        return dirname(__DIR__, 2).'/'.$relativePath;
    }

    private function repoFile(string $relativePath): string
    {
        $path = $this->repoPath($relativePath);

        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}

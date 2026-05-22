<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DocumentsBulkUiTest extends TestCase
{
    public function test_documents_table_supports_bulk_selection_and_actions(): void
    {
        $presenter = $this->repoFile('app/Presenters/DocumentPresenter.php');
        $table = $this->repoFile('resources/views/blade/table/documents.blade.php');
        $transformer = $this->repoFile('app/Http/Transformers/DocumentsTransformer.php');

        $this->assertStringContainsString("'field' => 'checkbox'", $presenter);
        $this->assertStringContainsString("'checkbox' => true", $presenter);
        $this->assertStringContainsString("route('documents.bulk.edit')", $table);
        $this->assertStringContainsString('Illuminate\Support\Str::camel($name) }}Toolbar', $table);
        $this->assertStringContainsString("trans('general.bulk_edit')", $table);
        $this->assertStringContainsString("trans('general.bulk_delete')", $table);
        $this->assertStringContainsString("'bulk_selectable'", $transformer);
    }

    public function test_documents_bulk_edit_supports_status_and_coverage_dates(): void
    {
        $controller = $this->repoFile('app/Http/Controllers/Documents/DocumentsController.php');
        $view = $this->repoFile('resources/views/documents/bulk-edit.blade.php');
        $routes = $this->repoFile('routes/web/documents.php');

        $this->assertStringContainsString("Route::match(['get', 'post'], 'bulk/edit'", $routes);
        $this->assertStringContainsString("Route::post('bulk/update'", $routes);
        $this->assertStringContainsString('session()->getOldInput', $controller);
        $this->assertStringContainsString("'apply_status' => 'nullable|boolean'", $controller);
        $this->assertStringContainsString("'status' => ['nullable', Rule::in(array_keys(Document::getStatusOptions()))]", $controller);
        $this->assertStringContainsString("'apply_effective_at' => 'nullable|boolean'", $controller);
        $this->assertStringContainsString("'next_review_at' => 'nullable|date_format:Y-m-d'", $controller);
        $this->assertStringContainsString('bulkApplyFields', $view);
        $this->assertStringContainsString("status: 'apply_status'", $view);
        $this->assertStringContainsString("effective_at: 'apply_effective_at'", $view);
        $this->assertStringContainsString("trans('admin/documents/form.status_help')", $view);
    }

    public function test_document_review_progress_uses_due_date_proximity(): void
    {
        $view = $this->repoFile('resources/views/documents/view.blade.php');

        $this->assertStringContainsString('$reviewHorizonDays = 365 * 3', $view);
        $this->assertStringContainsString('diffInDays($endDate, false)', $view);
        $this->assertStringContainsString('$reviewPercent = $datePercent($document->next_review_at)', $view);
        $this->assertStringContainsString('$renewalPercent = $datePercent($nextRenewalAssignment?->renewal_due_at)', $view);
        $this->assertStringNotContainsString('$reviewStart', $view);
    }

    public function test_requirement_coverage_counts_only_valid_current_primary_documents_with_files(): void
    {
        $model = $this->repoFile('app/Models/Document.php');
        $form = $this->repoFile('resources/lang/it-IT/admin/documents/form.php');

        $this->assertStringContainsString("where('documents.status', self::STATUS_ACTIVE)", $model);
        $this->assertStringContainsString('hasCoverageUpload()', $model);
        $this->assertStringContainsString('public function hasCoverageUpload(): bool', $model);
        $this->assertStringContainsString('coverage_uploads', $model);
        $this->assertStringContainsString("where('coverage_uploads.action_type', 'uploaded')", $model);
        $this->assertStringContainsString("where('coverage_upload_deletions.action_type', 'upload deleted')", $model);
        $this->assertStringContainsString('validi, in corso di validità e hanno almeno un allegato caricato', $form);
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

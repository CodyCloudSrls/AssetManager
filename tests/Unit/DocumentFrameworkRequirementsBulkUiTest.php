<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DocumentFrameworkRequirementsBulkUiTest extends TestCase
{
    public function test_bulk_actions_sync_selected_ids_from_table_state(): void
    {
        $partial = $this->repoFile('resources/views/partials/bootstrap-table.blade.php');

        $this->assertStringContainsString('window.snipeTableSyncBulkSelections', $partial);
        $this->assertStringContainsString("bootstrapTable('getSelections')", $partial);
        $this->assertStringContainsString('data-bulk-selection', $partial);
        $this->assertStringContainsString('submit.snipeBulkSelections', $partial);
        $this->assertStringContainsString("uniqueId: data_with_default('unique-id', 'id')", $partial);
        $this->assertStringContainsString('window.snipeTableSelectedBulkIds', $partial);
        $this->assertStringContainsString('input[name="btSelectItem"]:checked', $partial);
        $this->assertStringContainsString('window.snipeTableBulkSelectionButton', $partial);
        $this->assertStringContainsString('window.snipeTableSyncAllBulkSelections', $partial);
        $this->assertStringContainsString('window.snipeTableWriteBulkSelectionIds', $partial);
        $this->assertStringContainsString('window.snipeTableAddBulkSelectionIds', $partial);
        $this->assertStringContainsString('window.snipeTableRemoveBulkSelectionIds', $partial);
        $this->assertStringContainsString('window.snipeTableIdsFromRows', $partial);
        $this->assertStringContainsString('window.snipeTableVisibleBulkIds', $partial);
        $this->assertStringContainsString('window.snipeTableSyncVisibleBulkSelections', $partial);
        $this->assertStringContainsString('click.snipeBulkSelections change.snipeBulkSelections', $partial);
        $this->assertStringContainsString('input[data-index][type="checkbox"]:checked', $partial);
        $this->assertStringContainsString('.bootstrap-table tbody tr, .fixed-table-container tbody tr', $partial);
        $this->assertStringContainsString("$('.snipe-table').on('check.bs.table'", $partial);
        $this->assertStringContainsString("$('.snipe-table').on('uncheck.bs.table'", $partial);
        $this->assertStringNotContainsString("data('allow-empty-submit')", $partial);
    }

    public function test_bulk_actions_form_has_unique_container_and_form_ids(): void
    {
        $view = $this->repoFile('resources/views/blade/table/bulk-actions.blade.php');

        $this->assertStringContainsString('{{ $bulkActionId }}BulkActions', $view);
        $this->assertStringContainsString('{{ $bulkActionId }}Form', $view);
        $this->assertStringContainsString('type="submit"', $view);
        $this->assertStringContainsString("'name' => null", $view);
        $this->assertStringContainsString('disabled', $view);
        $this->assertStringNotContainsString('disable_until_selection', $view);
        $this->assertStringNotContainsString('data-allow-empty-submit', $view);
    }

    public function test_bulk_toolbar_ids_match_table_toolbar_selector(): void
    {
        $box = $this->repoFile('resources/views/blade/box/index.blade.php');
        $tab = $this->repoFile('resources/views/blade/tabs/pane.blade.php');
        $table = $this->repoFile('resources/views/blade/table/index.blade.php');

        $this->assertStringContainsString('}}Toolbar"', $box);
        $this->assertStringContainsString('}}Toolbar"', $tab);
        $this->assertStringContainsString('data-toolbar="#{{ Illuminate\Support\Str::camel($name) }}Toolbar"', $table);
        $this->assertStringContainsString('min-width:0', $box);
        $this->assertStringContainsString('min-width:0', $tab);
        $this->assertStringNotContainsString('}}ToolBar"', $box);
        $this->assertStringNotContainsString('}}ToolBar"', $tab);
        $this->assertStringNotContainsString('min-width:500px', $box);
        $this->assertStringNotContainsString('min-width:500px', $tab);
    }

    public function test_bulk_requirement_edit_prefills_parent_requirements_from_selected_rows(): void
    {
        $controller = $this->repoFile('app/Http/Controllers/DocumentFrameworkRequirementsController.php');
        $view = $this->repoFile('resources/views/documentframeworkrequirements/bulk-edit.blade.php');

        $this->assertStringContainsString('$selectedParentIds = $requirements', $controller);
        $this->assertStringContainsString("'selectedParentIds' => \$selectedParentIds", $controller);
        $this->assertStringContainsString("'framework' => fn (\$query) => \$query->withoutGlobalScopes()", $controller);
        $this->assertStringContainsString("old('parent_ids', \$selectedParentIds ?? [])", $view);
    }

    public function test_bulk_requirement_ui_auto_checks_apply_when_fields_change(): void
    {
        $bulkActions = $this->repoFile('resources/views/blade/table/documentframeworkrequirements.blade.php');
        $view = $this->repoFile('resources/views/documentframeworkrequirements/bulk-edit.blade.php');

        $this->assertStringContainsString('Illuminate\Support\Str::camel($name) }}Toolbar', $bulkActions);
        $this->assertStringContainsString(':$name', $bulkActions);
        $this->assertStringNotContainsString('<x-slot:bulkactions>', $bulkActions);
        $this->assertStringNotContainsString('disable_until_selection', $bulkActions);
        $this->assertStringContainsString('bulkApplyFields', $view);
        $this->assertStringContainsString("select[name=\"parent_ids[]\"]", $view);
        $this->assertStringContainsString('minimum_required_documents', $view);
        $this->assertStringContainsString('apply_minimum_required_documents', $view);
    }

    public function test_requirement_documents_count_formatter_marks_unsatisfied_minimum(): void
    {
        $partial = $this->repoFile('resources/views/partials/bootstrap-table.blade.php');

        $this->assertStringContainsString('documentFrameworkRequirementDocumentsCountFormatter', $partial);
        $this->assertStringContainsString('row.document_minimum_satisfied === false', $partial);
        $this->assertStringContainsString('text-danger', $partial);
        $this->assertStringContainsString('document_shortfall_count', $partial);
    }

    public function test_nis2_parent_references_do_not_use_transitive_cycle_block(): void
    {
        $request = $this->repoFile('app/Http/Requests/StoreDocumentFrameworkRequirementRequest.php');
        $controller = $this->repoFile('app/Http/Controllers/DocumentFrameworkRequirementsController.php');

        $this->assertStringContainsString("! \$framework?->isNis2Domain() && \$this->wouldCreateParentCycle", $request);
        $this->assertStringContainsString("! \$framework->isNis2Domain() && \$this->wouldCreateParentCycle", $controller);
        $this->assertStringContainsString('(int) $parentId === (int) $requirement->id', $controller);
        $this->assertStringContainsString('(int) $parentId === (int) $requirementId', $request);
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

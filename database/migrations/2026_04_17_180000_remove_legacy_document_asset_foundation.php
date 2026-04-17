<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $legacyColumns = [
        '_snipeit_document_type',
        '_snipeit_document_framework',
        '_snipeit_document_reference',
        '_snipeit_document_owner',
        '_snipeit_document_status',
        '_snipeit_document_version',
        '_snipeit_document_issue_date',
        '_snipeit_document_effective_date',
        '_snipeit_document_review_due',
        '_snipeit_document_control_id',
        '_snipeit_document_classification',
        '_snipeit_document_retention',
        '_snipeit_document_scope',
        '_snipeit_document_evidence_link',
    ];

    public function up(): void
    {
        $legacyModelId = DB::table('models')->where('name', 'Documento normativo')->value('id');
        $legacyCategoryId = DB::table('categories')->where('name', 'Documenti')->where('category_type', 'asset')->value('id');
        $legacyFieldsetId = DB::table('custom_fieldsets')->where('name', 'Registro documenti normativi')->value('id');
        $legacyFieldIds = DB::table('custom_fields')
            ->whereIn('db_column', $this->legacyColumns)
            ->pluck('id')
            ->all();

        if ($legacyModelId && DB::table('assets')->where('model_id', $legacyModelId)->exists()) {
            throw new RuntimeException('Legacy document asset model still has assets attached. Cleanup aborted.');
        }

        foreach ($this->legacyColumns as $column) {
            if (Schema::hasColumn('assets', $column) && DB::table('assets')->whereNotNull($column)->exists()) {
                throw new RuntimeException("Legacy document column {$column} still contains data. Cleanup aborted.");
            }
        }

        if (! empty($legacyFieldIds)) {
            DB::table('models_custom_fields')->whereIn('custom_field_id', $legacyFieldIds)->delete();
            DB::table('custom_field_custom_fieldset')->whereIn('custom_field_id', $legacyFieldIds)->delete();
            DB::table('custom_fields')->whereIn('id', $legacyFieldIds)->delete();
        }

        if ($legacyFieldsetId) {
            DB::table('custom_fieldsets')->where('id', $legacyFieldsetId)->delete();
        }

        if ($legacyModelId) {
            DB::table('models')->where('id', $legacyModelId)->delete();
        }

        if ($legacyCategoryId) {
            DB::table('categories')->where('id', $legacyCategoryId)->delete();
        }

        foreach ($this->legacyColumns as $column) {
            if (Schema::hasColumn('assets', $column)) {
                Schema::table('assets', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        // Intentionally left empty. The legacy bootstrap was replaced by the first-class documents module.
    }
};

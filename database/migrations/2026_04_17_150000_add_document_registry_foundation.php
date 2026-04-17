<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $categoryName = 'Documenti';
    private string $fieldsetName = 'Registro documenti normativi';
    private string $modelName = 'Documento normativo';

    public function up(): void
    {
        $createdBy = DB::table('users')->min('id') ?? 1;
        $now = now();

        $categoryId = DB::table('categories')
            ->where('name', $this->categoryName)
            ->where('category_type', 'asset')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => $this->categoryName,
                'category_type' => 'asset',
                'tag_color' => '#607d8b',
                'use_default_eula' => 0,
                'require_acceptance' => 0,
                'alert_on_response' => 0,
                'checkin_email' => 0,
                'notes' => 'Registro documentale per documenti normativi, policy, procedure, registri ed evidenze di compliance.',
                'created_by' => $createdBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $fieldsetId = DB::table('custom_fieldsets')
            ->where('name', $this->fieldsetName)
            ->value('id');

        if (! $fieldsetId) {
            $fieldsetId = DB::table('custom_fieldsets')->insertGetId([
                'name' => $this->fieldsetName,
                'created_by' => $createdBy,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($this->documentFieldDefinitions() as $order => $field) {
            if (! Schema::hasColumn('assets', $field['db_column'])) {
                Schema::table('assets', function (Blueprint $table) use ($field) {
                    $table->text($field['db_column'])->nullable();
                });
            }

            $fieldId = DB::table('custom_fields')
                ->where('name', $field['name'])
                ->value('id');

            if (! $fieldId) {
                $fieldId = DB::table('custom_fields')->insertGetId([
                    'name' => $field['name'],
                    'format' => $field['format'],
                    'element' => $field['element'],
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => $createdBy,
                    'field_values' => $field['field_values'],
                    'field_encrypted' => 0,
                    'db_column' => $field['db_column'],
                    'help_text' => $field['help_text'],
                    'show_in_email' => 0,
                    'show_in_requestable_list' => 0,
                    'is_unique' => 0,
                    'display_in_user_view' => 0,
                    'auto_add_to_fieldsets' => 0,
                    'show_in_listview' => $field['show_in_listview'],
                    'display_checkin' => 0,
                    'display_checkout' => 0,
                    'display_audit' => 0,
                ]);
            }

            DB::table('custom_field_custom_fieldset')->updateOrInsert(
                [
                    'custom_field_id' => $fieldId,
                    'custom_fieldset_id' => $fieldsetId,
                ],
                [
                    'order' => $order + 1,
                    'required' => $field['required'],
                ]
            );
        }

        $modelId = DB::table('models')
            ->where('name', $this->modelName)
            ->where('category_id', $categoryId)
            ->value('id');

        $modelPayload = [
            'model_number' => 'DOC-NORM',
            'min_amt' => 0,
            'manufacturer_id' => null,
            'category_id' => $categoryId,
            'require_serial' => 0,
            'depreciation_id' => null,
            'created_by' => $createdBy,
            'eol' => null,
            'image' => null,
            'fieldset_id' => $fieldsetId,
            'notes' => 'Model base per il censimento dei documenti normativi, procedurali e di compliance.',
            'requestable' => 0,
            'updated_at' => $now,
        ];

        if (! $modelId) {
            DB::table('models')->insert($modelPayload + [
                'name' => $this->modelName,
                'created_at' => $now,
            ]);
        } else {
            DB::table('models')
                ->where('id', $modelId)
                ->update($modelPayload);
        }
    }

    public function down(): void
    {
        $categoryId = DB::table('categories')
            ->where('name', $this->categoryName)
            ->where('category_type', 'asset')
            ->value('id');

        if ($categoryId) {
            DB::table('models')
                ->where('name', $this->modelName)
                ->where('category_id', $categoryId)
                ->delete();
        }

        $fieldsetId = DB::table('custom_fieldsets')
            ->where('name', $this->fieldsetName)
            ->value('id');

        foreach ($this->documentFieldDefinitions() as $field) {
            $fieldId = DB::table('custom_fields')
                ->where('name', $field['name'])
                ->value('id');

            if ($fieldId) {
                DB::table('models_custom_fields')->where('custom_field_id', $fieldId)->delete();

                if ($fieldsetId) {
                    DB::table('custom_field_custom_fieldset')
                        ->where('custom_field_id', $fieldId)
                        ->where('custom_fieldset_id', $fieldsetId)
                        ->delete();
                }

                DB::table('custom_fields')->where('id', $fieldId)->delete();
            }

            if (Schema::hasColumn('assets', $field['db_column'])) {
                Schema::table('assets', function (Blueprint $table) use ($field) {
                    $table->dropColumn($field['db_column']);
                });
            }
        }

        if ($fieldsetId) {
            DB::table('custom_fieldsets')->where('id', $fieldsetId)->delete();
        }

        if ($categoryId && DB::table('models')->where('category_id', $categoryId)->count() === 0) {
            DB::table('categories')->where('id', $categoryId)->delete();
        }
    }

    private function documentFieldDefinitions(): array
    {
        return [
            [
                'name' => 'Documento - Tipo',
                'db_column' => '_snipeit_document_type',
                'element' => 'listbox',
                'format' => 'ANY',
                'field_values' => implode("\n", [
                    'Policy',
                    'Procedura',
                    'Istruzione operativa',
                    'Registro',
                    'Valutazione / analisi',
                    'Piano',
                    'Nomina / incarico',
                    'Verbale / report',
                    'Modulo / template',
                    'Evidenza / attestazione',
                    'Contratto / accordo',
                    'Standard / linea guida',
                ]),
                'help_text' => 'Tipologia documentale generale.',
                'show_in_listview' => 1,
                'required' => 1,
            ],
            [
                'name' => 'Documento - Framework',
                'db_column' => '_snipeit_document_framework',
                'element' => 'listbox',
                'format' => 'ANY',
                'field_values' => implode("\n", [
                    'Generale',
                    'Dlgs 81/2008',
                    'GDPR',
                    'NIS2',
                    'AI Act',
                    'Privacy nazionale',
                    'Cybersecurity',
                    'ISO 27001 / 27002',
                    'ISO 22301',
                    'Multi-framework',
                    'Altro',
                ]),
                'help_text' => 'Framework o ambito normativo prevalente.',
                'show_in_listview' => 1,
                'required' => 1,
            ],
            [
                'name' => 'Documento - Riferimento',
                'db_column' => '_snipeit_document_reference',
                'element' => 'text',
                'format' => 'ANY',
                'field_values' => null,
                'help_text' => 'Articolo, controllo, misura o riferimento normativo principale.',
                'show_in_listview' => 1,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Proprietario',
                'db_column' => '_snipeit_document_owner',
                'element' => 'text',
                'format' => 'ANY',
                'field_values' => null,
                'help_text' => 'Funzione o owner responsabile del documento.',
                'show_in_listview' => 1,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Stato',
                'db_column' => '_snipeit_document_status',
                'element' => 'listbox',
                'format' => 'ANY',
                'field_values' => implode("\n", [
                    'Bozza',
                    'In revisione',
                    'Approvato',
                    'Da aggiornare',
                    'Obsoleto',
                    'Archiviato',
                ]),
                'help_text' => 'Stato del ciclo di vita del documento.',
                'show_in_listview' => 1,
                'required' => 1,
            ],
            [
                'name' => 'Documento - Versione',
                'db_column' => '_snipeit_document_version',
                'element' => 'text',
                'format' => 'ANY',
                'field_values' => null,
                'help_text' => 'Versione o revisione del documento.',
                'show_in_listview' => 1,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Data emissione',
                'db_column' => '_snipeit_document_issue_date',
                'element' => 'text',
                'format' => 'DATE',
                'field_values' => null,
                'help_text' => 'Data di emissione del documento.',
                'show_in_listview' => 0,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Data efficacia',
                'db_column' => '_snipeit_document_effective_date',
                'element' => 'text',
                'format' => 'DATE',
                'field_values' => null,
                'help_text' => 'Data da cui il documento e in vigore.',
                'show_in_listview' => 0,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Prossima revisione',
                'db_column' => '_snipeit_document_review_due',
                'element' => 'text',
                'format' => 'DATE',
                'field_values' => null,
                'help_text' => 'Data prevista per la prossima revisione.',
                'show_in_listview' => 1,
                'required' => 0,
            ],
            [
                'name' => 'Documento - ID documento',
                'db_column' => '_snipeit_document_control_id',
                'element' => 'text',
                'format' => 'ANY',
                'field_values' => null,
                'help_text' => 'Codice documento, control ID o riferimento interno.',
                'show_in_listview' => 1,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Classificazione',
                'db_column' => '_snipeit_document_classification',
                'element' => 'listbox',
                'format' => 'ANY',
                'field_values' => implode("\n", [
                    'Pubblico',
                    'Interno',
                    'Riservato',
                    'Confidenziale',
                ]),
                'help_text' => 'Classificazione di riservatezza del documento.',
                'show_in_listview' => 0,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Conservazione',
                'db_column' => '_snipeit_document_retention',
                'element' => 'listbox',
                'format' => 'ANY',
                'field_values' => implode("\n", [
                    'Finche vigente',
                    'Scadenza normativa',
                    'Scadenza contrattuale',
                    'Permanente',
                    'Storico',
                ]),
                'help_text' => 'Regola di conservazione o retention.',
                'show_in_listview' => 0,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Ambito',
                'db_column' => '_snipeit_document_scope',
                'element' => 'textarea',
                'format' => 'ANY',
                'field_values' => null,
                'help_text' => 'Processo, funzione, sede o perimetro a cui il documento si applica.',
                'show_in_listview' => 0,
                'required' => 0,
            ],
            [
                'name' => 'Documento - Link evidenza',
                'db_column' => '_snipeit_document_evidence_link',
                'element' => 'text',
                'format' => 'URL',
                'field_values' => null,
                'help_text' => 'URL al repository documentale o all evidenza primaria.',
                'show_in_listview' => 0,
                'required' => 0,
            ],
        ];
    }
};

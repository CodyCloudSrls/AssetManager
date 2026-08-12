<?php

use App\Models\CustomField;
use App\Models\CustomFieldset;
use Illuminate\Database\Migrations\Migration;

/**
 * FiC connector (P3) — custom fields on assets that link a cespite to its Fatture in Cloud
 * purchase document. Additive + reversible.
 *
 * The FIELDS (i.e. the `_snipeit_fic_*` columns on `assets`) are what the read-only FiC→Asset
 * connector will write to; they exist independently of any fieldset, so the connector works as
 * soon as this migration has run. The "FiC" fieldset just GROUPS them; it is intentionally NOT
 * attached to any asset model here — deciding which models expose these fields in the edit form
 * is a separate config choice, made later without touching schema.
 *
 * Idempotent (guards on field name) and reversible (down() drops the columns via the model).
 */
return new class extends Migration
{
    /** field name => [stored format ('' = ANY, 'date' = DATE), help text] */
    private function ficFields(): array
    {
        return [
            'fic_invoice_id' => ['', 'ID documento Fatture in Cloud — chiave di idempotenza del connettore (nessun duplicato).'],
            'fic_amount' => ['', 'Importo del documento FiC.'],
            'fic_payment_status' => ['', 'Stato pagamento FiC (es. not_paid / paid / partially_paid).'],
            'fic_supplier_taxcode' => ['', 'P.IVA / codice fiscale del fornitore FiC — chiave di match su suppliers.tax_code.'],
            'fic_doc_type' => ['', 'Tipo documento FiC (es. expense / invoice / credit_note).'],
            'fic_date' => ['date', 'Data del documento FiC.'],
        ];
    }

    public function up(): void
    {
        // Global visibility so the FiC fields apply to every company's assets (company_id NULL).
        $fieldset = CustomFieldset::where('name', 'FiC')->first();
        if (! $fieldset) {
            $fieldset = new CustomFieldset;
            $fieldset->name = 'FiC';
            $fieldset->company_id = null;
            $fieldset->visibility_type = 'global';
            $this->saveOrFail($fieldset, 'fieldset FiC');
        }

        $order = 1;
        foreach ($this->ficFields() as $name => [$format, $help]) {
            $field = CustomField::where('name', $name)->first();

            if (! $field) {
                $field = new CustomField;
                $field->name = $name;
                $field->element = 'text';
                $field->format = $format;              // '' => ANY, 'date' => DATE
                $field->field_encrypted = false;       // none of these are secrets
                $field->help_text = $help;
                $field->is_unique = false;
                $field->display_in_user_view = false;
                $field->auto_add_to_fieldsets = false;
                $field->show_in_listview = false;
                $field->company_id = null;
                $field->visibility_type = 'global';
                $field->created_by = 1;                // superadmin (codyadmin)
                $this->saveOrFail($field, 'field '.$name);   // model boot() adds the _snipeit_<slug> column
            }

            // Group under the FiC fieldset (idempotent, not required). Does NOT attach the
            // fieldset to any model — that stays a deliberate config decision.
            $fieldset->fields()->syncWithoutDetaching([
                $field->id => ['order' => $order, 'required' => 0],
            ]);
            $order++;
        }
    }

    /** Snipe's ValidatingTrait returns false (no throw) on invalid save — make failures loud. */
    private function saveOrFail($model, string $what): void
    {
        if (! $model->save()) {
            throw new RuntimeException('Impossibile creare '.$what.': '.$model->getErrors()->toJson());
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->ficFields()) as $name) {
            $field = CustomField::where('name', $name)->first();
            if ($field) {
                $field->delete();                      // model boot() drops the column + pivot rows
            }
        }

        $fieldset = CustomFieldset::where('name', 'FiC')->first();
        if ($fieldset && $fieldset->fields()->count() === 0) {
            $fieldset->delete();
        }
    }
};

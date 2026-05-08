<?php

return [
    'create' => [
        'success' => 'Document framework created successfully.',
    ],
    'update' => [
        'success' => 'Document framework updated successfully.',
    ],
    'delete' => [
        'success' => 'Document framework deleted successfully.',
        'associated_documents' => 'This document framework is still linked to one or more documents or requirements and cannot be deleted.',
    ],
    'restore' => [
        'success' => 'Document framework restored successfully.',
    ],
    'import' => [
        'success' => 'Document framework imported successfully with :count requirements.',
        'no_rows' => 'The file does not contain framework requirement rows.',
        'missing_columns' => 'The file is missing required columns: :columns.',
        'duplicate_columns' => 'The file contains the same mapped column more than once: :column.',
        'unsupported_file_type' => 'Unsupported framework file type: :type.',
        'duplicate_framework' => 'A document framework with this :column already exists for the selected company: :value.',
        'mixed_framework' => 'Row :row changes framework field :column. Import one framework per file.',
        'duplicate_requirement' => 'Requirement code :code appears more than once in the file.',
        'invalid_parent' => 'Parent requirement code :code does not match another imported requirement.',
        'invalid_enum' => 'Column :column contains an unsupported value on row :row.',
        'invalid_number' => 'Column :column must contain a valid number on row :row.',
        'invalid_boolean' => 'Column :column must contain a valid yes/no value on row :row.',
        'invalid_date' => 'Column :column must use YYYY-MM-DD on row :row.',
        'invalid_date_range' => 'The framework end date cannot be before the start date.',
        'invalid_url' => 'Column :column must contain a valid URL on row :row.',
        'invalid_required' => 'Column :column is required or too long on row :row.',
        'parse_error' => 'The framework file could not be read.',
        'save_failed' => 'The framework import could not be saved: :error',
    ],
    'export' => [
        'error' => 'The document framework could not be exported.',
    ],
];

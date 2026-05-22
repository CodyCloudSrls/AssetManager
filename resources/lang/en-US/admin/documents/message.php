<?php

return [
    'create' => [
        'success' => 'Document created successfully.',
    ],
    'update' => [
        'success' => 'Document updated successfully.',
    ],
    'assignment_create' => [
        'success' => 'Document assignment created successfully.',
    ],
    'assignment_update' => [
        'success' => 'Document assignment updated successfully.',
    ],
    'assignment_delete' => [
        'success' => 'Document assignment deleted successfully.',
    ],
    'framework_required_for_requirements' => 'Select a framework before mapping requirements to this document.',
    'invalid_requirements_for_framework' => 'One or more selected requirements do not belong to the chosen framework.',
    'invalid_bulk_documents' => 'One or more selected documents are not valid.',
    'bulk_action_invalid' => 'Select a valid bulk action.',
    'assignment_document_missing' => 'The source document for this assignment could not be resolved.',
    'assignment_requires_company' => 'Assign the document to a tenant company before linking it to people, assets, locations, or suppliers.',
    'assignment_target_invalid' => 'Select a valid person, asset, location, or supplier.',
    'assignment_target_wrong_tenant' => 'The selected target does not belong to the same tenant as the document.',
    'assignment_issuer_wrong_tenant' => 'The selected issuer does not belong to the same tenant as the document.',
    'assignment_save_document_first' => 'Save the document first. After the initial save you can link it to people, assets, locations, and suppliers.',
    'delete' => [
        'success' => 'Document deleted successfully.',
    ],
    'restore' => [
        'success' => 'Document restored successfully.',
    ],
    'force_delete' => [
        'action' => 'Permanently delete',
        'confirm' => 'Permanently delete this document? This cannot be undone.',
        'success' => 'Document permanently deleted successfully.',
        'not_deleted' => 'Only deleted documents can be permanently deleted.',
    ],
    'assignment_reviewer_wrong_tenant' => 'The selected reviewer does not belong to the same tenant as the document.',
];

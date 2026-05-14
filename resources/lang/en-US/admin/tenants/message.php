<?php

return [
    'create' => [
        'success' => 'Tenant created successfully.',
        'error' => 'Tenant could not be created.',
    ],
    'delete' => [
        'success' => 'Tenant deleted successfully.',
        'not_deletable' => 'This tenant cannot be deleted because it still contains operational data or tenant memberships.',
    ],
    'membership' => [
        'create' => [
            'success' => 'Tenant user assigned successfully.',
        ],
        'update' => [
            'success' => 'Tenant role updated successfully.',
        ],
        'delete' => [
            'success' => 'Tenant user removed successfully.',
        ],
    ],
    'helpdesk' => [
        'update' => [
            'success' => 'Helpdesk configuration updated successfully.',
        ],
        'disabled' => 'The public helpdesk portal is not enabled for this tenant.',
        'attachments_disabled' => 'Attachments are disabled for this tenant public helpdesk portal.',
        'no_public_types' => 'Enable at least one public ticket type before activating the helpdesk portal.',
    ],
    'mail' => [
        'update' => [
            'success' => 'Tenant mail settings updated successfully.',
        ],
    ],
    'settings' => [
        'update' => [
            'success' => 'Tenant settings updated successfully.',
        ],
        'bootstrap' => [
            'success' => 'Bootstrap completed for :locale: :frameworks framework(s) and :requirements requirement(s) created.',
            'safe_update_success' => 'Compliance pack safe update completed for :locale: :applied pack(s) applied, :frameworks framework(s) created, :requirements requirement(s) created, :manual_review manual review, :skipped skipped.',
        ],
    ],
];

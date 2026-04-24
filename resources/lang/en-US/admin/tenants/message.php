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
];

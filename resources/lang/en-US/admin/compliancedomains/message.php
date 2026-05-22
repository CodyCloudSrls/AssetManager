<?php

return [
    'create' => [
        'success' => 'Compliance domain created successfully.',
    ],
    'update' => [
        'success' => 'Compliance domain updated successfully.',
        'key_immutable' => 'The key cannot be changed because this is a system domain or it is already used by one or more frameworks.',
        'deactivation_blocked' => 'This compliance domain is still used by one or more frameworks and cannot be deactivated.',
    ],
    'delete' => [
        'success' => 'Compliance domain deleted successfully.',
        'associated_frameworks' => 'This compliance domain is still assigned to one or more frameworks and cannot be deleted.',
    ],
];

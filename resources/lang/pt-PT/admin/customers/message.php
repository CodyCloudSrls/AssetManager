<?php

return [
    'create' => [
        'success' => 'Customer created successfully.',
    ],
    'update' => [
        'success' => 'Customer updated successfully.',
    ],
    'delete' => [
        'success' => 'Customer deleted successfully.',
        'bulk_success' => 'Selected customers deleted successfully.',
        'partial_success' => ':count customer deleted.|:count customers deleted.',
        'not_found' => 'Customer not found.',
        'error' => 'The customer could not be deleted.',
        'associations' => 'The customer has linked contracts or document evidence and cannot be deleted.',
    ],
];

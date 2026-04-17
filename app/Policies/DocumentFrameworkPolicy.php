<?php

namespace App\Policies;

class DocumentFrameworkPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documentframeworks';
    }
}

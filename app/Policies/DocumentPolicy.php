<?php

namespace App\Policies;

class DocumentPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documents';
    }
}

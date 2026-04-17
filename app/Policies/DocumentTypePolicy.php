<?php

namespace App\Policies;

class DocumentTypePolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documenttypes';
    }
}

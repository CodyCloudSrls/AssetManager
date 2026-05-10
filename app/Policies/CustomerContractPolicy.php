<?php

namespace App\Policies;

class CustomerContractPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'contracts';
    }
}

<?php

namespace App\Policies;

/**
 * Bilanci ufficiali reuse the ERP "contracts" ability (same as the BilanciController
 * write gate), so users who manage ERP contracts can also view/attach bilancio PDFs.
 */
class BilancioUfficialePolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'contracts';
    }
}

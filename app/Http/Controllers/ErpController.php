<?php

namespace App\Http\Controllers;

use App\Models\CustomerContract;
use Illuminate\Contracts\View\View;

/**
 * ERP / Management-control module hub. Today it surfaces the existing Contracts
 * management; the financial-control modules (re-classified P&L, cash flow,
 * receivables/payables ageing, reconciliation, payroll cost, directional cockpit)
 * are being built on top, fed by the TeamSystem connectors (FiC / TS Pay /
 * Dipendenti in Cloud). Gated by the per-tenant "erp" feature flag.
 */
class ErpController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', CustomerContract::class);

        return view('erp.index');
    }
}

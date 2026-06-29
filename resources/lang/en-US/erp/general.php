<?php

return [
    'title' => 'ERP / Management control',
    'intro' => 'CodyCloud analytical/directional layer: contracts and — coming soon — management control (re-classified P&L, cash flow, deadlines, payment reconciliation) fed by the TeamSystem connectors (Fatture in Cloud, TS Pay, Dipendenti in Cloud). The fiscal layer stays on Fatture in Cloud.',
    'status_active' => 'Active',
    'status_planned' => 'Coming soon',
    'connectors_note' => 'The financial modules read data via API (Fatture in Cloud = fiscal source of truth, not duplicated) with idempotent sync. Development phases: connector + base cockpit, analytical accounting, cash flow, payroll cost.',
    'modules' => [
        'contracts' => 'Contracts',
        'contracts_help' => 'Customer contracts, deadlines and links to tenant services.',
        'pnl' => 'Re-classified P&L',
        'pnl_help' => 'COGS/OPEX/labour re-classification and analytical accounting by line / project / cost centre.',
        'cashflow' => 'Cash flow',
        'cashflow_help' => 'Cashbook, per-account balances and projection (FiC cashbook + payment accounts + TS Pay).',
        'deadlines' => 'Deadlines',
        'deadlines_help' => 'Fiscal (VAT, F24) and commercial (receivables/payables) deadlines with ageing and alerts.',
        'reconciliation' => 'Payment reconciliation',
        'reconciliation_help' => 'Multi-channel payments↔invoices matching (TS Pay, card, transfer, PayPal).',
        'cockpit' => 'Directional cockpit',
        'cockpit_help' => 'MRR/ARR, cash, receivables/payables, VAT balance, EBIT, leakage in real time.',
        'payroll' => 'Payroll cost',
        'payroll_help' => 'Payroll cost from Dipendenti in Cloud integrated into the P&L.',
    ],
];

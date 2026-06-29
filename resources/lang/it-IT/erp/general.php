<?php

return [
    'title' => 'ERP / Controllo di gestione',
    'intro' => 'Livello analitico e direzionale di CodyCloud: i contratti e — in arrivo — il controllo di gestione (conto economico riclassificato, flussi di cassa, scadenzario, riconciliazione incassi) alimentati dai connettori TeamSystem (Fatture in Cloud, TS Pay, Dipendenti in Cloud). Il livello fiscale resta su Fatture in Cloud.',
    'status_active' => 'Attivo',
    'status_planned' => 'In arrivo',
    'connectors_note' => 'I moduli finanziari leggono i dati via API (Fatture in Cloud = verità fiscale, non duplicata) con sync idempotente. Fasi di sviluppo: connettore + cruscotto base, contabilità analitica, flussi di cassa, costo del personale.',
    'modules' => [
        'contracts' => 'Contratti',
        'contracts_help' => 'Gestione contratti cliente, scadenze e collegamento ai servizi tenant.',
        'pnl' => 'Conto economico riclassificato',
        'pnl_help' => 'Riclassificazione COGS/OPEX/personale e contabilità analitica per linea / commessa / centro di costo.',
        'cashflow' => 'Flussi di cassa',
        'cashflow_help' => 'Prima nota, saldi per conto e proiezione (cashbook + payment accounts FiC + TS Pay).',
        'deadlines' => 'Scadenzario',
        'deadlines_help' => 'Scadenzario fiscale (IVA, F24) e commerciale (crediti/debiti) con aging e alert.',
        'reconciliation' => 'Riconciliazione incassi',
        'reconciliation_help' => 'Match incassi↔fatture multi-canale (TS Pay, carta, bonifico, PayPal).',
        'cockpit' => 'Cruscotto direzionale',
        'cockpit_help' => 'MRR/ARR, cassa, crediti/debiti, saldo IVA, EBIT, leakage in tempo reale.',
        'payroll' => 'Costo del personale',
        'payroll_help' => 'Costo del personale da Dipendenti in Cloud integrato nel conto economico.',
    ],
];

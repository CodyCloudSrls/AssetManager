<?php

return [
    'title' => 'Riconciliazione incassi',
    'intro' => 'Incassi reali dalla prima nota di Fatture in Cloud, raggruppati per canale (TS Pay, carta, PayPal, banche, ...) e collegati alla fattura che hanno saldato. Evidenzia gli incassi non ancora collegati a un documento.',
    'empty' => 'Nessun movimento di cassa sincronizzato. Esegui la sincronizzazione cassa (fic:sync-cassa).',
    'empty_year' => 'Nessun incasso per l\'anno selezionato.',

    'channel' => 'Canale',
    'movements' => 'Movimenti',
    'collected' => 'Incassato',
    'unmatched_count' => 'Non collegati',
    'unmatched_amount' => '€ non collegati',
    'detail' => 'Dettaglio',

    'to_reconcile' => 'Da riconciliare',
    'to_reconcile_help' => 'Incassi registrati in cassa ma non ancora collegati a una fattura: da verificare/abbinare in Fatture in Cloud.',
    'date' => 'Data',
    'amount' => 'Importo',
    'counterpart' => 'Controparte',
    'description' => 'Descrizione',

    'channel_detail' => 'Dettaglio canale — :channel',
    'matched_document' => 'Documento collegato',
    'matched' => 'Collegato',
    'not_matched' => 'Da collegare',
];

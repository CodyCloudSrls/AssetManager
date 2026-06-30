<?php

return [
    'title' => 'Incassi reconciliation',
    'intro' => 'Real money received from the Fatture in Cloud cashbook, grouped by channel (TS Pay, card, PayPal, banks, ...) and tied to the invoice each movement settled. Highlights incassi not yet linked to a document.',
    'empty' => 'No cashbook movements synced yet. Run the cash sync (fic:sync-cassa).',
    'empty_year' => 'No incassi for the selected year.',

    'channel' => 'Channel',
    'movements' => 'Movements',
    'collected' => 'Collected',
    'unmatched_count' => 'Unmatched',
    'unmatched_amount' => 'Unmatched €',
    'detail' => 'Detail',

    'to_reconcile' => 'To reconcile',
    'to_reconcile_help' => 'Money received in the cashbook but not yet linked to an invoice: review/match it in Fatture in Cloud.',
    'date' => 'Date',
    'amount' => 'Amount',
    'counterpart' => 'Counterpart',
    'description' => 'Description',

    'channel_detail' => 'Channel detail — :channel',
    'matched_document' => 'Linked document',
    'matched' => 'Linked',
    'not_matched' => 'To link',
];

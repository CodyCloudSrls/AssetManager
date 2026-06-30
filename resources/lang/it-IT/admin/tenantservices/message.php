<?php

return [
    'create' => [
        'success' => 'Servizio tenant creato correttamente.',
    ],
    'update' => [
        'success' => 'Servizio tenant aggiornato correttamente.',
    ],
    'delete' => [
        'success' => 'Servizio tenant eliminato correttamente.',
        'linked' => 'Questo servizio e collegato a documenti o contratti e non puo essere eliminato.',
    ],
    'bulk' => [
        'success' => 'Servizi tenant aggiornati correttamente.',
        'nothing_selected' => 'Nessun servizio selezionato.',
        'conflict' => 'Modifica non applicata: la combinazione macro-area + nome esiste già per una delle aziende selezionate.',
        'delete_success' => ':count servizi tenant eliminati correttamente.',
        'delete_partial' => ':deleted servizi eliminati. :skipped saltati perché collegati a documenti o contratti.',
        'delete_confirm' => 'Eliminare i servizi selezionati? I servizi collegati a documenti o contratti verranno saltati.',
    ],
];

<?php

return [
    'create' => [
        'success' => 'Tenant creato correttamente.',
        'error' => 'Impossibile creare il tenant.',
    ],
    'delete' => [
        'success' => 'Tenant eliminato correttamente.',
        'not_deletable' => 'Questo tenant non può essere eliminato perché contiene ancora dati operativi o membership tenant.',
    ],
    'membership' => [
        'create' => [
            'success' => 'Utente tenant assegnato correttamente.',
        ],
        'update' => [
            'success' => 'Ruolo tenant aggiornato correttamente.',
        ],
        'delete' => [
            'success' => 'Utente tenant rimosso correttamente.',
        ],
    ],
    'helpdesk' => [
        'update' => [
            'success' => 'Configurazione helpdesk aggiornata correttamente.',
        ],
        'disabled' => 'Il portale helpdesk pubblico non e attivo per questo tenant.',
        'attachments_disabled' => 'Gli allegati non sono consentiti nel portale helpdesk pubblico di questo tenant.',
        'no_public_types' => 'Se attivi il portale helpdesk devi esporre almeno una tipologia ticket pubblica.',
    ],
    'mail' => [
        'update' => [
            'success' => 'Impostazioni mail tenant aggiornate correttamente.',
        ],
    ],
];

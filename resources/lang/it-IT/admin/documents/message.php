<?php

return [
    'create' => [
        'success' => 'Documento creato correttamente.',
    ],
    'update' => [
        'success' => 'Documento aggiornato correttamente.',
    ],
    'assignment_create' => [
        'success' => 'Assegnazione documento creata correttamente.',
    ],
    'assignment_update' => [
        'success' => 'Assegnazione documento aggiornata correttamente.',
    ],
    'assignment_delete' => [
        'success' => 'Assegnazione documento eliminata correttamente.',
    ],
    'framework_required_for_requirements' => 'Seleziona un framework prima di collegare requisiti a questo documento.',
    'invalid_requirements_for_framework' => 'Uno o più requisiti selezionati non appartengono al framework scelto.',
    'invalid_bulk_documents' => 'Uno o più documenti selezionati non sono validi.',
    'bulk_action_invalid' => 'Seleziona un’azione massiva valida.',
    'assignment_document_missing' => 'Documento non trovato per questa assegnazione.',
    'assignment_requires_company' => 'Per assegnare un documento devi prima associarlo a un’azienda del tenant.',
    'assignment_target_invalid' => 'Seleziona una persona, un bene, una sede o un fornitore validi.',
    'assignment_target_wrong_tenant' => 'Il destinatario selezionato non appartiene allo stesso tenant del documento.',
    'assignment_issuer_wrong_tenant' => 'L’utente selezionato come emittente non appartiene allo stesso tenant del documento.',
    'assignment_save_document_first' => 'Salva prima il documento. Dopo il primo salvataggio potrai collegarlo a persone, beni, sedi e fornitori.',
    'delete' => [
        'success' => 'Documento eliminato correttamente.',
    ],
    'restore' => [
        'success' => 'Documento ripristinato correttamente.',
    ],
    'force_delete' => [
        'action' => 'Elimina definitivamente',
        'confirm' => 'Eliminare definitivamente questo documento? L azione non puo essere annullata.',
        'success' => 'Documento eliminato definitivamente.',
        'not_deleted' => 'Solo i documenti gia eliminati possono essere eliminati definitivamente.',
    ],
    'assignment_reviewer_wrong_tenant' => 'L’utente selezionato come revisore non appartiene allo stesso tenant del documento.',
];

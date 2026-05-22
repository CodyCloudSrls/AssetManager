<?php

return [
    'create' => [
        'success' => 'Framework documentale creato correttamente.',
    ],
    'update' => [
        'success' => 'Framework documentale aggiornato correttamente.',
    ],
    'delete' => [
        'success' => 'Framework documentale eliminato correttamente.',
        'associated_documents' => 'Questo framework documentale è ancora collegato a uno o più documenti o requisiti e non può essere eliminato.',
    ],
    'purge_unused_bootstrap' => [
        'success' => 'Copia bootstrap inutilizzata rimossa: :frameworks framework e :requirements requisiti eliminati.',
        'blocked' => 'Questo framework non può essere pulito perché non è una copia bootstrap tenant inutilizzata o ha documenti/evidenze collegate.',
    ],
    'restore' => [
        'success' => 'Framework documentale ripristinato correttamente.',
    ],
    'import' => [
        'success' => 'Framework documentale importato correttamente con :count requisiti.',
        'no_rows' => 'Il file non contiene righe di requisiti del framework.',
        'missing_columns' => 'Nel file mancano colonne obbligatorie: :columns.',
        'duplicate_columns' => 'Il file contiene la stessa colonna mappata più di una volta: :column.',
        'unsupported_file_type' => 'Tipo di file framework non supportato: :type.',
        'duplicate_framework' => 'Esiste già un framework documentale con questo :column per l’azienda selezionata: :value.',
        'mixed_framework' => 'La riga :row modifica il campo framework :column. Importa un solo framework per file.',
        'duplicate_requirement' => 'Il codice requisito :code compare più di una volta nel file.',
        'invalid_parent' => 'Il codice requisito padre :code non corrisponde a un altro requisito importato.',
        'invalid_enum' => 'La colonna :column contiene un valore non supportato alla riga :row.',
        'invalid_number' => 'La colonna :column deve contenere un numero valido alla riga :row.',
        'invalid_boolean' => 'La colonna :column deve contenere un valore sì/no valido alla riga :row.',
        'invalid_date' => 'La colonna :column deve usare il formato AAAA-MM-GG alla riga :row.',
        'invalid_date_range' => 'La data fine del framework non può precedere la data inizio.',
        'invalid_url' => 'La colonna :column deve contenere un URL valido alla riga :row.',
        'invalid_required' => 'La colonna :column è obbligatoria o troppo lunga alla riga :row.',
        'parse_error' => 'Impossibile leggere il file framework.',
        'save_failed' => 'Impossibile salvare l’import del framework: :error',
    ],
    'export' => [
        'error' => 'Impossibile esportare il framework documentale.',
    ],
];

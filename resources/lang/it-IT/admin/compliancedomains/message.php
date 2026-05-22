<?php

return [
    'create' => [
        'success' => 'Dominio compliance creato correttamente.',
    ],
    'update' => [
        'success' => 'Dominio compliance aggiornato correttamente.',
        'key_immutable' => 'La chiave non puo essere modificata perche il dominio e di sistema o e gia usato da uno o piu framework.',
        'deactivation_blocked' => 'Questo dominio compliance e ancora usato da uno o piu framework e non puo essere disattivato.',
    ],
    'delete' => [
        'success' => 'Dominio compliance eliminato correttamente.',
        'associated_frameworks' => 'Questo dominio compliance e ancora assegnato a uno o piu framework e non puo essere eliminato.',
    ],
];

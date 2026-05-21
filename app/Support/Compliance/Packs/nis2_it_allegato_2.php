<?php

return [
  0 =>
  [
    'code' => 'GV.SC-01 punto 1',
    'title' => 'Processi di approvvigionamento allineati alla NIS2 e coinvolgendo l\'organizzazione per  la sicurezza informatica',
    'domain' => 'Gestione del rischio di cybersecurity della catena di approvvigionamento (GV.SC)',
    'obligation_type' => 'supply_chain',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 0,
    'description' => 'Sono stabiliti e accettati dagli stakeholder dell\'organizzazione il programma, la strategia, obiettivi, politiche e processi di gestione del rischio di cybersecurity della catena di approvvigionamento.

1. In merito all’affidamento di forniture con potenziali impatti sulla sicurezza dei sistemi informativi e di rete, anche mediante ricorso agli strumenti delle centrali di committenza di cui all’allegato I.1, articolo 1, comma 1, lettera i), del decreto legislativo 31 marzo 2023, n. 36, sono previsti:

a) il coinvolgimento dell’organizzazione per la sicurezza informatica di cui alla misura GV.RR-02 nella definizione ed esecuzione dei processi di approvvigionamento a partire dalla fase di identificazione e progettazione della fornitura;

b) in accordo agli esiti della valutazione del rischio associato alla fornitura di cui alla misura GV.SC-07, la definizione di requisiti di sicurezza sulla fornitura coerenti con le misure di sicurezza applicate dal soggetto NIS ai sistemi informativi e di rete.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
    ],
  ],
  1 =>
  [
    'code' => 'ID.RA-08 punto 5',
    'title' => 'Monitoraggio dei canali dei fornitori di software critico',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 0,
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Procedura',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-08 punto 3',
    ],
  ],
  2 =>
  [
    'code' => 'PR.IR-01 punto 3',
    'title' => 'Presenza mantenuta e aggiornata di firewall e difese perimetrali',
    'domain' => 'Resilienza dell\'infrastruttura tecnologica (PR.IR)',
    'obligation_type' => 'asset_inventory',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 0,
    'description' => '3. Sono presenti, aggiornati, mantenuti e configurati i sistemi perimetrali, quali firewall.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Inventario',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.AM-01 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  3 =>
  [
    'code' => 'DE.CM-01 punto 1',
    'title' => 'Presenza di strumenti per la notifica tempestiva degli incidenti (SIEM e mail)',
    'domain' => 'Monitoraggio continuo (DE.CM)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => 'Le reti e i servizi di rete sono monitorati per individuare eventi potenzialmente avversi.

1. Per almeno i sistemi informativi e di rete rilevanti, sono presenti, aggiornati, mantenuti e configurati in modo adeguato strumenti tecnici per rilevare tempestivamente gli incidenti significativi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'ID.RA-08 punto 3',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  4 =>
  [
    'code' => 'GV.RR-02 punto 1',
    'title' => 'Organizzazione per la sicurezza informatica',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 1,
    'description' => 'I ruoli, le responsabilità e i correlati poteri relativi alla gestione del rischio di cybersecurity sono stabiliti, comunicati, compresi e applicati.

1. È definita, approvata dagli organi di amministrazione e direttivi, e resa nota alle articolazioni competenti del soggetto NIS, l\'organizzazione per la sicurezza informatica e ne sono stabiliti ruoli e responsabilità.',
    'evidence_guidance' => 'se stesso.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Nomina',
    'is_mandatory' => true,
    'is_active' => true,
  ],
  5 =>
  [
    'code' => 'GV.RR-02 punto 2',
    'title' => 'Elenco aggiornato del personale dell\'organizzazione avente specifici ruoli e responsabilità',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => '2. È mantenuto un elenco aggiornato del personale dell\'organizzazione di cui al punto 1 avente specifici ruoli e responsabilità ed è reso noto alle articolazioni competenti del soggetto NIS.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Nomina',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
    ],
  ],
  6 =>
  [
    'code' => 'GV.RR-02 punto 3',
    'title' => 'Inclusione del punto di contatto e di almeno un suo sostituto',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => '3. All’interno dell’organizzazione per la sicurezza informatica di cui al punto 1, sono inclusi il punto di contatto, e almeno un suo sostituto, di cui alla determina adottata ai sensi dell’articolo 7, comma 6 del decreto NIS.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Nomina',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
    ],
  ],
  7 =>
  [
    'code' => 'GV.RR-02 punto 4',
    'title' => 'Riesamina e aggiornamento del documento ogni due anni',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'registration',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => '4. I ruoli e le responsabilità di cui al punto 1 sono riesaminati e, se opportuno, aggiornati periodicamente e comunque almeno ogni due anni, nonché qualora si verifichino incidenti significativi, variazioni organizzative o mutamenti dell’esposizione alle minacce e ai relativi rischi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
    ],
  ],
  8 =>
  [
    'code' => 'GV.RR-04 punto 3',
    'title' => 'Documentazione e adozione procedure di controllo sul personale',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'registration',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => '3. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 1 e 2.',
    'evidence_guidance' => 'gv.rr-02 e id.ra-05',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-04 punto 1',
      1 => 'GV.RR-04 punto 2',
    ],
  ],
  9 =>
  [
    'code' => 'GV.RR-04 punto 5',
    'title' => 'Documentazione e adozione delle clausole nei contratti',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'registration',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => '5. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le
procedure in relazione al punto 4.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-04 punto 4',
    ],
  ],
  10 =>
  [
    'code' => 'GV.SC-01 punto 2',
    'title' => 'Elenco di standard minimi stabiliti da NIS2 per i processi di approvvigionamento',
    'domain' => 'Gestione del rischio di cybersecurity della catena di approvvigionamento (GV.SC)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => '2. Per i requisiti di sicurezza di cui al punto 1, lettera b), sono considerati, ove applicabile, almeno i seguenti ambiti:

a) affidabilità dei fornitori, tenendo conto almeno delle loro eventuali vulnerabilità specifiche, della qualità complessiva dei loro prodotti e delle pratiche di sicurezza informatica, specie con riguardo all’oggetto della fornitura, della capacità di garantire l’approvvigionamento, l’assistenza e la manutenzione nel tempo, nonché, ove applicabile, dei risultati delle valutazioni coordinate dei rischi per la sicurezza delle catene di approvvigionamento critiche effettuate dal Gruppo di cooperazione NIS;
b) ruoli e responsabilità nell\'ambito della fornitura;
c) affidabilità delle risorse umane;
d) conformità e audit di sicurezza;
e) gestione delle vulnerabilità;
f) continuità operativa e ripristino in caso di disastro;
g) gestione dell\'autenticazione, delle identità digitali e del controllo accessi;
h) sicurezza fisica;
i) formazione del personale e consapevolezza;
j) sicurezza dei dati;
k) protezione delle reti e delle comunicazioni;
l) monitoraggio degli eventi di sicurezza ivi inclusi gli accessi e le attività effettuate;
m) gestione e segnalazione degli incidenti;
n) sviluppo sicuro del codice e sicurezza fin dalla progettazione e per impostazione
predefinita;
o) manutenzione ordinaria ed evolutiva ivi inclusi gli aggiornamenti di sicurezza;
p) dismissione della fornitura ivi compresa la restituzione e la cancellazione dei dati;
q) subappalto, subfornitura o relativi potenziali requisiti di sicurezza lungo la catena di
fornitura.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.SC-01 punto 1',
    ],
  ],
  11 =>
  [
    'code' => 'GV.SC-02 punto 1',
    'title' => 'Comunicazione all\'ACN dei membri dell\'organizzazione per la sicurezza informatica esterni',
    'domain' => 'Gestione del rischio di cybersecurity della catena di approvvigionamento (GV.SC)',
    'obligation_type' => 'custom',
    'evidence_type' => 'other',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => 'I ruoli e le responsabilità in materia di cybersecurity per fornitori, clienti e partner sono stabiliti, comunicati e coordinati internamente ed esternamente.

1. Nell\'ambito dell\'organizzazione per la sicurezza informatica di cui alla misura GV.RR-02, sono definiti e resi noti alle articolazioni competenti del soggetto NIS gli eventuali ruoli e responsabilità in materia di sicurezza informatica assegnati al personale delle terze parti.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Nomina',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
    ],
  ],
  12 =>
  [
    'code' => 'GV.SC-02 punto 2',
    'title' => 'Personale esterno incluso nell\'organizzazione per la sicurezza informatica',
    'domain' => 'Gestione del rischio di cybersecurity della catena di approvvigionamento (GV.SC)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => '2. Il personale di cui al punto 1 avente specifici ruoli e responsabilità è incluso nell’elenco di
cui al punto 2 della misura GV.RR-02.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
    ],
  ],
  13 =>
  [
    'code' => 'PR.IR-03 punto 1',
    'title' => 'Canali di emergenza protetti in caso di crisi',
    'domain' => 'Resilienza dell\'infrastruttura tecnologica (PR.IR)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => 'Sono implementati meccanismi per soddisfare i requisiti di resilienza in situazioni normali e avverse.

1. In accordo agli esiti della valutazione del rischio di cui alla misura ID.RA-05, sono utilizzati sistemi di comunicazione di emergenza protetti.',
    'minimum_required_documents' => 4,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.IM-04 punto 3',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  14 =>
  [
    'code' => 'PR.IR-03 punto 2',
    'title' => 'Documentazione comprova PR.IR-03 punto 1',
    'domain' => 'Resilienza dell\'infrastruttura tecnologica (PR.IR)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 1,
    'description' => '2. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione al punto 1.',
    'minimum_required_documents' => 3,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'PR.IR-03 punto 1',
      1 => 'GV.PO-01 punto 1',
      2 => 'GV.RR-02 punto 1',
    ],
  ],
  15 =>
  [
    'code' => 'DE.CM-01 punto 4',
    'title' => 'Filtraggio sul traffico in ingresso (antispam/phishing su email e firewall)',
    'domain' => 'Monitoraggio continuo (DE.CM)',
    'obligation_type' => 'registration',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '4. Per almeno i sistemi informativi e di rete rilevanti, sono utilizzati strumenti di analisi e filtraggio sul flusso di traffico in ingresso (ivi inclusa la posta elettronica).',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  16 =>
  [
    'code' => 'DE.CM-01 punto 5',
    'title' => 'Monitoraggio (SIEM) su firewall, accessi',
    'domain' => 'Monitoraggio continuo (DE.CM)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '5. Per almeno i sistemi informativi e di rete rilevanti, ai fini di cui al punto 1, sono monitorati gli accessi da remoto, le attività dei sistemi perimetrali (ad esempio router e firewall), gli eventi amministrativi di rilievo, nonché gli accessi eseguiti o falliti alle risorse di rete, alle postazioni terminali e agli applicativi al fine di rilevare gli eventi di sicurezza informatica.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  17 =>
  [
    'code' => 'DE.CM-01 punto 6',
    'title' => 'Catalogazione con parametri quali-quantitativi sui log registrati dai SIEM',
    'domain' => 'Monitoraggio continuo (DE.CM)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '6. Per almeno i sistemi informativi e di rete rilevanti, ai fini di cui al punto 1, sono definiti, monitorati e documentati parametri quali-quantitativi per rilevare gli accessi non autorizzati o con abuso dei privilegi concessi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'DE.CM-01 punto 5',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  18 =>
  [
    'code' => 'DE.CM-01 punto 7',
    'title' => 'Documentazione comprova DE.CM-01 punto 4, 5 e 6',
    'domain' => 'Monitoraggio continuo (DE.CM)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '7. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 4, 5 e 6.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'DE.CM-01 punto 4',
      1 => 'DE.CM-01 punto 5',
      2 => 'DE.CM-01 punto 6',
      3 => 'ID.RA-05 punto 1',
      4 => 'GV.PO-01 punto 1',
    ],
  ],
  19 =>
  [
    'code' => 'DE.CM-09 punto 1',
    'title' => 'Antivirus e antimalware su tutti i dispositivi',
    'domain' => 'Monitoraggio continuo (DE.CM)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'L\'hardware e il software di elaborazione, gli ambienti di runtime e i loro dati sono
monitorati per individuare eventi potenzialmente avversi.

1. Fatte salve motivate e documentate ragioni normative o tecniche, sono presenti, aggiornati,
mantenuti e configurati in modo adeguato, sistemi di protezione delle postazioni terminali
per il rilevamento del codice malevolo.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.AM-01 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  20 =>
  [
    'code' => 'DE.CM-09 punto 2',
    'title' => 'Documentazione comprova DE.CM-09 punto 1',
    'domain' => 'Monitoraggio continuo (DE.CM',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione al punto 1.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'DE.CM-09 punto 1',
      1 => 'ID.RA-01 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  21 =>
  [
    'code' => 'GV.OC-04 punto 1',
    'title' => 'Elenco aggiornato dei sistemi informativi e di rete rilevanti',
    'domain' => 'Contesto organizzativo (GV.OC)',
    'obligation_type' => 'asset_inventory',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Gli obiettivi, le capacità e i servizi critici dai quali gli stakeholder dipendono o che si aspettano dall\'organizzazione sono compresi e comunicati.

1. È mantenuto un elenco aggiornato dei sistemi informativi e di rete rilevanti.',
    'evidence_guidance' => 'id.ra-05 è dove lo annuncio;
gv.po-01 dove lo ribadisco.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Inventario',
    'is_mandatory' => true,
    'is_active' => true,
  ],
  22 =>
  [
    'code' => 'GV.RM-03 punto 1',
    'title' => 'Piano di gestione dei rischi per la sicurezza informatica',
    'domain' => 'Strategia di gestione del rischio (GV.RM)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Le attività e gli esiti della gestione del rischio di cybersecurity sono parte integrante dei processi di gestione del rischio dell\'organizzazione.

1. Nell\'ambito dei processi di gestione del rischio del soggetto NIS e nel rispetto delle politiche di cui alla misura GV.PO-01, è definito, attuato, aggiornato e documentato un piano di gestione dei rischi per la sicurezza informatica per identificare, analizzare, valutare, trattare e monitorare i rischi.',
    'evidence_guidance' => 'id.ra-05 è dove gestisco il rischio effettivamente;
in gv.po-01 lo ribadisco.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
    ],
  ],
  23 =>
  [
    'code' => 'GV.RR-04 punto 1',
    'title' => 'Garanzia e valutazione dell’esperienza, capacità e affidabilità del personale autorizzato',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '1. Per almeno i sistemi informativi e di rete rilevanti, il personale autorizzato ad accedervi è
individuato previa valutazione dell’esperienza, capacità e affidabilità e deve fornire idonea
garanzia del pieno rispetto della normativa in materia di sicurezza informatica.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
  ],
  24 =>
  [
    'code' => 'GV.RR-04 punto 2',
    'title' => 'Garanzia e valutazione dell’esperienza, capacità e affidabilità degli amministratori di sistema',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Gli amministratori di sistema dei sistemi informativi e di rete sono individuati previa
valutazione dell’esperienza, capacità e affidabilità e devono fornire idonea garanzia del
pieno rispetto della normativa in materia di sicurezza informatica.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
  ],
  25 =>
  [
    'code' => 'GV.RR-04 punto 4',
    'title' => 'Clausole di riservatezza e sicurezza informatica anche postume nei contratti',
    'domain' => 'Ruoli, responsabilità e correlati poteri (GV.RR)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'medium',
    'sort_order' => 2,
    'description' => '4. In accordo agli esiti della valutazione del rischio di cui alla misura ID.RA-05, sono definiti
a livello contrattuale gli eventuali obblighi, in materia di sicurezza informatica, che
rimangono validi dopo la cessazione o la modifica del rapporto di lavoro dei dipendenti
del soggetto NIS (ad esempio prevedendo clausole in materia di riservatezza).',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
    ],
  ],
  26 =>
  [
    'code' => 'GV.SC-04 punto 1',
    'title' => 'Inventario aggiornato dei fornitori',
    'domain' => 'Gestione del rischio di cybersecurity della catena di approvvigionamento (GV.SC)',
    'obligation_type' => 'supply_chain',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'I fornitori sono noti e prioritizzati in base alla criticità.

1. È mantenuto un inventario aggiornato dei fornitori, le cui forniture hanno un potenziale impatto sulla sicurezza dei sistemi informativi e di rete, che comprende almeno:

a) gli estremi di contatto del referente della fornitura;
b) la tipologia di fornitura.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Inventario',
    'is_mandatory' => true,
    'is_active' => true,
  ],
  27 =>
  [
    'code' => 'GV.SC-05 punto 1',
    'title' => 'Inclusione degli standard per i fornitori dentro bandi, contratti e simili',
    'domain' => 'Gestione del rischio di cybersecurity della catena di approvvigionamento (GV.SC)',
    'obligation_type' => 'supply_chain',
    'evidence_type' => 'contract',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'I requisiti per affrontare i rischi di cybersecurity nella catena di approvvigionamento sono stabiliti, prioritizzati e integrati nei contratti e in altri tipi di accordi con i fornitori e altre terze parti rilevanti.

1. Fatte salve motivate e documentate ragioni normative o tecniche, i requisiti di sicurezza di cui alla misura GV.SC-01, punto 1, lettera b) sono inseriti nelle richieste di offerta, bandi di gara, contratti, accordi e convenzioni relativi alle forniture con potenziali impatto sulla sicurezza dei sistemi informativi e di rete.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.SC-01 punto 1',
      1 => 'GV.RR-02 punto 1',
    ],
  ],
  28 =>
  [
    'code' => 'GV.SC-07 punto 1',
    'title' => 'Considerazione rischi posti da un fornitore ai fini ID.RA-05',
    'domain' => 'Gestione del rischio di cybersecurity della catena di approvvigionamento (GV.SC)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'I rischi posti da un fornitore, dai suoi prodotti e servizi e da altre terze parti sono compresi, registrati, prioritizzati, valutati, trattati e monitorati nel corso della relazione.

1. Nell’ambito della valutazione del rischio di cui alla misura ID.RA-05, è valutato e documentato il rischio associato alle forniture. A tal fine, sono valutati almeno:
a) il livello di accesso del fornitore ai sistemi informativi e di rete del soggetto NIS;
b) l\'accesso del fornitore alla proprietà intellettuale e ai dati anche sulla base della loro criticità;
c) l\'impatto di una grave interruzione della fornitura;
d) i tempi e i costi di ripristino in caso di indisponibilità dei servizi;
e) i ruoli e le responsabilità del fornitore nel governo dei sistemi informativi e di rete.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.SC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  29 =>
  [
    'code' => 'GV.SC-07 punto 2',
    'title' => 'Documentazione periodica della conformità delle forniture',
    'domain' => 'Gestione del rischio di cybersecurity della catena di approvvigionamento (GV.SC)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. È verificata periodicamente e documentata la conformità delle forniture ai requisiti di cui
alla misura GV.SC-05.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.SC-04 punto 1',
    ],
  ],
  30 =>
  [
    'code' => 'ID.AM-01 punto 1',
    'title' => 'Inventario aggiornato di tutti gli apparati fisici autorizzati',
    'domain' => 'Gestione degli asset (ID.AM)',
    'obligation_type' => 'asset_inventory',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Sono mantenuti gli inventari dell\'hardware gestito dall\'organizzazione.

1. È mantenuto un inventario aggiornato degli apparati fisici (hardware) che compongono i sistemi informativi e di rete, ivi inclusi i dispositivi IT, IoT, OT e mobili, approvati da attori interni al soggetto NIS.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Inventario',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  31 =>
  [
    'code' => 'ID.AM-02 punto 1',
    'title' => 'Inventario aggiornato di tutti i software',
    'domain' => 'Gestione degli asset (ID.AM)',
    'obligation_type' => 'asset_inventory',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Sono mantenuti gli inventari del software, dei servizi e dei sistemi gestiti dall\'organizzazione.

1. È mantenuto un inventario aggiornato dei servizi, dei sistemi e delle applicazioni software che compongono i sistemi informativi e di rete, ivi incluse le applicazioni commerciali, open-source e custom, anche accessibili tramite API, approvati da attori interni al soggetto NIS.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Inventario',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  32 =>
  [
    'code' => 'ID.AM-03 punto 1',
    'title' => 'Inventario aggiornato di tutti i flussi di rete autorizzati',
    'domain' => 'Gestione degli asset (ID.AM)',
    'obligation_type' => 'asset_inventory',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Sono mantenute le rappresentazioni delle comunicazioni di rete, dei flussi di dati di rete interni ed esterni, autorizzati dall\'organizzazione.

1. È mantenuto un inventario aggiornato dei flussi di rete tra i sistemi informativi e di rete del soggetto NIS e l’esterno, approvati da attori interni al soggetto NIS.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Inventario',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  33 =>
  [
    'code' => 'ID.AM-04 punto 1',
    'title' => 'Inventario aggiornato di tutti i servizi di fornitori',
    'domain' => 'Gestione degli asset (ID.AM)',
    'obligation_type' => 'supply_chain',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Sono mantenuti gli inventari dei servizi erogati dai fornitori.

1. È mantenuto un inventario aggiornato dei servizi informatici erogati dai fornitori, ivi inclusi i servizi cloud.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Inventario',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
    ],
  ],
  34 =>
  [
    'code' => 'ID.RA-01 punto 1',
    'title' => 'Utilizzo dei canali ufficiali come criterio di valutazione del rischio in ID.RA-05',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Le vulnerabilità negli asset sono identificate, confermate e registrate.

1. Le informazioni di cui al punto 1 della misura ID.RA-08 sono utilizzate per identificare eventuali vulnerabilità sui i sistemi informativi e di rete.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  35 =>
  [
    'code' => 'ID.RA-01 punto 2',
    'title' => 'Pentest o vulnerability assessment periodici',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Per almeno i sistemi informativi e di rete rilevanti, in accordo al piano di gestione delle vulnerabilità di cui alla misura ID.RA-08, fatte salve motivate e documentate ragioni normative o tecniche, sono eseguite periodicamente e comunque prima della loro messa in esercizio, attività per l’identificazione delle vulnerabilità che comprendano almeno vulnerability assessment e/o penetration test.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Valutazione',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  36 =>
  [
    'code' => 'ID.RA-01 punto 3',
    'title' => 'Documentazione degli avvenuti pentest o vulnerability assessment',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. Le attività di cui al punto 2 sono documentate tramite apposite relazioni che contengono almeno:

a) la descrizione generale delle attività effettuate e gli esiti delle stesse;
b) la descrizione delle vulnerabilità rilevate e il relativo livello di impatto sulla sicurezza.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-01 punto 2',
    ],
  ],
  37 =>
  [
    'code' => 'ID.RA-05 punto 1',
    'title' => 'Valutazione del rischio posto alla sicurezza dei  sistemi informativi e di rete',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'registration',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 2,
    'description' => 'Minacce, vulnerabilità, probabilità e impatti sono utilizzati per comprendere il rischio inerente e per informare la prioritizzazione della risposta al rischio.

1. In accordo al piano di gestione dei rischi per la sicurezza informatica di cui alla misura GV.RM-03, è eseguita e documentata la valutazione del rischio posto alla sicurezza dei sistemi informativi e di rete, anche con riferimento alle eventuali dipendenze da fornitori e partner terzi, che comprende almeno:
a) l’identificazione del rischio;
b) l’analisi del rischio;
c) la ponderazione del rischio.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Valutazione',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
    ],
  ],
  38 =>
  [
    'code' => 'ID.RA-05 punto 2',
    'title' => 'Aggiornamento biennale della valutazione del rischio',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. La valutazione del rischio di cui al punto 1 è eseguita a intervalli pianificati e comunque almeno ogni due anni, nonché qualora si verifichino incidenti significativi, variazioni organizzative o mutamenti dell’esposizione alle minacce e ai relativi rischi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
    ],
  ],
  39 =>
  [
    'code' => 'ID.RA-05 punto 3',
    'title' => 'Approvazione della valutazione del rischio',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. La valutazione del rischio cui al punto 1 è approvata dagli organi di amministrazione e direttivi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
    ],
  ],
  40 =>
  [
    'code' => 'ID.RA-05 punto 4',
    'title' => 'Valutazione fatta anche su tutte le minacce, vulnerabilità e impatti',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '4. La valutazione del rischio di cui al punto 1 è effettuata considerando almeno le minacce interne ed esterne, le vulnerabilità non risolte e gli impatti conseguenti ad eventuali incidenti.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Procedura',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
    ],
  ],
  41 =>
  [
    'code' => 'PR.AA-01 punto 1',
    'title' => 'Inventario aggiornato di tutti gli accessi normali, privilegiati e da remoto',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'asset_inventory',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Le identità e le credenziali degli utenti, dei servizi e dell\'hardware autorizzati sono gestite dall\'organizzazione.

1. Tutte le utenze, ivi incluse quelle con privilegi amministrativi e quelle utilizzate per l’accesso remoto, sono censite, approvate da attori interni al soggetto NIS e, fatte salve motivate e documentate ragioni tecniche, in accordo agli esiti della valutazione del rischio di cui alla misura ID.RA-05, sono individuali per gli utenti.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Inventario',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
    ],
  ],
  42 =>
  [
    'code' => 'PR.AA-01 punto 2',
    'title' => 'Password robuste e aggiornate',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Le credenziali (ad esempio nome utente e password) relative alle utenze sono robuste e aggiornate in accordo agli esiti della valutazione del rischio di cui alla misura ID.RA-05.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  43 =>
  [
    'code' => 'PR.AA-01 punto 3',
    'title' => 'Terminazione aggiornata degli accessi',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. Per almeno i sistemi informativi e di rete rilevanti, sono verificate periodicamente le utenze e le relative autorizzazioni, aggiornandole/revocandole in caso di variazioni (ad esempio trasferimento o cessazione di personale).',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.AA-01 punto 1',
    ],
  ],
  44 =>
  [
    'code' => 'PR.AA-01 punto 4',
    'title' => 'Documentazione comprova PR.AA-01 punti 1, 2 e 3',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '4. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 1, 2 e 3.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.AA-01 punto 1',
      2 => 'PR.AA-01 punto 2',
      3 => 'PR.AA-01 punto 3',
      4 => 'GV.PO-01 punto 1',
    ],
  ],
  45 =>
  [
    'code' => 'PR.AA-03 punto 1',
    'title' => 'Valutazione del rischio legato alle utenze',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '1. Le modalità di autenticazione delle utenze per accedere ai sistemi informativi e di rete sono commisurate al rischio. A tal fine sono valutati almeno i rischi connessi:

a) ai privilegi delle utenze;
b) alla criticità dei sistemi informativi e di rete;
c) alla tipologia di operazioni che le utenze possono effettuare sui sistemi informativi e di rete.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Valutazione',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  46 =>
  [
    'code' => 'PR.AA-03 punto 2',
    'title' => 'MFA per almeno i sistemi di rete rilevanti',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Per almeno i sistemi informativi e di rete rilevanti e in accordo agli esiti della valutazione del rischio di cui alla misura ID.RA-05, sono impiegate modalità di autenticazione multifattore.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  47 =>
  [
    'code' => 'PR.AA-03 punto 3',
    'title' => 'Documentazione comprova PR.AA-03 punti 1 e 2',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 1 e 2.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.AA-03 punto 1',
      2 => 'PR.AA-03 punto 2',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  48 =>
  [
    'code' => 'PR.AA-05 punto 1',
    'title' => 'Least privilege sulle utenze',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '1. I permessi sono assegnati alle utenze in accordo ai principi del minimo privilegio e della separazione delle funzioni, tenuto anche conto della necessità di conoscere (need to know).',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'PR.AA-01 punto 1',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  49 =>
  [
    'code' => 'PR.AA-05 punto 2',
    'title' => 'Credenziali diverse per utente con diversi privilegi',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. È assicurata la completa distinzione tra utenze con e senza privilegi amministrativi degli amministratori di sistema alle quali debbono corrispondere credenziali diverse.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.RA-01 punto 1',
      2 => 'PR.AA-01 punto 1',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  50 =>
  [
    'code' => 'PR.AA-05 punto 3',
    'title' => 'Documentazione comprova PR.AA-05 punto 1 e 2',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le
procedure in relazione ai punti 1e 2.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.AA-05 punto 1',
      2 => 'PR.AA-05 punto 2',
    ],
  ],
  51 =>
  [
    'code' => 'PR.AA-06 punto 1',
    'title' => 'Accesso fisico protetto per almeno i sistemi rilevanti',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'L\'accesso fisico agli asset è gestito, monitorato e applicato in misura appropriata al rischio.

1. Per almeno i sistemi informativi e di rete rilevanti, l’accesso fisico è protetto.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  52 =>
  [
    'code' => 'PR.AA-06 punto 2',
    'title' => 'Documentazione comprova PR.AA-06 punto 1',
    'domain' => 'Gestione delle identità, autenticazione e controllo degli accessi (PR.AA)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione al punto 1.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  53 =>
  [
    'code' => 'PR.DS-01 punto 1',
    'title' => 'Cifratura su tutti i dispositivi',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '1. Per almeno i sistemi informativi e di rete rilevanti e in accordo agli esiti della valutazione del rischio di cui alla misura ID.RA-05, fatte salve motivate e documentate ragioni normative o tecniche, i dati memorizzati sui dispositivi portatili, ivi inclusi laptop, smartphone e tablet, e sui supporti removibili, sono cifrati con protocolli e algoritmi allo
stato dell’arte e considerati sicuri.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.AM-01 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  54 =>
  [
    'code' => 'PR.DS-01 punto 2',
    'title' => 'Autoplay disattivato, scansione dei supporti mobili prima dell\'apertura su PC',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Fatte salve e documentate ragioni normative o tecniche, è disabilitata l\'auto esecuzione dei supporti rimovibili ed è effettuata la loro scansione al fine di rilevare codici malevoli prima che siano utilizzati nei sistemi informativi e di rete.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  55 =>
  [
    'code' => 'PR.DS-01 punto 3',
    'title' => 'Documentazione comprova PR.DS-01 punti 1 e 2',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 1 e 2.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.DS-01 punto 1',
      2 => 'PR.DS-01 punto 2',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  56 =>
  [
    'code' => 'PR.DS-02 punto 1',
    'title' => 'Protocolli e cifratura per la trasmissione di dati',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '1. Per almeno i sistemi informativi e di rete rilevanti, ivi inclusi quelli di comunicazione vocale, video e testuale, e in accordo agli esiti della valutazione del rischio di cui alla misura ID.RA-05 fatte salve motivate e documentate ragioni normative o tecniche, sono utilizzati, per la trasmissione dei dati da e verso l’esterno del soggetto NIS, protocolli e
algoritmi di cifratura allo stato dell’arte e considerati sicuri.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.AM-03 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  57 =>
  [
    'code' => 'PR.DS-02 punto 2',
    'title' => 'Documentazione comprova cifrature e protocolli di PR.DS-02 punto 1',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione al punto 1.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.DS-02 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  58 =>
  [
    'code' => 'PR.DS-11 punto 1',
    'title' => 'Backup periodici, anche offline',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'I backup dei dati sono creati, protetti, mantenuti e verificati.

1. In accordo alle esigenze di continuità operativa e di ripristino in caso di disastro individuate nei piani di cui alla misura ID.IM-04, sono effettuati periodicamente i backup dei dati e delle configurazioni e, per almeno i sistemi informativi e di rete rilevanti, sono anche conservate copie di backup offline.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  59 =>
  [
    'code' => 'PR.DS-11 punto 2',
    'title' => 'Documentazione comprova PR.DS-11 punto 1',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione al punto 1.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.DS-11 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  60 =>
  [
    'code' => 'PR.DS-11 punto 3',
    'title' => 'Protezione fisica e cifratura dei backup',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. Per almeno i sistemi informativi e di rete rilevanti, è assicurata la riservatezza e l’integrità delle informazioni contenute nei backup mediante adeguata protezione fisica dei supporti ovvero mediante cifratura.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.DS-11 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  61 =>
  [
    'code' => 'PR.DS-11 punto 4',
    'title' => 'Test di ripristino sui backup',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'technical_report',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '4. Per almeno i sistemi informativi e di rete rilevanti, è verificata periodicamente l\'utilizzabilità dei backup effettuati mediante test di ripristino.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'PR.DS-11 punto 1',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  62 =>
  [
    'code' => 'PR.DS-11 punto 5',
    'title' => 'Documentazione comprova PR.DS-11 punti 3 e 4',
    'domain' => 'Sicurezza dei dati (PR.DS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '5. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 3 e 4.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'PR.DS-11 punto 3',
      1 => 'PR.DS-11 punto 4',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  63 =>
  [
    'code' => 'PR.IR-01 punto 1',
    'title' => 'Definizione delle attività consentite da remoto e con quali accessi',
    'domain' => 'Resilienza dell\'infrastruttura tecnologica (PR.IR)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Le reti e gli ambienti sono protetti dall\'accesso logico e dall\'uso non autorizzati.

1. Per almeno i sistemi informativi e di rete rilevanti, sono definite e documentate le eventuali attività consentite da remoto e implementate adeguate misure di sicurezza per l’accesso.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  64 =>
  [
    'code' => 'PR.IR-01 punto 2',
    'title' => 'Elenco aggiornato dei sistemi accessibili da remoto e con quali accessi',
    'domain' => 'Resilienza dell\'infrastruttura tecnologica (PR.IR)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. È mantenuto un elenco aggiornato dei sistemi informativi e di rete ai quali è possibile accedere da remoto con la descrizione delle relative modalità di accesso.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'PR.AA-01 punto 1',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  65 =>
  [
    'code' => 'PR.IR-01 punto 4',
    'title' => 'Documentazione comprova PR.IR-01 punti 1, 2 e 3',
    'domain' => 'Resilienza dell\'infrastruttura tecnologica (PR.IR)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '4. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 1, 2 e 3.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'PR.IR-01 punto 3',
      2 => 'ID.RA-05 punto 1',
      3 => 'PR.IR-01 punto 1',
      4 => 'PR.IR-01 punto 2',
    ],
  ],
  66 =>
  [
    'code' => 'PR.PS-01 punto 1',
    'title' => 'Configurazioni hardened dei sistemi',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Sono stabilite e applicate pratiche di gestione della configurazione.

1. Per almeno i sistemi informativi e di rete rilevanti, sono definite, e documentate in un
elenco aggiornato, le loro configurazioni di riferimento sicure (hardened).',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  67 =>
  [
    'code' => 'PR.PS-01 punto 2',
    'title' => 'Documentazione comprova PR.PS-01 punto 1',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione al punto 1.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.PS-01 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  68 =>
  [
    'code' => 'PR.PS-02 punto 1',
    'title' => 'Presenza esclusiva di software con aggiornamenti di sicurezza garantiti',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '1. Fatte salve motivate e documentate ragioni normative o tecniche, è installato esclusivamente software, ivi compresi i sistemi operativi, per il quale è garantita la disponibilità di aggiornamenti di sicurezza.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.AM-02 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  69 =>
  [
    'code' => 'PR.PS-02 punto 2',
    'title' => 'Aggiornamenti di sicurezza installati tempestivamente',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Fatte salve motivate e documentate ragioni normative o tecniche, sono installati, senza ingiustificato ritardo, gli ultimi aggiornamenti di sicurezza rilasciati dal produttore in coerenza con il piano di gestione delle vulnerabilità di cui alla misura ID.RA-08.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.RA-08 punto 5',
      2 => 'ID.RA-05 punto 1',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  70 =>
  [
    'code' => 'PR.PS-02 punto 3',
    'title' => 'Documentazione comprova PR.PS-02 punti 1 e 2',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 1e 2.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'PR.PS-02 punto 1',
      1 => 'PR.PS-02 punto 2',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  71 =>
  [
    'code' => 'PR.PS-02 punto 4',
    'title' => 'Test degli aggiornamenti di software critici prima dell\'installazione',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'technical_report',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '4. Fatte salve motivate e documentate ragioni normative o tecniche e in accordo agli esiti della valutazione del rischio di cui alla misura ID.RA-05, l’aggiornamento del software ritenuto critico è verificato in ambiente di test prima dell’effettivo impiego in ambiente operativo.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.PS-02 punto 1',
      2 => 'PR.PS-02 punto 2',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  72 =>
  [
    'code' => 'PR.PS-02 punto 5',
    'title' => 'Documentazione comprova PR.PS-02 punto 4',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '5. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione al punto 4.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.PS-02 punto 4',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  73 =>
  [
    'code' => 'PR.PS-03 punto 1',
    'title' => 'Fine vita dei dispositivi gestito e sicuro',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'L\'hardware è mantenuto, sostituito e rimosso in base al rischio.

1. Per almeno i sistemi informativi e di rete rilevanti, sono adottate e documentate procedure per il trasferimento fisico e la dismissione di dispositivi atti alla memorizzazione di dati in modo sicuro.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  74 =>
  [
    'code' => 'PR.PS-03 punto 2',
    'title' => 'Registro delle manutenzioni dell\'hardware',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Per almeno i sistemi informativi e di rete rilevanti, sono mantenuti uno o più registri delle manutenzioni effettuate sull\'hardware.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  75 =>
  [
    'code' => 'PR.PS-04 punto 1',
    'title' => 'Registrazione log di accessi da remoto e privilegiati',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '1. Tutti gli accessi eseguiti da remoto e quelli effettuati con utenze con privilegi amministrativi sono registrati.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  76 =>
  [
    'code' => 'PR.PS-04 punto 2',
    'title' => 'Conservazione sicura dei log per monitorare la sicurezza',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '2. Per almeno i sistemi informativi e di rete rilevanti, sono conservati in modo sicuro, e possibilmente centralizzato, almeno i log necessari ai fini del monitoraggio degli eventi di sicurezza, ivi compresi quelli relativi agli accessi di cui al punto 1.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.PS-04 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  77 =>
  [
    'code' => 'PR.PS-04 punto 3',
    'title' => 'Definizione delle tempistiche di conservazione dei log',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '3. In accordo agli esiti della valutazione rischio di cui alla misura ID.RA-05, sono definite e documentate le tempistiche di conservazione dei log di cui al punto 2.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.PS-04 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  78 =>
  [
    'code' => 'PR.PS-04 punto 4',
    'title' => 'Documentazione comprova PR.PS-04 punti 1 e 2',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => '4. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 1 e 2.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'PR.PS-04 punto 1',
      2 => 'PR.PS-04 punto 2',
    ],
  ],
  79 =>
  [
    'code' => 'PR.PS-06 punto 1',
    'title' => 'Sviluppo sicuro del codice',
    'domain' => 'Sicurezza delle piattaforme (PR.PS)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 2,
    'description' => 'Le pratiche di sviluppo sicuro del software sono integrate e le loro prestazioni sono monitorate durante l\'intero ciclo di vita del software.

1. Sono adottate e documentate pratiche di sviluppo sicuro del codice nello sviluppo del software.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  80 =>
  [
    'code' => 'ID.RA-08 punto 1',
    'title' => 'Monitoraggio CSIRT Italia, CERT e ISAC',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 3,
    'description' => 'Sono stabiliti processi per la ricezione, l\'analisi e la risposta alle divulgazioni di vulnerabilità.

1. Sono monitorati almeno i canali di comunicazione del CSIRT Italia, nonché di eventuali CERT e Information Sharing & Analysis Centre (ISAC) settoriali, al fine di acquisire, analizzare e rispondere alle informazioni sulle vulnerabilità.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-01 punto 1',
      1 => 'ID.RA-08 punto 3',
    ],
  ],
  81 =>
  [
    'code' => 'ID.RA-08 punto 3',
    'title' => 'Piano di gestione delle vulnerabilità',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 3,
    'description' => '3. È definito, attuato, aggiornato e documentato un piano di gestione delle vulnerabilità che comprende almeno:

a) le modalità per l\'identificazione delle vulnerabilità di cui alla misura ID.RA-01 e la relativa pianificazione delle attività;
b) le modalità per monitorare, ricevere, analizzare e rispondere alle informazioni sulle vulnerabilità;
c) le procedure, i ruoli, le responsabilità per lo svolgimento delle attività di cui alle lettere a) e b).',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
      1 => 'GV.RR-02 punto 1',
    ],
  ],
  82 =>
  [
    'code' => 'ID.RA-08 punto 4',
    'title' => 'Approvazione del piano di gestione delle vulnerabilità',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 3,
    'description' => '4. Il piano di cui al punto 3 è approvato dagli organi di amministrazione e direttivi.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-08 punto 3',
    ],
  ],
  83 =>
  [
    'code' => 'PR.AT-01 punto 1',
    'title' => 'Piano di formazione in materia di sicurezza informatica',
    'domain' => 'Consapevolezza e formazione (PR.AT)',
    'obligation_type' => 'training',
    'evidence_type' => 'training_record',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 1,
    'sort_order' => 4,
    'description' => 'Il personale è sensibilizzato e formato in modo da possedere le conoscenze e le competenze per svolgere compiti di carattere generale tenendo conto dei rischi di cybersecurity.

1. È definito, attuato, aggiornato e documentato un piano di formazione in materia di sicurezza informatica del personale, ivi inclusi gli organi di amministrazione e direttivi, che comprende almeno:

a) la pianificazione delle attività di formazione previste con l’indicazione dei contenuti della formazione fornita;
b) le eventuali modalità di verifica dell\'acquisizione dei contenuti.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  84 =>
  [
    'code' => 'PR.AT-01 punto 2',
    'title' => 'Approvazione del piano di formazione',
    'domain' => 'Consapevolezza e formazione (PR.AT)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 4,
    'description' => '2. Il piano di formazione di cui al punto 1 è approvato dagli organi di amministrazione e direttivi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'PR.AT-01 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  85 =>
  [
    'code' => 'PR.AT-01 punto 3',
    'title' => 'Registro aggiornato dell\'avvenuta formazione con elenco dipendenti',
    'domain' => 'Consapevolezza e formazione (PR.AT)',
    'obligation_type' => 'training',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 4,
    'description' => '3. È mantenuto un registro aggiornato recante l\'elenco dei dipendenti che hanno ricevuto la formazione di cui al punto 1, i relativi contenuti e l\'elenco delle verifiche svolte laddove previste.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'PR.AT-01 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  86 =>
  [
    'code' => 'PR.AT-02 punto 1',
    'title' => 'Formazione specifica per i ruoli',
    'domain' => 'Consapevolezza e formazione (PR.AT)',
    'obligation_type' => 'training',
    'evidence_type' => 'training_record',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 1,
    'sort_order' => 4,
    'description' => 'Gli individui che ricoprono ruoli specializzati sono sensibilizzati e formati in modo da possedere le conoscenze e le competenze per svolgere i pertinenti compiti tenendo conto dei rischi di cybersecurity.

1. Il piano di cui alla misura PR.AT-01 prevede una formazione dedicata al personale con ruoli specializzati, ossia che richiedono una serie di capacità e competenze attinenti alla sicurezza, ivi compresi gli amministratori di sistema, che comprende almeno:

a) le istruzioni relative alla configurazione e al funzionamento sicuri dei sistemi informativi e di rete;
b) le informazioni sulle minacce informatiche note;
c) le istruzioni sul comportamento da tenere in caso di eventi rilevanti per la sicurezza.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'PR.AT-01 punto 1',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  87 =>
  [
    'code' => 'PR.AT-02 punto 2',
    'title' => 'Registro aggiornato dell\'avvenuta formazione specifica con elenco dipendenti',
    'domain' => 'Consapevolezza e formazione (PR.AT)',
    'obligation_type' => 'training',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 4,
    'description' => '2. È mantenuto un registro aggiornato recante l\'elenco dei dipendenti che hanno ricevuto la formazione di cui al punto 1, i relativi contenuti e l\'elenco delle verifiche svolte laddove previste.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'PR.AT-02 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  88 =>
  [
    'code' => 'ID.RA-06 punto 1',
    'title' => 'Piano di trattamento del rischio',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 5,
    'description' => 'Le risposte al rischio sono scelte, prioritizzate, pianificate, monitorate e comunicate.

1. È definito, documentato, eseguito e monitorato un piano di trattamento del rischio che comprende almeno:
a) le opzioni di trattamento e le misure da attuare in merito al trattamento di ciascun rischio individuato e le relative priorità;
b) le articolazioni competenti per l\'attuazione delle misure di trattamento dei rischi e le tempistiche per tale attuazione;
c) la descrizione e le ragioni che giustificano l\'accettazione di eventuali rischi residui al trattamento.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  89 =>
  [
    'code' => 'ID.RA-06 punto 2',
    'title' => 'Mitigazioni alternative incluse in ID.RA-06',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 5,
    'description' => '2. Qualora per motivate e documentate ragioni normative o tecniche non siano attuati i requisiti di cui alla tabella 2 in appendice al presente allegato, sono adottate, ove applicabile, misure di mitigazione compensative e il piano di cui al punto 1 include la descrizione di tali misure e dell’eventuale rischio residuo.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
    ],
  ],
  90 =>
  [
    'code' => 'ID.RA-06 punto 3',
    'title' => 'Approvazione del piano di trattamento del rischio',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 5,
    'description' => '3. Il piano di cui al punto 1, ivi compresa l’accettazione di eventuali rischi residui, è approvato dagli organi di amministrazione e direttivi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.OC-04 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'ID.RA-06 punto 1',
    ],
  ],
  91 =>
  [
    'code' => 'ID.RA-08 punto 2',
    'title' => 'Risoluzione, mitigazione o accettazione di tutto il rilevato',
    'domain' => 'Valutazione del rischio (Risk Assessment) (ID.RA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'technical_report',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 5,
    'description' => '2. Le vulnerabilità, ivi comprese quelle identificate ai sensi della misura ID.RA-01, sono prontamente risolte attraverso aggiornamenti di sicurezza o misure di mitigazione, ove disponibili, ovvero accettando e documentando il rischio in accordo al piano di trattamento del rischio informatico di cui alla misura ID.RA-06.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-01 punto 2',
      1 => 'ID.RA-06 punto 1',
    ],
  ],
  92 =>
  [
    'code' => 'RC.CO-03 punto 1',
    'title' => 'Definizione delle comunicazioni agli stakeholder dell\'avvenuto ripristino',
    'domain' => 'Comunicazione sul ripristino dagli incidenti (RC.CO)',
    'obligation_type' => 'incident_reporting',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 6,
    'description' => '1. Sono adottate e documentate procedure per comunicare alle parti interne interessate, ivi incluse le articolazioni competenti del soggetto NIS, le attività di ripristino a seguito di un incidente.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'RS.MA-01 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  93 =>
  [
    'code' => 'RC.RP-01 punto 1',
    'title' => 'Definizione delle procedure di ripristino del normale funzionamento dei sistemi',
    'domain' => 'Esecuzione del piano di ripristino dagli incidenti (RC.RP)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 6,
    'description' => '1. Nell\'ambito del piano per la gestione degli incidenti di cui alla misura RS.MA-01, sono adottate e documentate procedure per il ripristino con riguardo almeno al ripristino del normale funzionamento dei sistemi informativi e di rete coinvolti da incidenti di sicurezza informatica, ivi compresi quelli di cui all’articolo 25 del decreto NIS.',
    'minimum_required_documents' => 4,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'RS.MA-01 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  94 =>
  [
    'code' => 'RS.CO-02 punto 1',
    'title' => 'Definizione delle comunicazioni di incidente ai destinatari dei servizi',
    'domain' => 'Segnalazione e comunicazione della risposta agli incidenti (RS.CO)',
    'obligation_type' => 'incident_reporting',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 6,
    'description' => 'Gli stakeholder interni ed esterni sono informati degli incidenti.

1. In accordo al piano per la gestione degli incidenti di cui alla misura RS.MA-01, sono documentate e adottate procedure per comunicare senza ingiustificato ritardo, se ritenuto opportuno e qualora possibile, sentito il CSIRT Italia, ovvero qualora intimato dall’Agenzia per la cybersicurezza nazionale ai sensi dell’articolo 37, comma 3, lettere g) e h), del decreto NIS:

a) ai destinatari dei loro servizi, gli incidenti significativi che possono ripercuotersi negativamente sulla fornitura di tali servizi;
b) ai destinatari dei servizi che sono potenzialmente interessati da una minaccia informatica significativa, le misure o azioni correttive o di mitigazione che tali destinatari possono adottare in risposta a tale minaccia e la natura di tale minaccia.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'RS.MA-01 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  95 =>
  [
    'code' => 'RS.CO-02 punto 2',
    'title' => 'Definizione delle comunicazioni di incidente al pubblico',
    'domain' => 'Segnalazione e comunicazione della risposta agli incidenti (RS.CO)',
    'obligation_type' => 'incident_reporting',
    'evidence_type' => 'procedure',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 6,
    'description' => '2. Sono documentate e adottate procedure per informare il pubblico sugli incidenti occorsi, qualora intimato dall’Agenzia per la cybersicurezza nazionale ai sensi dell’art. 37, comma 3, lettera i) del decreto NIS.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'RS.MA-01 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  96 =>
  [
    'code' => 'RS.MA-01 punto 1',
    'title' => 'Piano per la gestione degli incidenti di sicurezza informatica',
    'domain' => 'Gestione degli incidenti (RS.MA)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 6,
    'description' => 'Il piano di risposta agli incidenti è eseguito in coordinamento con le terze parti interessate una volta dichiarato un incidente.

1. È definito, attuato, aggiornato e documentato un piano per la gestione degli incidenti di sicurezza informatica e la notifica al CSIRT Italia, in accordo a quanto previsto dall’articolo 25 del decreto NIS, che comprende almeno:
a) le fasi e le procedure di gestione e notifica degli incidenti con l’indicazione dei relativi
ruoli e delle responsabilità;
b) le procedure per la predisposizione e la trasmissione delle relazioni di cui all’articolo
25, comma 5, lettere c), d) ed e) del decreto NIS;
c) le informazioni di contatto per la segnalazione degli incidenti;
d) le modalità di comunicazione interna, anche con riguardo al coinvolgimento degli
organi di amministrazione e direttivi, ed esterna;
e) la reportistica da utilizzare per la documentazione dell’incidente.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
    ],
  ],
  97 =>
  [
    'code' => 'RS.MA-01 punto 2',
    'title' => 'Approvazione del piano per la gestione degli incidenti RS.MA-01',
    'domain' => 'Gestione degli incidenti (RS.MA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 6,
    'description' => '2. Il piano di cui al punto 1 è approvato dagli organi di amministrazione e direttivi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'RS.MA-01 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  98 =>
  [
    'code' => 'RS.MA-01 punto 3',
    'title' => 'Aggiornamento biennale del piano RS.MA-01',
    'domain' => 'Gestione degli incidenti (RS.MA)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 6,
    'description' => '3. Il piano di cui al punto 1 è riesaminato e, se opportuno, aggiornato periodicamente e comunque almeno ogni due anni, nonché qualora si verifichino incidenti significativi, integrando le relative lezioni apprese, o mutamenti dell’esposizione alle minacce e ai relativi rischi.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'RS.MA-01 punto 1',
      1 => 'GV.PO-01 punto 1',
    ],
  ],
  99 =>
  [
    'code' => 'DE.CM-01 punto 2',
    'title' => 'Definizione di livelli di servizio attesi',
    'domain' => 'Monitoraggio continuo (DE.CM)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 7,
    'description' => '2. Sono definiti e documentati i livelli di servizio attesi (SL) dei servizi e delle attività del soggetto NIS anche ai fini di rilevare tempestivamente gli incidenti significativi.',
    'minimum_required_documents' => 4,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.IM-04 punto 1',
      1 => 'ID.IM-04 punto 2',
      2 => 'ID.IM-04 punto 3',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  100 =>
  [
    'code' => 'DE.CM-01 punto 3',
    'title' => 'Documentazione comprova DE.CM-01 punti 1 e 2',
    'domain' => 'Monitoraggio continuo (DE.CM)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 7,
    'description' => '3. Nel rispetto delle politiche di cui alla misura GV.PO-01, sono adottate e documentate le procedure in relazione ai punti 1 e 2.',
    'minimum_required_documents' => 3,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'DE.CM-01 punto 1',
      1 => 'DE.CM-01 punto 2',
      2 => 'GV.PO-01 punto 1',
    ],
  ],
  101 =>
  [
    'code' => 'ID.IM-04 punto 1',
    'title' => 'Piano di continuità operativa',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 7,
    'description' => 'I piani di risposta agli incidenti e gli altri piani di cybersecurity che impattano le operazioni sono stabiliti, comunicati, mantenuti e migliorati.
1. Per almeno i sistemi informativi e di rete rilevanti è definito, attuato, aggiornato e documentato un piano di continuità operativa, che comprende almeno:
a) le finalità e l\'ambito di applicazione;
b) i ruoli e le responsabilità;
c) i contatti principali e i canali di comunicazione (interni ed esterni);
d) le condizioni per l\'attivazione e la disattivazione del piano;
e) le risorse necessarie, ivi compresi i backup e le ridondanze.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
      1 => 'GV.RR-02 punto 1',
      2 => 'ID.RA-05 punto 1',
      3 => 'ID.RA-08 punto 3',
    ],
  ],
  102 =>
  [
    'code' => 'ID.IM-04 punto 2',
    'title' => 'Piano di ripristino in caso di disastro',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 8,
    'description' => '2. Per almeno i sistemi informativi e di rete rilevanti è definito, attuato, aggiornato e documentato un piano di ripristino in caso di disastro, che comprende almeno:
a) le finalità e l\'ambito di applicazione;
b) i ruoli e le responsabilità;
c) i contatti principali e i canali di comunicazione (interni ed esterni);
d) le condizioni per l\'attivazione e la disattivazione del piano;
e) le risorse necessarie, ivi compresi i backup e le ridondanze;
f) l\'ordine di ripristino delle operazioni;
g) le procedure di ripristino per operazioni specifiche, compresi gli obiettivi di ripristino.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
      1 => 'GV.RR-02 punto 1',
      2 => 'ID.RA-05 punto 1',
      3 => 'ID.RA-08 punto 3',
    ],
  ],
  103 =>
  [
    'code' => 'ID.IM-04 punto 3',
    'title' => 'Piano per la gestione delle crisi',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 9,
    'description' => '3. Per almeno i sistemi informativi e di rete rilevanti è definito, attuato, aggiornato e documentato un piano per la gestione delle crisi che comprende almeno:

a) i ruoli e responsabilità del personale e, se opportuno, dei fornitori, specificando l\'assegnazione dei ruoli in situazioni di crisi, comprese le procedure specifiche da seguire;
b) le modalità di comunicazione tra i soggetti e le autorità competenti.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.RA-05 punto 1',
      2 => 'ID.RA-08 punto 3',
      3 => 'GV.PO-01 punto 1',
    ],
  ],
  104 =>
  [
    'code' => 'ID.IM-04 punto 4',
    'title' => 'Approvazione dei tre piani ID.IM-04.1, 2 e 3',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 9,
    'minimum_required_documents' => 4,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.IM-04 punto 1',
      2 => 'ID.IM-04 punto 2',
      3 => 'ID.IM-04 punto 3',
    ],
  ],
  105 =>
  [
    'code' => 'ID.IM-04 punto 5',
    'title' => 'Aggiornamento biennale dei tre piani ID.IM-04.1, 2 e 3',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 9,
    'description' => '5. I piani di cui ai punti 1, 2 e 3 sono riesaminati e, se opportuno, aggiornati periodicamente
e comunque almeno ogni due anni, nonché qualora si verifichino incidenti significativi o
mutamenti dell’esposizione alle minacce e ai relativi rischi.',
    'minimum_required_documents' => 4,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.RR-02 punto 1',
      1 => 'ID.IM-04 punto 1',
      2 => 'ID.IM-04 punto 2',
      3 => 'ID.IM-04 punto 3',
    ],
  ],
  106 =>
  [
    'code' => 'ID.IM-01 punto 1',
    'title' => 'Piano di  adeguamento',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'risk_management',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 10,
    'description' => 'Sono identificati miglioramenti in esito alle valutazioni.

1. In accordo agli esiti del riesame di cui al punto 1 della misura GV.PO-02, è definito, attuato, documentato e approvato dagli organi di amministrazioni e direttivi un piano di adeguamento che identifichi gli interventi necessari ad assicurare l’attuazione delle politiche di sicurezza.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
      1 => 'GV.RR-02 punto 1',
      2 => 'ID.RA-05 punto 1',
    ],
  ],
  107 =>
  [
    'code' => 'ID.IM-01 punto 2',
    'title' => 'Relazioni periodiche agli organi amministrativi/direttivi sul piano di adeguamento',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 10,
    'description' => '2. Gli organi di amministrazione e direttivi sono informati mediante apposite relazioni periodiche sugli esiti dei piani di cui al punto 1.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.IM-01 punto 1',
    ],
  ],
  108 =>
  [
    'code' => 'ID.IM-01 punto 3',
    'title' => 'Piano per la valutazione dell\'efficacia delle misure di gestione del rischio ID.RA-05',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 2,
    'sort_order' => 10,
    'description' => '3. È definito, attuato, aggiornato e documentato un piano per la valutazione dell\'efficacia
delle misure di gestione del rischio per la sicurezza informatica che comprenda
l\'indicazione delle misure da valutare e i relativi metodi di valutazione.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Piano',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.RA-05 punto 1',
      1 => 'GV.RR-02 punto 1',
    ],
  ],
  109 =>
  [
    'code' => 'ID.IM-01 punto 4',
    'title' => 'Relazioni periodiche sul piano di valutazione dell’efficacia ID.IM-01.3',
    'domain' => 'Miglioramento (ID.IM)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 10,
    'description' => '4. Gli organi di amministrazione e direttivi sono informati mediante apposite relazioni periodiche sul piano di valutazione dell’efficacia di cui al punto 3.',
    'minimum_required_documents' => 2,
    'default_document_type_name' => 'Verbale',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'ID.IM-01 punto 3',
    ],
  ],
  110 =>
  [
    'code' => 'GV.PO-01 punto 1',
    'title' => 'Politiche di sicurezza informatica',
    'domain' => 'Politica (GV.PO)',
    'obligation_type' => 'governance',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'review_frequency_months' => 1,
    'sort_order' => 11,
    'description' => 'La politica per la gestione del rischio di cybersecurity è stabilita in base al contesto organizzativo, alla strategia di cybersecurity e alle priorità, ed è comunicata e applicata.

1. Sono adottate e documentate politiche di sicurezza informatica per almeno i seguenti ambiti:
a) gestione del rischio;
b) ruoli e responsabilità;
c) affidabilità delle risorse umane;
d) conformità e audit di sicurezza;
e) gestione dei rischi per la sicurezza informatica della catena di approvvigionamento;
f) gestione degli asset;
g) gestione delle vulnerabilità;
h) continuità operativa, ripristino in caso di disastro e gestione delle crisi;
i) gestione dell\'autenticazione, delle identità digitali e del controllo accessi;
j) sicurezza fisica;
k) formazione del personale e consapevolezza;
l) sicurezza dei dati;
m) sviluppo, configurazione, manutenzione e dismissione dei sistemi informativi e di rete;
n) protezione delle reti e delle comunicazioni;
o) monitoraggio degli eventi di sicurezza;
p) risposta agli incidenti e ripristino.',
    'minimum_required_documents' => 11,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
  ],
  111 =>
  [
    'code' => 'GV.PO-01 punto 2',
    'title' => 'Copertura della Tabella 1 nelle politiche di sicurezza informatica',
    'domain' => 'Politica (GV.PO)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 11,
    'description' => 'Per gli ambiti di cui al punto 1 sono incluse almeno le politiche in relazione ai requisiti
indicati nella tabella 1 in appendice al presente allegato.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
    ],
  ],
  112 =>
  [
    'code' => 'GV.PO-01 punto 3',
    'title' => 'Approvazione delle politiche di sicurezza informatica',
    'domain' => 'Politica (GV.PO)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 11,
    'description' => '3. Le politiche di cui al punto 1 sono approvate dagli organi di amministrazione e direttivi.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
    ],
  ],
  113 =>
  [
    'code' => 'GV.PO-02 punto 1',
    'title' => 'Aggiornamento annuale delle politiche di sicurezza informatica',
    'domain' => 'Politica (GV.PO)',
    'obligation_type' => 'registration',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 11,
    'description' => 'La politica per la gestione del rischio di cybersecurity è revisionata, aggiornata, comunicata e applicata per riflettere i cambiamenti nei requisiti, nelle minacce, nella tecnologia e nella missione dell\'organizzazione.

1. Le politiche di cui alla misura GV.PO-01 sono riesaminate e, se opportuno, aggiornate periodicamente e comunque almeno con cadenza annuale, nonché qualora si verifichino evoluzioni del contesto normativo in materia di sicurezza informatica, incidenti significativi, variazioni organizzative o mutamenti dell’esposizione alle minacce e ai
relativi rischi.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Policy',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-01 punto 1',
    ],
  ],
  114 =>
  [
    'code' => 'GV.PO-02 punto 2',
    'title' => 'Confronto tra politiche di sicurezza informatica e normativa aggiornata',
    'domain' => 'Politica (GV.PO)',
    'obligation_type' => 'governance',
    'evidence_type' => 'policy',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 11,
    'description' => '2. Ai fini del riesame di cui al punto 1, è verificata almeno la conformità delle politiche di
cui alla misura GV.PO-01 alla normativa in materia di sicurezza informatica.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Valutazione',
    'is_mandatory' => true,
    'is_active' => true,
  ],
  115 =>
  [
    'code' => 'GV.PO-02 punto 3',
    'title' => 'Registro aggiornato degli esiti del riesame annuale',
    'domain' => 'Politica (GV.PO)',
    'obligation_type' => 'registration',
    'evidence_type' => 'register',
    'delegation_level' => 'owner_review',
    'risk_level' => 'not_applicable',
    'sort_order' => 11,
    'description' => '3. È mantenuto un registro aggiornato contenente gli esiti del riesame di cui al punto 1.',
    'minimum_required_documents' => 1,
    'default_document_type_name' => 'Registro',
    'is_mandatory' => true,
    'is_active' => true,
    'parent_requirement_codes' =>
    [
      0 => 'GV.PO-02 punto 1',
    ],
  ],
];

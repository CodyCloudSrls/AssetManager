<?php

return array (
  'superuser' =>
  array (
    'name' => 'Super User',
    'note' => 'Determina se l\'utente ha accesso completo a tutti gli aspetti dell\'amministrazione. Questa impostazione sostituisce TUTTE le autorizzazioni più specifiche e restrittive nel sistema ',
  ),
  'admin' =>
  array (
    'name' => 'Accesso Amministratore',
    'note' => 'Determina se l\'utente ha accesso alla maggior parte degli aspetti del sistema, TRANNE le Impostazioni di Amministrazione di Sistema. Questi utenti potranno gestire utenti, sedi, categorie, ecc., ma SONO vincolati dal Supporto Completo per Più Aziende, se abilitato.',
  ),
  'import' =>
  array (
    'name' => 'Importazione CSV',
    'note' => 'Questo consentirà agli utenti di importare anche se l\'accesso a utenti, asset, ecc è negato altrove.',
  ),
  'reports' =>
  array (
    'name' => 'Rapporti di accesso',
    'note' => 'Determina se l\'utente ha accesso alla sezione Report dell\'applicazione.',
  ),
  'assets' =>
  array (
    'name' => 'Beni',
    'note' => 'Concede l\'accesso alla sezione Assets dell\'applicazione.',
  ),
  'assetsview' =>
  array (
    'name' => 'Visualizza Asset',
  ),
  'assetscreate' =>
  array (
    'name' => 'Crea Nuovi Asset',
  ),
  'assetsedit' =>
  array (
    'name' => 'Modifica Asset',
  ),
  'assetsdelete' =>
  array (
    'name' => 'Elimina Asset',
  ),
  'assetscheckin' =>
  array (
    'name' => 'Check In',
    'note' => 'Controlla gli asset di nuovo nell\'inventario che sono attualmente in fase di verifica.',
  ),
  'assetscheckout' =>
  array (
    'name' => 'Check Out',
    'note' => 'Assegnare asset nell\'inventario verificandole.',
  ),
  'assetsaudit' =>
  array (
    'name' => 'Revisione contabile Asset',
    'note' => 'Consente all\'utente di contrassegnare un asset come fisicamente inventariato.',
  ),
  'assetsviewrequestable' =>
  array (
    'name' => 'Visualizza Asset Richiedibili',
    'note' => 'Consente all\'utente di visualizzare gli asset contrassegnati come richiedibili.',
  ),
  'assetsviewencrypted-custom-fields' =>
  array (
    'name' => 'Visualizza Campi Personalizzati Criptati',
    'note' => 'Consente all\'utente di visualizzare e modificare i campi personalizzati crittografati negli asset.',
  ),
  'accessories' =>
  array (
    'name' => 'Accessori',
    'note' => 'Consente l\'accesso alla sezione Accessori dell\'applicazione.',
  ),
  'accessoriesview' =>
  array (
    'name' => 'Visualizza Accessori',
  ),
  'accessoriescreate' =>
  array (
    'name' => 'Crea Nuovi Accessori',
  ),
  'accessoriesedit' =>
  array (
    'name' => 'Modifica Accessori',
  ),
  'accessoriesdelete' =>
  array (
    'name' => 'Elimina Accessori',
  ),
  'accessoriescheckout' =>
  array (
    'name' => 'Check Out Accessori',
    'note' => 'Assegnare gli accessori nell\'inventario controllandoli.',
  ),
  'accessoriescheckin' =>
  array (
    'name' => 'Check In Accessori',
    'note' => 'Controlla gli accessori di nuovo nell\'inventario che sono attualmente in check out.',
  ),
  'accessoriesfiles' =>
  array (
    'name' => 'Gestisce File Accessori',
    'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati agli accessori.',
  ),
  'documents' =>
  array (
    'name' => 'Documenti',
    'note' => 'Concede l\'accesso alla sezione Documenti dell\'applicazione.',
  ),
  'documentsview' =>
  array (
    'name' => 'Visualizza documenti',
  ),
  'documentscreate' =>
  array (
    'name' => 'Crea nuovi documenti',
  ),
  'documentsedit' =>
  array (
    'name' => 'Modifica documenti',
  ),
  'documentsdelete' =>
  array (
    'name' => 'Elimina documenti',
  ),
  'documentsfiles' =>
  array (
    'name' => 'Gestisci file documenti',
    'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati ai documenti.',
  ),
  'tickets' =>
  array (
    'name' => 'Ticket',
    'note' => 'Concede l\'accesso alla sezione Ticket dell\'applicazione.',
  ),
  'ticketsview' =>
  array (
    'name' => 'Visualizza ticket',
  ),
  'ticketscreate' =>
  array (
    'name' => 'Crea ticket',
  ),
  'ticketsoperate' =>
  array (
    'name' => 'Opera sui ticket',
    'note' => 'Consente all\'utente di aggiornare stato, priorità, tipologia, assegnatario, SLA operativi e di aggiungere commenti e worklog senza accedere alla modifica completa del ticket.',
  ),
  'ticketsedit' =>
  array (
    'name' => 'Modifica ticket',
    'note' => 'Consente la modifica completa del ticket dopo la creazione, inclusi soggetto, descrizione, collegamenti e ownership.',
  ),
  'ticketsdelete' =>
  array (
    'name' => 'Elimina ticket',
  ),
  'ticketsfiles' =>
  array (
    'name' => 'Gestisci file ticket',
    'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati ai ticket.',
  ),
  'documenttypes' =>
  array (
    'name' => 'Tipologie documento',
    'note' => 'Concede l\'accesso alla gestione delle tipologie documento.',
  ),
  'documenttypesview' =>
  array (
    'name' => 'Visualizza tipologie documento',
  ),
  'documenttypescreate' =>
  array (
    'name' => 'Crea tipologie documento',
  ),
  'documenttypesedit' =>
  array (
    'name' => 'Modifica tipologie documento',
  ),
  'documenttypesdelete' =>
  array (
    'name' => 'Elimina tipologie documento',
  ),
  'documentframeworks' =>
  array (
    'name' => 'Framework documentali',
    'note' => 'Concede l\'accesso alla gestione dei framework documentali.',
  ),
  'documentframeworksview' =>
  array (
    'name' => 'Visualizza framework documentali',
  ),
  'documentframeworkscreate' =>
  array (
    'name' => 'Crea framework documentali',
  ),
  'documentframeworksedit' =>
  array (
    'name' => 'Modifica framework documentali',
  ),
  'documentframeworksdelete' =>
  array (
    'name' => 'Elimina framework documentali',
  ),
  'consumables' =>
  array (
    'name' => 'Consumabili',
    'note' => 'Concede l\'accesso alla sezione Consumabili dell\'applicazione.',
  ),
  'consumablesview' =>
  array (
    'name' => 'Visualizza Consumabili',
  ),
  'consumablescreate' =>
  array (
    'name' => 'Crea Nuovi Consumabili',
  ),
  'consumablesedit' =>
  array (
    'name' => 'Modifica Consumabili',
  ),
  'consumablesdelete' =>
  array (
    'name' => 'Elimina Consumabili',
  ),
  'consumablescheckout' =>
  array (
    'name' => 'Check Out Consumabili',
    'note' => 'Assegnare i materiali di consumo nell\'inventario verificandoli.',
  ),
  'consumablesfiles' =>
  array (
    'name' => 'Gestisci file consumabili',
    'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati ai materiali di consumo.',
  ),
  'licenses' =>
  array (
    'name' => 'Licenze',
    'note' => 'Concede l\'accesso alla sezione Licenze dell\'applicazione.',
  ),
  'licensesview' =>
  array (
    'name' => 'Visualizza Licenze',
  ),
  'licensescreate' =>
  array (
    'name' => 'Crea Nuove Licenze',
  ),
  'licensesedit' =>
  array (
    'name' => 'Modifica Licenze',
  ),
  'licensesdelete' =>
  array (
    'name' => 'Elimina Licenze',
  ),
  'licensescheckout' =>
  array (
    'name' => 'Assegna Licenze',
    'note' => 'Consente all\'utente di assegnare licenze ad asset o utenti.',
  ),
  'licensescheckin' =>
  array (
    'name' => 'Annulla assegnazione licenze',
    'note' => 'Consente all\'utente di annullare l\'assegnazione di licenze da asset o utenti.',
  ),
  'licensesfiles' =>
  array (
    'name' => 'Gestisci File Licenza',
    'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati alle licenze.',
  ),
  'licenseskeys' =>
  array (
    'name' => 'Gestisci Chiavi Licenza',
    'note' => 'Consente all\'utente di visualizzare le chiavi del prodotto associate alle licenze.',
  ),
  'components' =>
  array (
    'name' => 'Componenti',
    'note' => 'Consente l\'accesso alla sezione Componenti dell\'applicazione.',
  ),
  'componentsview' =>
  array (
    'name' => 'Visualizza Componenti',
  ),
  'componentscreate' =>
  array (
    'name' => 'Crea Nuovi Componenti',
  ),
  'componentsedit' =>
  array (
    'name' => 'Modifica Componenti',
  ),
  'componentsdelete' =>
  array (
    'name' => 'Elimina Componenti',
  ),
  'componentsfiles' =>
  array (
    'name' => 'Gestisci File Componenti',
    'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati ai componenti.',
  ),
  'componentscheckout' =>
  array (
    'name' => 'Check Out Componenti',
    'note' => 'Assegna componenti nell\'inventario controllandoli.',
  ),
  'componentscheckin' =>
  array (
    'name' => 'Check In Componenti',
    'note' => 'Controlla i componenti di nuovo nell\'inventario che sono attualmente controllati.',
  ),
  'kits' =>
  array (
    'name' => 'Kit Predefiniti',
    'note' => 'Consente l\'accesso alla sezione Kit Predefiniti dell\'applicazione.',
  ),
  'kitsview' =>
  array (
    'name' => 'Visualizza Kit Predefiniti',
  ),
  'kitscreate' =>
  array (
    'name' => 'Crea Nuovi Kit Predefiniti',
  ),
  'kitsedit' =>
  array (
    'name' => 'Modifica Kit Predefiniti',
  ),
  'kitsdelete' =>
  array (
    'name' => 'Elimina Kit Predefiniti',
  ),
  'users' =>
  array (
    'name' => 'Utenti',
    'note' => 'Consente l\'accesso alla sezione Utenti dell\'applicazione.',
  ),
  'usersview' =>
  array (
    'name' => 'Visualizza utenti',
  ),
  'userscreate' =>
  array (
    'name' => 'Crea Nuovi Utenti',
  ),
  'usersedit' =>
  array (
    'name' => 'Modifica Utenti',
  ),
  'usersdelete' =>
  array (
    'name' => 'Elimina Utenti',
  ),
  'models' =>
  array (
    'name' => 'Modelli',
    'note' => 'Consente l\'accesso alla sezione Modelli dell\'applicazione.',
  ),
  'modelsview' =>
  array (
    'name' => 'Visualizza i modelli',
  ),
  'modelscreate' =>
  array (
    'name' => 'Crea Nuovi Modelli',
  ),
  'modelsedit' =>
  array (
    'name' => 'Modifica Modelli',
  ),
  'modelsdelete' =>
  array (
    'name' => 'Elimina Modelli',
  ),
  'categories' =>
  array (
    'name' => 'Categorie',
    'note' => 'Concede l\'accesso alla sezione Categorie dell\'applicazione.',
  ),
  'categoriesview' =>
  array (
    'name' => 'Visualizza Categorie',
  ),
  'categoriescreate' =>
  array (
    'name' => 'Crea Nuove Categorie',
  ),
  'categoriesedit' =>
  array (
    'name' => 'Modifica Categorie',
  ),
  'categoriesdelete' =>
  array (
    'name' => 'Elimina Categorie',
  ),
  'departments' =>
  array (
    'name' => 'Reparti',
    'note' => 'Concede l\'accesso alla sezione Dipartimenti dell\'applicazione.',
  ),
  'departmentsview' =>
  array (
    'name' => 'Visualizza Dipartimenti',
  ),
  'departmentscreate' =>
  array (
    'name' => 'Crea nuovi dipartimenti',
  ),
  'departmentsedit' =>
  array (
    'name' => 'Modifica Dipartimenti',
  ),
  'departmentsdelete' =>
  array (
    'name' => 'Elimina Dipartimenti',
  ),
  'locations' =>
  array (
    'name' => 'Sedi',
    'note' => 'Concede l\'accesso alla sezione Posizioni dell\'applicazione.',
  ),
  'locationsview' =>
  array (
    'name' => 'Visualizza Posizioni',
  ),
  'locationscreate' =>
  array (
    'name' => 'Crea Nuove Posizioni',
  ),
  'locationsedit' =>
  array (
    'name' => 'Modifica Posizioni',
  ),
  'locationsdelete' =>
  array (
    'name' => 'Elimina Posizioni',
  ),
  'status-labels' =>
  array (
    'name' => 'Etichette di Stato',
    'note' => 'Concede l\'accesso alla sezione Etichette di stato dell\'applicazione utilizzata da Assets.',
  ),
  'statuslabelsview' =>
  array (
    'name' => 'Visualizza Etichette Di Stato',
  ),
  'statuslabelscreate' =>
  array (
    'name' => 'Crea nuove etichette di stato',
  ),
  'statuslabelsedit' =>
  array (
    'name' => 'Modifica Etichette Di Stato',
  ),
  'statuslabelsdelete' =>
  array (
    'name' => 'Elimina Etichette Di Stato',
  ),
  'custom-fields' =>
  array (
    'name' => 'Campi Personalizzati',
    'note' => 'Consente l\'accesso alla sezione Campi Personalizzati dell\'applicazione utilizzata da Assets.',
  ),
  'customfieldsview' =>
  array (
    'name' => 'Visualizza Campi Personalizzati',
  ),
  'customfieldscreate' =>
  array (
    'name' => 'Crea Nuovi Campi Personalizzati',
  ),
  'customfieldsedit' =>
  array (
    'name' => 'Modifica Campi Personalizzati',
  ),
  'customfieldsdelete' =>
  array (
    'name' => 'Elimina Campi Personalizzati',
  ),
  'suppliers' =>
  array (
    'name' => 'Fornitori',
    'note' => 'Consente l\'accesso alla sezione Fornitori dell\'applicazione.',
  ),
  'suppliersview' =>
  array (
    'name' => 'Visualizza Fornitori',
  ),
  'supplierscreate' =>
  array (
    'name' => 'Crea Nuovi Fornitori',
  ),
  'suppliersedit' =>
  array (
    'name' => 'Modifica Fornitori',
  ),
  'suppliersdelete' =>
  array (
    'name' => 'Elimina Fornitori',
  ),
  'customers' =>
  array (
    'name' => 'Clienti',
    'note' => 'Consente l\'accesso alla sezione Clienti dell\'applicazione.',
  ),
  'customersview' =>
  array (
    'name' => 'Visualizza Clienti',
  ),
  'customerscreate' =>
  array (
    'name' => 'Crea nuovi clienti',
  ),
  'customersedit' =>
  array (
    'name' => 'Modifica clienti',
  ),
  'customersdelete' =>
  array (
    'name' => 'Elimina clienti',
  ),
  'contracts' =>
  array (
    'name' => 'Contratti',
    'note' => 'Consente l\'accesso ai contratti cliente e agli abbonamenti.',
  ),
  'contractsview' =>
  array (
    'name' => 'Visualizza contratti',
  ),
  'contractscreate' =>
  array (
    'name' => 'Crea nuovi contratti',
  ),
  'contractsedit' =>
  array (
    'name' => 'Modifica contratti',
  ),
  'contractsdelete' =>
  array (
    'name' => 'Elimina contratti',
  ),
  'manufacturers' =>
  array (
    'name' => 'Produttori',
    'note' => 'Concede l\'accesso alla sezione Produttori dell\'applicazione.',
  ),
  'manufacturersview' =>
  array (
    'name' => 'Visualizza Produttori',
  ),
  'manufacturerscreate' =>
  array (
    'name' => 'Crea Nuovi Produttori',
  ),
  'manufacturersedit' =>
  array (
    'name' => 'Modifica Produttori',
  ),
  'manufacturersdelete' =>
  array (
    'name' => 'Elimina Produttori',
  ),
  'companies' =>
  array (
    'name' => 'Aziende',
    'note' => 'Concede l\'accesso alla sezione Aziende dell\'applicazione.',
  ),
  'companiesview' =>
  array (
    'name' => 'Visualizza Aziende',
  ),
  'companiescreate' =>
  array (
    'name' => 'Crea Nuove Aziende',
  ),
  'companiesedit' =>
  array (
    'name' => 'Modifica Aziende',
  ),
  'companiesdelete' =>
  array (
    'name' => 'Elimina Aziende',
  ),
  'user-self-accounts' =>
  array (
    'name' => 'Account utente personali',
    'note' => 'Permette agli utenti non-admin di gestire alcuni aspetti dei propri account utente.',
  ),
  'selftwo-factor' =>
  array (
    'name' => 'Gestisci Autenticazione A Due Fattori',
    'note' => 'Consente agli utenti di abilitare, disabilitare e gestire l\'autenticazione a due fattori per i propri account.',
  ),
  'selfapi' =>
  array (
    'name' => 'Gestisci i token API',
    'note' => 'Consente agli utenti di creare, visualizzare e revocare i propri token API. I token utente avranno gli stessi permessi dell\'utente che li ha creati.',
  ),
  'selfedit-location' =>
  array (
    'name' => 'Modifica Posizione',
    'note' => 'Consente agli utenti di modificare la posizione associata al proprio account utente.',
  ),
  'selfcheckout-assets' =>
  array (
    'name' => 'Asset per il self-check-out',
    'note' => 'Consente agli utenti di controllare gli asset a se stessi senza intervento amministratore.',
  ),
  'selfview-purchase-cost' =>
  array (
    'name' => 'Visualizza Costo Acquisto',
    'note' => 'Consente agli utenti di visualizzare il costo di acquisto degli articoli nella vista del proprio account.',
  ),
  'depreciations' =>
  array (
    'name' => 'Gestione degli ammortamenti',
    'note' => 'Consente agli utenti di gestire e visualizzare i dettagli dell\'ammortamento degli asset.',
  ),
  'depreciationsview' =>
  array (
    'name' => 'Visualizza Dettagli Di Ammortamento',
  ),
  'depreciationsedit' =>
  array (
    'name' => 'Modifica Impostazioni Di Ammortamento',
  ),
  'depreciationsdelete' =>
  array (
    'name' => 'Elimina Record Di Ammortamento',
  ),
  'depreciationscreate' =>
  array (
    'name' => 'Crea Record Di Ammortamento',
  ),
  'grant_all' => 'Concedi tutti i permessi per :area',
  'deny_all' => 'Nega tutti i permessi per :area',
  'inherit_all' => 'Eredita tutti i permessi per :area dai gruppi di permessi',
  'grant' => 'Concedi il permesso per :area',
  'deny' => 'Nega il permesso per :area',
  'inherit' => 'Eredita il permesso per :area da gruppi di permessi',
  'use_groups' => 'Si consiglia vivamente di utilizzare i gruppi di permessi invece di assegnare i permessi individuali per una gestione più semplice.',
  'assetsfiles' =>
  array (
    'name' => 'Manage Asset Files',
    'note' => 'Allows the user to upload, download, and delete files associated with assets. (This only makes sense with view privileges or higher.)',
  ),
  'usersfiles' =>
  array (
    'name' => 'Manage User Files',
    'note' => 'Allows the user to upload, download, and delete files associated with users. (This only makes sense with view privileges or higher.)',
  ),
  'modelsfiles' =>
  array (
    'name' => 'Manage Model Files',
    'note' => 'Allows the user to upload, download, and delete files associated with asset models on both the model view and the asset view screens. (This only makes sense with view privileges or higher.)',
  ),
  'departmentsfiles' =>
  array (
    'name' => 'Manage Department Files',
    'note' => 'Allows the user to upload, download, and delete files associated with departments. (This only makes sense with view privileges or higher.)',
  ),
  'suppliersfiles' =>
  array (
    'name' => 'Manage Supplier Files',
    'note' => 'Allows the user to upload, download, and delete files associated with suppliers. (This only makes sense with view privileges or higher.)',
  ),
  'customersfiles' =>
  array (
    'name' => 'Gestione file clienti',
    'note' => 'Consente di caricare, scaricare ed eliminare file associati ai clienti. Ha senso solo insieme al permesso di visualizzazione o superiore.',
  ),
  'locationsfiles' =>
  array (
    'name' => 'Manage Location Files',
    'note' => 'Allows the user to upload, download, and delete files associated with locations.(This only makes sense with view privileges or higher.)',
  ),
  'companiesfiles' =>
  array (
    'name' => 'Manage Company Files',
    'note' => 'Allows the user to upload, download, and delete files associated with companies. (This only makes sense with view privileges or higher.)',
  ),
);

<?php

return [
    'superadmin' => [
        'name' => 'Superadmin piattaforma',
        'note' => 'Determina se l\'utente ha accesso a livello piattaforma, incluse impostazioni di sistema e amministrazione globale. Solo i superadmin piattaforma possono vedere tutti i tenant.',
    ],
    'superuser' => [
        'name' => 'Superadmin tenant',
        'note' => 'Determina se l\'utente ha accesso amministrativo completo nel proprio tenant. Non concede accesso a livello piattaforma o cross-tenant.',
    ],
    'admin' => [
        'name' => 'Accesso Amministratore',
        'note' => 'Determina se l\'utente ha accesso alla maggior parte degli aspetti del sistema, TRANNE le Impostazioni di Amministrazione di Sistema. Questi utenti potranno gestire utenti, sedi, categorie, ecc., ma SONO vincolati dal Supporto Completo per Più Aziende, se abilitato.',
    ],
    'tenants' => [
        'name' => 'Tenant',
        'note' => 'Controlla la visibilità cross-tenant. Senza questa autorizzazione, gli utenti possono entrare solo nei tenant assegnati direttamente o nel tenant della propria azienda.',
    ],
    'tenantsview-all' => [
        'name' => 'Vede tutti i tenant',
        'note' => 'Consente a un superadmin piattaforma di vedere e cambiare su tutti i tenant. Questa autorizzazione non ha effetto per superuser tenant o utenti vincolati a tenant.',
    ],
    'import' => [
        'name' => 'Importazione CSV',
        'note' => 'Questo consentirà agli utenti di importare anche se l\'accesso a utenti, asset, ecc è negato altrove.',
    ],
    'reports' => [
        'name' => 'Rapporti di accesso',
        'note' => 'Determina se l\'utente ha accesso alla sezione Report dell\'applicazione.',
    ],
    'reportsview' => [
        'name' => 'Visualizza tutti i report',
        'note' => 'Consente l\'accesso alla sezione report standard.',
    ],
    'reportsnis-risk-matrixview' => [
        'name' => 'Visualizza report matrice rischio NIS2',
        'note' => 'Consente l\'accesso solo al report matrice rischio NIS2.',
    ],
    'reportsnis-real-coverageview' => [
        'name' => 'Visualizza report copertura reale NIS2',
        'note' => 'Consente l\'accesso solo al report copertura reale NIS2.',
    ],
    'assets' => [
        'name' => 'Beni',
        'note' => 'Concede l\'accesso alla sezione Assets dell\'applicazione.',
    ],
    'assetsview' => [
        'name' => 'Visualizza Asset',
    ],
    'assetscreate' => [
        'name' => 'Crea Nuovi Asset',
    ],
    'assetsedit' => [
        'name' => 'Modifica Asset',
    ],
    'assetsdelete' => [
        'name' => 'Elimina Asset',
    ],
    'assetscheckin' => [
        'name' => 'Check In',
        'note' => 'Controlla gli asset di nuovo nell\'inventario che sono attualmente in fase di verifica.',
    ],
    'assetscheckout' => [
        'name' => 'Check Out',
        'note' => 'Assegnare asset nell\'inventario verificandole.',
    ],
    'assetsaudit' => [
        'name' => 'Revisione contabile Asset',
        'note' => 'Consente all\'utente di contrassegnare un asset come fisicamente inventariato.',
    ],
    'assetsviewrequestable' => [
        'name' => 'Visualizza Asset Richiedibili',
        'note' => 'Consente all\'utente di visualizzare gli asset contrassegnati come richiedibili.',
    ],
    'assetsviewencrypted-custom-fields' => [
        'name' => 'Visualizza Campi Personalizzati Criptati',
        'note' => 'Consente all\'utente di visualizzare e modificare i campi personalizzati crittografati negli asset.',
    ],
    'accessories' => [
        'name' => 'Accessori',
        'note' => 'Consente l\'accesso alla sezione Accessori dell\'applicazione.',
    ],
    'accessoriesview' => [
        'name' => 'Visualizza Accessori',
    ],
    'accessoriescreate' => [
        'name' => 'Crea Nuovi Accessori',
    ],
    'accessoriesedit' => [
        'name' => 'Modifica Accessori',
    ],
    'accessoriesdelete' => [
        'name' => 'Elimina Accessori',
    ],
    'accessoriescheckout' => [
        'name' => 'Check Out Accessori',
        'note' => 'Assegnare gli accessori nell\'inventario controllandoli.',
    ],
    'accessoriescheckin' => [
        'name' => 'Check In Accessori',
        'note' => 'Controlla gli accessori di nuovo nell\'inventario che sono attualmente in check out.',
    ],
    'accessoriesfiles' => [
        'name' => 'Gestisce File Accessori',
        'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati agli accessori.',
    ],
    'documents' => [
        'name' => 'Documenti',
        'note' => 'Concede l\'accesso alla sezione Documenti dell\'applicazione.',
    ],
    'documentsview' => [
        'name' => 'Visualizza documenti',
    ],
    'documentscreate' => [
        'name' => 'Crea nuovi documenti',
    ],
    'documentsedit' => [
        'name' => 'Modifica documenti',
    ],
    'documentsdelete' => [
        'name' => 'Elimina documenti',
    ],
    'documentsfilesview' => [
        'name' => 'Visualizza allegati documenti',
        'note' => 'Consente all\'utente di elencare, aprire e scaricare gli allegati dei documenti senza poterli caricare o eliminare.',
    ],
    'documentsfiles' => [
        'name' => 'Gestisci file documenti',
        'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati ai documenti.',
    ],
    'documentsrestore' => [
        'name' => 'Ripristina documenti',
        'note' => 'Consente all\'utente di ripristinare i documenti eliminati in modo non definitivo.',
    ],
    'documentsforce-delete' => [
        'name' => 'Elimina definitivamente documenti',
        'note' => 'Consente all\'utente di rimuovere definitivamente i documenti dalla vista elementi eliminati.',
    ],
    'documentsrequirementsmap' => [
        'name' => 'Collega documenti ai requisiti',
        'note' => 'Consente all\'utente di collegare documenti ai requisiti dei framework e modificare i dettagli delle evidenze.',
    ],
    'documentsareaadministrationview' => [
        'name' => 'Visualizza documenti amministrazione',
    ],
    'documentsareaadministrationedit' => [
        'name' => 'Modifica documenti amministrazione',
    ],
    'documentsareaadministrationfilesview' => [
        'name' => 'Visualizza allegati documenti amministrazione',
    ],
    'documentsareaadministrationfiles' => [
        'name' => 'Gestisci allegati documenti amministrazione',
    ],
    'documentsareaitview' => [
        'name' => 'Visualizza documenti IT',
    ],
    'documentsareaitedit' => [
        'name' => 'Modifica documenti IT',
    ],
    'documentsareaitfilesview' => [
        'name' => 'Visualizza allegati documenti IT',
    ],
    'documentsareaitfiles' => [
        'name' => 'Gestisci allegati documenti IT',
    ],
    'documentsareacybersecurityview' => [
        'name' => 'Visualizza documenti cybersicurezza',
    ],
    'documentsareacybersecurityedit' => [
        'name' => 'Modifica documenti cybersicurezza',
    ],
    'documentsareacybersecurityfilesview' => [
        'name' => 'Visualizza allegati documenti cybersicurezza',
    ],
    'documentsareacybersecurityfiles' => [
        'name' => 'Gestisci allegati documenti cybersicurezza',
    ],
    'compliancedomains' => [
        'name' => 'Domini compliance',
        'note' => 'Controlla i domini compliance configurabili usati per limitare framework, requisiti, documenti e report.',
    ],
    'compliancedomainsview' => [
        'name' => 'Visualizza domini compliance',
    ],
    'compliancedomainscreate' => [
        'name' => 'Crea domini compliance',
    ],
    'compliancedomainsedit' => [
        'name' => 'Modifica domini compliance',
    ],
    'compliancedomainsdelete' => [
        'name' => 'Elimina domini compliance',
    ],
    'tickets' => [
        'name' => 'Ticket',
        'note' => 'Concede l\'accesso alla sezione Ticket dell\'applicazione.',
    ],
    'ticketsview' => [
        'name' => 'Visualizza ticket',
    ],
    'ticketscreate' => [
        'name' => 'Crea ticket',
    ],
    'ticketsoperate' => [
        'name' => 'Opera sui ticket',
        'note' => 'Consente all\'utente di aggiornare stato, priorità, tipologia, assegnatario, SLA operativi e di aggiungere commenti e worklog senza accedere alla modifica completa del ticket.',
    ],
    'ticketsedit' => [
        'name' => 'Modifica ticket',
        'note' => 'Consente la modifica completa del ticket dopo la creazione, inclusi soggetto, descrizione, collegamenti e ownership.',
    ],
    'ticketsdelete' => [
        'name' => 'Elimina ticket',
    ],
    'ticketsfiles' => [
        'name' => 'Gestisci file ticket',
        'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati ai ticket.',
    ],
    'documenttypes' => [
        'name' => 'Tipologie documento',
        'note' => 'Concede l\'accesso alla gestione delle tipologie documento.',
    ],
    'documenttypesview' => [
        'name' => 'Visualizza tipologie documento',
    ],
    'documenttypescreate' => [
        'name' => 'Crea tipologie documento',
    ],
    'documenttypesedit' => [
        'name' => 'Modifica tipologie documento',
    ],
    'documenttypesdelete' => [
        'name' => 'Elimina tipologie documento',
    ],
    'documentframeworks' => [
        'name' => 'Framework documentali',
        'note' => 'Concede l\'accesso alla gestione dei framework documentali.',
    ],
    'documentframeworksview' => [
        'name' => 'Visualizza framework documentali',
    ],
    'documentframeworkscreate' => [
        'name' => 'Crea framework documentali',
    ],
    'documentframeworksedit' => [
        'name' => 'Modifica framework documentali',
    ],
    'documentframeworksdelete' => [
        'name' => 'Elimina framework documentali',
    ],
    'consumables' => [
        'name' => 'Consumabili',
        'note' => 'Concede l\'accesso alla sezione Consumabili dell\'applicazione.',
    ],
    'consumablesview' => [
        'name' => 'Visualizza Consumabili',
    ],
    'consumablescreate' => [
        'name' => 'Crea Nuovi Consumabili',
    ],
    'consumablesedit' => [
        'name' => 'Modifica Consumabili',
    ],
    'consumablesdelete' => [
        'name' => 'Elimina Consumabili',
    ],
    'consumablescheckout' => [
        'name' => 'Check Out Consumabili',
        'note' => 'Assegnare i materiali di consumo nell\'inventario verificandoli.',
    ],
    'consumablesfiles' => [
        'name' => 'Gestisci file consumabili',
        'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati ai materiali di consumo.',
    ],
    'licenses' => [
        'name' => 'Licenze',
        'note' => 'Concede l\'accesso alla sezione Licenze dell\'applicazione.',
    ],
    'licensesview' => [
        'name' => 'Visualizza Licenze',
    ],
    'licensescreate' => [
        'name' => 'Crea Nuove Licenze',
    ],
    'licensesedit' => [
        'name' => 'Modifica Licenze',
    ],
    'licensesdelete' => [
        'name' => 'Elimina Licenze',
    ],
    'licensescheckout' => [
        'name' => 'Assegna Licenze',
        'note' => 'Consente all\'utente di assegnare licenze ad asset o utenti.',
    ],
    'licensescheckin' => [
        'name' => 'Annulla assegnazione licenze',
        'note' => 'Consente all\'utente di annullare l\'assegnazione di licenze da asset o utenti.',
    ],
    'licensesfiles' => [
        'name' => 'Gestisci File Licenza',
        'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati alle licenze.',
    ],
    'licenseskeys' => [
        'name' => 'Gestisci Chiavi Licenza',
        'note' => 'Consente all\'utente di visualizzare le chiavi del prodotto associate alle licenze.',
    ],
    'components' => [
        'name' => 'Componenti',
        'note' => 'Consente l\'accesso alla sezione Componenti dell\'applicazione.',
    ],
    'componentsview' => [
        'name' => 'Visualizza Componenti',
    ],
    'componentscreate' => [
        'name' => 'Crea Nuovi Componenti',
    ],
    'componentsedit' => [
        'name' => 'Modifica Componenti',
    ],
    'componentsdelete' => [
        'name' => 'Elimina Componenti',
    ],
    'componentsfiles' => [
        'name' => 'Gestisci File Componenti',
        'note' => 'Consente all\'utente di caricare, scaricare ed eliminare i file associati ai componenti.',
    ],
    'componentscheckout' => [
        'name' => 'Check Out Componenti',
        'note' => 'Assegna componenti nell\'inventario controllandoli.',
    ],
    'componentscheckin' => [
        'name' => 'Check In Componenti',
        'note' => 'Controlla i componenti di nuovo nell\'inventario che sono attualmente controllati.',
    ],
    'kits' => [
        'name' => 'Kit Predefiniti',
        'note' => 'Consente l\'accesso alla sezione Kit Predefiniti dell\'applicazione.',
    ],
    'kitsview' => [
        'name' => 'Visualizza Kit Predefiniti',
    ],
    'kitscreate' => [
        'name' => 'Crea Nuovi Kit Predefiniti',
    ],
    'kitsedit' => [
        'name' => 'Modifica Kit Predefiniti',
    ],
    'kitsdelete' => [
        'name' => 'Elimina Kit Predefiniti',
    ],
    'users' => [
        'name' => 'Utenti',
        'note' => 'Consente l\'accesso alla sezione Utenti dell\'applicazione.',
    ],
    'usersview' => [
        'name' => 'Visualizza utenti',
    ],
    'userscreate' => [
        'name' => 'Crea Nuovi Utenti',
    ],
    'usersedit' => [
        'name' => 'Modifica Utenti',
    ],
    'usersdelete' => [
        'name' => 'Elimina Utenti',
    ],
    'models' => [
        'name' => 'Modelli',
        'note' => 'Consente l\'accesso alla sezione Modelli dell\'applicazione.',
    ],
    'modelsview' => [
        'name' => 'Visualizza i modelli',
    ],
    'modelscreate' => [
        'name' => 'Crea Nuovi Modelli',
    ],
    'modelsedit' => [
        'name' => 'Modifica Modelli',
    ],
    'modelsdelete' => [
        'name' => 'Elimina Modelli',
    ],
    'categories' => [
        'name' => 'Categorie',
        'note' => 'Concede l\'accesso alla sezione Categorie dell\'applicazione.',
    ],
    'categoriesview' => [
        'name' => 'Visualizza Categorie',
    ],
    'categoriescreate' => [
        'name' => 'Crea Nuove Categorie',
    ],
    'categoriesedit' => [
        'name' => 'Modifica Categorie',
    ],
    'categoriesdelete' => [
        'name' => 'Elimina Categorie',
    ],
    'departments' => [
        'name' => 'Reparti',
        'note' => 'Concede l\'accesso alla sezione Dipartimenti dell\'applicazione.',
    ],
    'departmentsview' => [
        'name' => 'Visualizza Dipartimenti',
    ],
    'departmentscreate' => [
        'name' => 'Crea nuovi dipartimenti',
    ],
    'departmentsedit' => [
        'name' => 'Modifica Dipartimenti',
    ],
    'departmentsdelete' => [
        'name' => 'Elimina Dipartimenti',
    ],
    'locations' => [
        'name' => 'Sedi',
        'note' => 'Concede l\'accesso alla sezione Posizioni dell\'applicazione.',
    ],
    'locationsview' => [
        'name' => 'Visualizza Posizioni',
    ],
    'locationscreate' => [
        'name' => 'Crea Nuove Posizioni',
    ],
    'locationsedit' => [
        'name' => 'Modifica Posizioni',
    ],
    'locationsdelete' => [
        'name' => 'Elimina Posizioni',
    ],
    'status-labels' => [
        'name' => 'Etichette di Stato',
        'note' => 'Concede l\'accesso alla sezione Etichette di stato dell\'applicazione utilizzata da Assets.',
    ],
    'statuslabelsview' => [
        'name' => 'Visualizza Etichette Di Stato',
    ],
    'statuslabelscreate' => [
        'name' => 'Crea nuove etichette di stato',
    ],
    'statuslabelsedit' => [
        'name' => 'Modifica Etichette Di Stato',
    ],
    'statuslabelsdelete' => [
        'name' => 'Elimina Etichette Di Stato',
    ],
    'custom-fields' => [
        'name' => 'Campi Personalizzati',
        'note' => 'Consente l\'accesso alla sezione Campi Personalizzati dell\'applicazione utilizzata da Assets.',
    ],
    'customfieldsview' => [
        'name' => 'Visualizza Campi Personalizzati',
    ],
    'customfieldscreate' => [
        'name' => 'Crea Nuovi Campi Personalizzati',
    ],
    'customfieldsedit' => [
        'name' => 'Modifica Campi Personalizzati',
    ],
    'customfieldsdelete' => [
        'name' => 'Elimina Campi Personalizzati',
    ],
    'suppliers' => [
        'name' => 'Fornitori',
        'note' => 'Consente l\'accesso alla sezione Fornitori dell\'applicazione.',
    ],
    'suppliersview' => [
        'name' => 'Visualizza Fornitori',
    ],
    'supplierscreate' => [
        'name' => 'Crea Nuovi Fornitori',
    ],
    'suppliersedit' => [
        'name' => 'Modifica Fornitori',
    ],
    'suppliersdelete' => [
        'name' => 'Elimina Fornitori',
    ],
    'customers' => [
        'name' => 'Clienti',
        'note' => 'Consente l\'accesso alla sezione Clienti dell\'applicazione.',
    ],
    'customersview' => [
        'name' => 'Visualizza Clienti',
    ],
    'customerscreate' => [
        'name' => 'Crea nuovi clienti',
    ],
    'customersedit' => [
        'name' => 'Modifica clienti',
    ],
    'customersdelete' => [
        'name' => 'Elimina clienti',
    ],
    'contracts' => [
        'name' => 'Contratti',
        'note' => 'Consente l\'accesso ai contratti cliente e agli abbonamenti.',
    ],
    'contractsview' => [
        'name' => 'Visualizza contratti',
    ],
    'contractscreate' => [
        'name' => 'Crea nuovi contratti',
    ],
    'contractsedit' => [
        'name' => 'Modifica contratti',
    ],
    'contractsdelete' => [
        'name' => 'Elimina contratti',
    ],
    'manufacturers' => [
        'name' => 'Produttori',
        'note' => 'Concede l\'accesso alla sezione Produttori dell\'applicazione.',
    ],
    'manufacturersview' => [
        'name' => 'Visualizza Produttori',
    ],
    'manufacturerscreate' => [
        'name' => 'Crea Nuovi Produttori',
    ],
    'manufacturersedit' => [
        'name' => 'Modifica Produttori',
    ],
    'manufacturersdelete' => [
        'name' => 'Elimina Produttori',
    ],
    'companies' => [
        'name' => 'Aziende',
        'note' => 'Concede l\'accesso alla sezione Aziende dell\'applicazione.',
    ],
    'companiesview' => [
        'name' => 'Visualizza Aziende',
    ],
    'companiescreate' => [
        'name' => 'Crea Nuove Aziende',
    ],
    'companiesedit' => [
        'name' => 'Modifica Aziende',
    ],
    'companiesdelete' => [
        'name' => 'Elimina Aziende',
    ],
    'user-self-accounts' => [
        'name' => 'Account utente personali',
        'note' => 'Permette agli utenti non-admin di gestire alcuni aspetti dei propri account utente.',
    ],
    'selftwo-factor' => [
        'name' => 'Gestisci Autenticazione A Due Fattori',
        'note' => 'Consente agli utenti di abilitare, disabilitare e gestire l\'autenticazione a due fattori per i propri account.',
    ],
    'selfapi' => [
        'name' => 'Gestisci i token API',
        'note' => 'Consente agli utenti di creare, visualizzare e revocare i propri token API. I token utente avranno gli stessi permessi dell\'utente che li ha creati.',
    ],
    'selfedit-location' => [
        'name' => 'Modifica Posizione',
        'note' => 'Consente agli utenti di modificare la posizione associata al proprio account utente.',
    ],
    'selfcheckout-assets' => [
        'name' => 'Asset per il self-check-out',
        'note' => 'Consente agli utenti di controllare gli asset a se stessi senza intervento amministratore.',
    ],
    'selfview-purchase-cost' => [
        'name' => 'Visualizza Costo Acquisto',
        'note' => 'Consente agli utenti di visualizzare il costo di acquisto degli articoli nella vista del proprio account.',
    ],
    'depreciations' => [
        'name' => 'Gestione degli ammortamenti',
        'note' => 'Consente agli utenti di gestire e visualizzare i dettagli dell\'ammortamento degli asset.',
    ],
    'depreciationsview' => [
        'name' => 'Visualizza Dettagli Di Ammortamento',
    ],
    'depreciationsedit' => [
        'name' => 'Modifica Impostazioni Di Ammortamento',
    ],
    'depreciationsdelete' => [
        'name' => 'Elimina Record Di Ammortamento',
    ],
    'depreciationscreate' => [
        'name' => 'Crea Record Di Ammortamento',
    ],
    'grant_all' => 'Concedi tutti i permessi per :area',
    'deny_all' => 'Nega tutti i permessi per :area',
    'inherit_all' => 'Eredita tutti i permessi per :area dai gruppi di permessi',
    'grant' => 'Concedi il permesso per :area',
    'deny' => 'Nega il permesso per :area',
    'inherit' => 'Eredita il permesso per :area da gruppi di permessi',
    'use_groups' => 'Si consiglia vivamente di utilizzare i gruppi di permessi invece di assegnare i permessi individuali per una gestione più semplice.',
    'assetsfiles' => [
        'name' => 'Manage Asset Files',
        'note' => 'Allows the user to upload, download, and delete files associated with assets. (This only makes sense with view privileges or higher.)',
    ],
    'usersfiles' => [
        'name' => 'Manage User Files',
        'note' => 'Allows the user to upload, download, and delete files associated with users. (This only makes sense with view privileges or higher.)',
    ],
    'modelsfiles' => [
        'name' => 'Manage Model Files',
        'note' => 'Allows the user to upload, download, and delete files associated with asset models on both the model view and the asset view screens. (This only makes sense with view privileges or higher.)',
    ],
    'departmentsfiles' => [
        'name' => 'Manage Department Files',
        'note' => 'Allows the user to upload, download, and delete files associated with departments. (This only makes sense with view privileges or higher.)',
    ],
    'suppliersfiles' => [
        'name' => 'Manage Supplier Files',
        'note' => 'Allows the user to upload, download, and delete files associated with suppliers. (This only makes sense with view privileges or higher.)',
    ],
    'customersfiles' => [
        'name' => 'Gestione file clienti',
        'note' => 'Consente di caricare, scaricare ed eliminare file associati ai clienti. Ha senso solo insieme al permesso di visualizzazione o superiore.',
    ],
    'locationsfiles' => [
        'name' => 'Manage Location Files',
        'note' => 'Allows the user to upload, download, and delete files associated with locations.(This only makes sense with view privileges or higher.)',
    ],
    'companiesfiles' => [
        'name' => 'Manage Company Files',
        'note' => 'Allows the user to upload, download, and delete files associated with companies. (This only makes sense with view privileges or higher.)',
    ],
];

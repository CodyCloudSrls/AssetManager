<?php

return array (
  'superuser' =>
  array (
    'name' => 'Super Benutzer',
    'note' => 'Legt fest, ob der Benutzer vollen Zugriff auf alle Aspekte des Administrators hat. Diese Einstellung überschreibt ALLE spezifischeren und restriktiveren Berechtigungen im gesamten System. ',
  ),
  'admin' =>
  array (
    'name' => 'Admin-Zugriff',
    'note' => 'Legt fest, ob der Benutzer Zugriff auf die meisten Aspekte des Systems AUSSER in den Systemeinstellungen hat. Diese Benutzer werden in der Lage sein, Benutzer, Standorte, Kategorien, etc, zu verwalten, aber SIND beschränkt durch die Volle Mehrmandanten-Unterstützung für Firmen, wenn sie aktiviert ist.',
  ),
  'import' =>
  array (
    'name' => 'CSV-Import',
    'note' => 'Dies wird Benutzern erlauben zu importieren, auch wenn der Zugriff auf Benutzer, Gegenstände usw. an anderer Stelle verweigert wird.',
  ),
  'reports' =>
  array (
    'name' => 'Berichtszugriff',
    'note' => 'Legt fest, ob der Benutzer Zugriff auf den Berichte-Abschnitt der Anwendung hat.',
  ),
  'assets' =>
  array (
    'name' => 'Assets',
    'note' => 'Gewährt Zugriff auf den Bereich "Assets" in der Anwendung.',
  ),
  'assetsview' =>
  array (
    'name' => 'Assets Anzeigen',
  ),
  'assetscreate' =>
  array (
    'name' => 'Neue Assets Erstellen',
  ),
  'assetsedit' =>
  array (
    'name' => 'Assets Bearbeiten',
  ),
  'assetsdelete' =>
  array (
    'name' => 'Assets Löschen',
  ),
  'assetscheckin' =>
  array (
    'name' => 'Einchecken',
    'note' => 'Checken Sie die derzeit ausgebuchten Assets wieder in das Inventar ein.',
  ),
  'assetscheckout' =>
  array (
    'name' => 'Auschecken',
    'note' => 'Assets im Inventar zuweisen, indem sie ausgecheckt werden.',
  ),
  'assetsaudit' =>
  array (
    'name' => 'Assets Prüfung',
    'note' => 'Ermöglicht dem Benutzer, ein Asset als physisch inventarisiert zu markieren.',
  ),
  'assetsviewrequestable' =>
  array (
    'name' => 'Anforderbare Assets anzeigen',
    'note' => 'Ermöglicht dem Benutzer, Assets anzusehen, die als anforderbar markiert sind.',
  ),
  'assetsviewencrypted-custom-fields' =>
  array (
    'name' => 'Verschlüsselte Benutzerdefinierte Felder ansehen',
    'note' => 'Ermöglicht dem Benutzer, verschlüsselte benutzerdefinierte Felder auf Assets anzusehen und zu ändern.',
  ),
  'accessories' =>
  array (
    'name' => 'Zubehör',
    'note' => 'Gewährt Zugriff auf den Bereich "Zubehör" in der Anwendung.',
  ),
  'accessoriesview' =>
  array (
    'name' => 'Zubehör Ansehen',
  ),
  'accessoriescreate' =>
  array (
    'name' => 'Neues Zubehör erstellen',
  ),
  'accessoriesedit' =>
  array (
    'name' => 'Zubehör Bearbeiten',
  ),
  'accessoriesdelete' =>
  array (
    'name' => 'Zubehör Löschen',
  ),
  'accessoriescheckout' =>
  array (
    'name' => 'Zubehör Auschecken',
    'note' => 'Zubehör im Inventar zuweisen, indem sie ausgecheckt werden.',
  ),
  'accessoriescheckin' =>
  array (
    'name' => 'Zubehör Einchecken',
    'note' => 'Checken Sie die derzeit ausgebuchtes Zubehör wieder in das Inventar ein.',
  ),
  'accessoriesfiles' =>
  array (
    'name' => 'Zubehördateien Verwalten',
    'note' => 'Ermöglicht dem Benutzer das Hochladen, Herunterladen und Löschen in Verbindung mit Zubehör.',
  ),
  'consumables' =>
  array (
    'name' => 'Verbrauchsmaterialien',
    'note' => 'Gewährt Zugriff auf den Bereich "Verbrauchsmaterialien" in der Anwendung.',
  ),
  'consumablesview' =>
  array (
    'name' => 'Verbrauchsmaterialien Ansehen',
  ),
  'consumablescreate' =>
  array (
    'name' => 'Neue Verbrauchsmaterialien erstellen',
  ),
  'consumablesedit' =>
  array (
    'name' => 'Verbrauchsmaterialien Bearbeiten',
  ),
  'consumablesdelete' =>
  array (
    'name' => 'Verbrauchsmaterialien Löschen',
  ),
  'consumablescheckout' =>
  array (
    'name' => 'Verbrauchsmaterialien Auschecken',
    'note' => 'Verbrauchsmaterialien im Inventar zuweisen, indem sie ausgecheckt werden.',
  ),
  'consumablesfiles' =>
  array (
    'name' => 'Verbrauchsdateien verwalten',
    'note' => 'Ermöglicht dem Benutzer das Hochladen, Herunterladen und Löschen in Verbindung mit Verbrauchsmaterialien.',
  ),
  'licenses' =>
  array (
    'name' => 'Lizenzen',
    'note' => 'Gewährt Zugriff auf den Bereich "Lizenzen" in der Anwendung.',
  ),
  'licensesview' =>
  array (
    'name' => 'Lizenzen Ansehen',
  ),
  'licensescreate' =>
  array (
    'name' => 'Neue Lizenz erstellen',
  ),
  'licensesedit' =>
  array (
    'name' => 'Lizenz Bearbeiten',
  ),
  'licensesdelete' =>
  array (
    'name' => 'Lizenzen Löschen',
  ),
  'licensescheckout' =>
  array (
    'name' => 'Lizenzen Zuweisen',
    'note' => 'Ermöglicht dem Benutzer, Assets oder Benutzern Lizenzen zuzuweisen.',
  ),
  'licensescheckin' =>
  array (
    'name' => 'Zuweisung von Lizenzen Aufheben',
    'note' => 'Ermöglicht dem Benutzer, die Zuweisung von Lizenzen von Assets oder Benutzern aufzuheben.',
  ),
  'licensesfiles' =>
  array (
    'name' => 'Lizenzdateien Verwalten',
    'note' => 'Ermöglicht dem Benutzer das Hochladen, Herunterladen und Löschen in Verbindung mit Lizenzen.',
  ),
  'licenseskeys' =>
  array (
    'name' => 'Lizenzschlüssel Verwalten',
    'note' => 'Ermöglicht dem Benutzer, Produktschlüssel anzuzeigen, die mit Lizenzen verknüpft sind.',
  ),
  'components' =>
  array (
    'name' => 'Komponenten',
    'note' => 'Gewährt Zugriff auf den Bereich "Komponenten" in der Anwendung.',
  ),
  'componentsview' =>
  array (
    'name' => 'Komponenten Anzeigen',
  ),
  'componentscreate' =>
  array (
    'name' => 'Neue Komponenten Erstellen',
  ),
  'componentsedit' =>
  array (
    'name' => 'Komponenten Bearbeiten',
  ),
  'componentsdelete' =>
  array (
    'name' => 'Komponenten Löschen',
  ),
  'componentsfiles' =>
  array (
    'name' => 'Komponentendateien Verwalten',
    'note' => 'Ermöglicht dem Benutzer das Hochladen, Herunterladen und Löschen in Verbindung mit Komponenten.',
  ),
  'componentscheckout' =>
  array (
    'name' => 'Komponenten Auschecken',
    'note' => 'Komponenten im Inventar zuweisen, indem sie ausgecheckt werden.',
  ),
  'componentscheckin' =>
  array (
    'name' => 'Komponenten einchecken',
    'note' => 'Checken Sie die derzeit ausgebuchten Komponenten wieder in das Inventar ein.',
  ),
  'kits' =>
  array (
    'name' => 'Vordefinierte Kits',
    'note' => 'Gewährt Zugriff auf den Abschnitt "Vordefinierte Kits" in der Anwendung.',
  ),
  'kitsview' =>
  array (
    'name' => 'Vordefinierte Kits Anzeigen',
  ),
  'kitscreate' =>
  array (
    'name' => 'Vordefiniertes Kits Erstellen',
  ),
  'kitsedit' =>
  array (
    'name' => 'Vordefinierte Kits Bearbeiten',
  ),
  'kitsdelete' =>
  array (
    'name' => 'Vordefinierte Kits Löschen',
  ),
  'users' =>
  array (
    'name' => 'Benutzer',
    'note' => 'Gewährt Zugriff auf den Bereich "Benutzer" in der Anwendung.',
  ),
  'usersview' =>
  array (
    'name' => 'Benutzer anzeigen',
  ),
  'userscreate' =>
  array (
    'name' => 'Neue Benutzer Anlegen',
  ),
  'usersedit' =>
  array (
    'name' => 'Benutzer Bearbeiten',
  ),
  'usersdelete' =>
  array (
    'name' => 'Benutzer löschen',
  ),
  'models' =>
  array (
    'name' => 'Modelle',
    'note' => 'Gewährt Zugriff auf den Bereich "Modelle" in der Anwendung.',
  ),
  'modelsview' =>
  array (
    'name' => 'Modelle anzeigen',
  ),
  'modelscreate' =>
  array (
    'name' => 'Neue Modelle Erstellen',
  ),
  'modelsedit' =>
  array (
    'name' => 'Modelle Bearbeiten',
  ),
  'modelsdelete' =>
  array (
    'name' => 'Modelle Löschen',
  ),
  'categories' =>
  array (
    'name' => 'Kategorien',
    'note' => 'Gewährt Zugriff auf den Bereich "Kategorien" in der Anwendung.',
  ),
  'categoriesview' =>
  array (
    'name' => 'Kategorien Anzeigen',
  ),
  'categoriescreate' =>
  array (
    'name' => 'Neue Kategorien Erstellen',
  ),
  'categoriesedit' =>
  array (
    'name' => 'Kategorien Bearbeiten',
  ),
  'categoriesdelete' =>
  array (
    'name' => 'Kategorien Löschen',
  ),
  'departments' =>
  array (
    'name' => 'Abteilungen',
    'note' => 'Gewährt Zugriff auf den Bereich "Abteilungen" in der Anwendung.',
  ),
  'departmentsview' =>
  array (
    'name' => 'Abteilungen Anzeigen',
  ),
  'departmentscreate' =>
  array (
    'name' => 'Neue Abteilungen Erstellen',
  ),
  'departmentsedit' =>
  array (
    'name' => 'Abteilungen Bearbeiten',
  ),
  'departmentsdelete' =>
  array (
    'name' => 'Abteilungen Löschen',
  ),
  'locations' =>
  array (
    'name' => 'Standorte',
    'note' => 'Gewährt Zugriff auf den Bereich "Standorte" in der Anwendung.',
  ),
  'locationsview' =>
  array (
    'name' => 'Standorte Anzeigen',
  ),
  'locationscreate' =>
  array (
    'name' => 'Neue Standorte Erstellen',
  ),
  'locationsedit' =>
  array (
    'name' => 'Standorte Bearbeiten',
  ),
  'locationsdelete' =>
  array (
    'name' => 'Standorte Löschen',
  ),
  'status-labels' =>
  array (
    'name' => 'Statusbezeichnungen',
    'note' => 'Gewährt Zugriff auf den Bereich "Statusbezeichnungen", die für Assets benutzt werden.',
  ),
  'statuslabelsview' =>
  array (
    'name' => 'Statusbezeichnungen Anzeigen',
  ),
  'statuslabelscreate' =>
  array (
    'name' => 'Neue Statusbezeichnungen Erstellen',
  ),
  'statuslabelsedit' =>
  array (
    'name' => 'Statusbezeichnungen Bearbeiten',
  ),
  'statuslabelsdelete' =>
  array (
    'name' => 'Statusbezeichnungen Löschen',
  ),
  'custom-fields' =>
  array (
    'name' => 'Benutzerdefinierte Felder',
    'note' => 'Gewährt Zugriff auf den Bereich "Benutzerdefinierte Felder", die für Assets benutzt werden.',
  ),
  'customfieldsview' =>
  array (
    'name' => 'Benutzerdefinierte Felder Anzeigen',
  ),
  'customfieldscreate' =>
  array (
    'name' => 'Neue Benutzerdefinierte Felder Erstellen',
  ),
  'customfieldsedit' =>
  array (
    'name' => 'Benutzerdefinierte Felder Bearbeiten',
  ),
  'customfieldsdelete' =>
  array (
    'name' => 'Benutzerdefinierte Felder Löschen',
  ),
  'suppliers' =>
  array (
    'name' => 'Lieferanten',
    'note' => 'Gewährt Zugriff auf den Bereich "Lieferanten" in der Anwendung.',
  ),
  'suppliersview' =>
  array (
    'name' => 'Lieferanten Anzeigen',
  ),
  'supplierscreate' =>
  array (
    'name' => 'Neue Lieferanten Erstellen',
  ),
  'suppliersedit' =>
  array (
    'name' => 'Lieferanten Bearbeiten',
  ),
  'suppliersdelete' =>
  array (
    'name' => 'Lieferanten Löschen',
  ),
  'manufacturers' =>
  array (
    'name' => 'Hersteller',
    'note' => 'Gewährt Zugriff auf den Bereich "Hersteller" in der Anwendung.',
  ),
  'manufacturersview' =>
  array (
    'name' => 'Hersteller Anzeigen',
  ),
  'manufacturerscreate' =>
  array (
    'name' => 'Neue Hersteller Erstellen',
  ),
  'manufacturersedit' =>
  array (
    'name' => 'Hersteller Bearbeiten',
  ),
  'manufacturersdelete' =>
  array (
    'name' => 'Hersteller Löschen',
  ),
  'companies' =>
  array (
    'name' => 'Firmen',
    'note' => 'Gewährt Zugriff auf den Bereich "Firmen" in der Anwendung.',
  ),
  'companiesview' =>
  array (
    'name' => 'Firmen Anzeigen',
  ),
  'companiescreate' =>
  array (
    'name' => 'Neue Firmen Erstellen',
  ),
  'companiesedit' =>
  array (
    'name' => 'Firmen Bearbeiten',
  ),
  'companiesdelete' =>
  array (
    'name' => 'Firmen Löschen',
  ),
  'user-self-accounts' =>
  array (
    'name' => 'Benutzerkonten',
    'note' => 'Erlaubt Nicht-Administratoren die Möglichkeit, bestimmte Aspekte ihrer eigenen Benutzerkonten zu verwalten.',
  ),
  'selftwo-factor' =>
  array (
    'name' => 'Zwei-Faktor-Authentifizierung Verwalten',
    'note' => 'Erlaubt Benutzern die Zwei-Faktor-Authentifizierung für ihre eigenen Konten zu aktivieren, zu deaktivieren und zu verwalten.',
  ),
  'selfapi' =>
  array (
    'name' => 'API-Schlüssel Verwalten',
    'note' => 'Ermöglicht Benutzern, eigene API-Token zu erstellen, anzuschauen und zu widerrufen. Benutzer-Token haben die gleichen Berechtigungen wie der Benutzer, der sie erstellt hat.',
  ),
  'selfedit-location' =>
  array (
    'name' => 'Standort Bearbeiten',
    'note' => 'Ermöglicht Benutzern den Standort zu bearbeiten, der mit ihrem eigenen Benutzerkonto verknüpft ist.',
  ),
  'selfcheckout-assets' =>
  array (
    'name' => 'Assets Selbst Auschecken',
    'note' => 'Erlaubt es Benutzern Assets ohne Admin-Intervention selbst auszuchecken.',
  ),
  'selfview-purchase-cost' =>
  array (
    'name' => 'Kaufpreis Anzeigen',
    'note' => 'Ermöglicht den Benutzern, die Kaufpreis von Artikeln in ihrer Account-Ansicht anzuzeigen.',
  ),
  'depreciations' =>
  array (
    'name' => 'Abschreibungs-Verwaltung',
    'note' => 'Ermöglicht Benutzern das Verwalten und Anzeigen von Vermögensabschreibungsdaten.',
  ),
  'depreciationsview' =>
  array (
    'name' => 'Abschreibungsdetails Anzeigen',
  ),
  'depreciationsedit' =>
  array (
    'name' => 'Abschreibungseinstellungen Bearbeiten',
  ),
  'depreciationsdelete' =>
  array (
    'name' => 'Abschreibungs-Aufzeichnungen Löschen',
  ),
  'depreciationscreate' =>
  array (
    'name' => 'Abschreibungs-Aufzeichnungen Erstellen',
  ),
  'grant_all' => 'Erteilen Sie alle Berechtigungen für :area',
  'deny_all' => 'Verweigerung aller Berechtigungen für :area',
  'inherit_all' => 'Alle Berechtigungen für :area von Berechtigungsgruppen Vererben',
  'grant' => 'Erteilung von Berechtigungen für :area',
  'deny' => 'Verweigerung von Berechtigungen für :area',
  'inherit' => 'Berechtigungen für :area von Berechtigungsgruppen Vererben',
  'use_groups' => 'Wir empfehlen dringend, Berechtigungsgruppen zu verwenden, anstatt individuelle Berechtigungen für eine einfachere Verwaltung zuzuweisen.',
  'documenttypes' =>
  array (
    'name' => 'Document Types',
    'note' => 'Grants access to document type settings.',
  ),
  'documenttypesview' =>
  array (
    'name' => 'View Document Types',
  ),
  'documenttypescreate' =>
  array (
    'name' => 'Create Document Types',
  ),
  'documenttypesedit' =>
  array (
    'name' => 'Edit Document Types',
  ),
  'documenttypesdelete' =>
  array (
    'name' => 'Delete Document Types',
  ),
  'documentframeworks' =>
  array (
    'name' => 'Document Frameworks',
    'note' => 'Grants access to document framework settings.',
  ),
  'documentframeworksview' =>
  array (
    'name' => 'View Document Frameworks',
  ),
  'documentframeworkscreate' =>
  array (
    'name' => 'Create Document Frameworks',
  ),
  'documentframeworksedit' =>
  array (
    'name' => 'Edit Document Frameworks',
  ),
  'documentframeworksdelete' =>
  array (
    'name' => 'Delete Document Frameworks',
  ),
  'assetsfiles' =>
  array (
    'name' => 'Manage Asset Files',
    'note' => 'Allows the user to upload, download, and delete files associated with assets. (This only makes sense with view privileges or higher.)',
  ),
  'documents' =>
  array (
    'name' => 'Documents',
    'note' => 'Grants access to the Documents section of the application.',
  ),
  'documentsview' =>
  array (
    'name' => 'View Documents',
  ),
  'documentscreate' =>
  array (
    'name' => 'Create New Documents',
  ),
  'documentsedit' =>
  array (
    'name' => 'Edit Documents',
  ),
  'documentsdelete' =>
  array (
    'name' => 'Delete Documents',
  ),
  'documentsfiles' =>
  array (
    'name' => 'Manage Document Files',
    'note' => 'Allows the user to upload, download, and delete files associated with documents.',
  ),
  'tickets' =>
  array (
    'name' => 'Tickets',
    'note' => 'Grants access to the Tickets section of the application.',
  ),
  'ticketsview' =>
  array (
    'name' => 'View Tickets',
  ),
  'ticketscreate' =>
  array (
    'name' => 'Create Tickets',
  ),
  'ticketsoperate' =>
  array (
    'name' => 'Operate Tickets',
    'note' => 'Allows the user to update ticket status, priority, type, assignee, operational SLA fields, and add comments and worklogs without full ticket editing access.',
  ),
  'ticketsedit' =>
  array (
    'name' => 'Edit Tickets',
    'note' => 'Allows full ticket editing after creation, including subject, description, links, and ownership.',
  ),
  'ticketsdelete' =>
  array (
    'name' => 'Delete Tickets',
  ),
  'ticketsfiles' =>
  array (
    'name' => 'Manage Ticket Files',
    'note' => 'Allows the user to upload, download, and delete files associated with tickets.',
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

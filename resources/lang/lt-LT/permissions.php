<?php

return array (
  'superuser' =>
  array (
    'name' => 'Super naudotojas',
    'note' => 'Nustato, ar naudotojas turi visišką prieigą prie visų administratoriaus funkcijų. Šis nustatymas pakeičia VISAS konkretesnes ir ribojančias teises visoje sistemoje. ',
  ),
  'admin' =>
  array (
    'name' => 'Administratoriaus prieiga',
    'note' => 'Nustato, ar naudotojas turi prieigą prie daugumos sistemos funkcijų, IŠSKYRUS sistemos administratoriaus nustatymus. Šie naudotojai galės valdyti naudotojus, vietas, kategorijas ir kt., tačiau jų prieiga YRA ribojama Pilno kelių įmonių palaikymo, kai jis įjungtas.',
  ),
  'import' =>
  array (
    'name' => 'CSV importavimas',
    'note' => 'Tai leis naudotojams importuoti duomenis, net jei prieiga prie naudotojų, turto ir pan. visur kitur yra draudžiama.',
  ),
  'reports' =>
  array (
    'name' => 'Prieiga prie ataskaitų',
    'note' => 'Nustato, ar naudotojas turi prieigą prie programos Ataskaitų skilties.',
  ),
  'assets' =>
  array (
    'name' => 'Turtas',
    'note' => 'Suteikia prieigą prie programos Turto skilties.',
  ),
  'assetsview' =>
  array (
    'name' => 'Peržiūrėti turtą',
  ),
  'assetscreate' =>
  array (
    'name' => 'Sukurti turtą',
  ),
  'assetsedit' =>
  array (
    'name' => 'Redaguoti turtą',
  ),
  'assetsdelete' =>
  array (
    'name' => 'Ištrinti turtą',
  ),
  'assetscheckin' =>
  array (
    'name' => 'Paimti',
    'note' => 'Paimti šiuo metu išduotą turtą ir grąžinti atgal į inventorių.',
  ),
  'assetscheckout' =>
  array (
    'name' => 'Išduoti',
    'note' => 'Priskirti inventoriuje esantį turtą, jį išduodant.',
  ),
  'assetsaudit' =>
  array (
    'name' => 'Audituoti turtą',
    'note' => 'Leidžia naudotojui pažymėti turtą kaip fiziškai inventorizuotą.',
  ),
  'assetsviewrequestable' =>
  array (
    'name' => 'Peržiūrėti užsakomą turtą',
    'note' => 'Leidžia naudotojui peržiūrėti turtą, kuris pažymėtas kaip užsakomas.',
  ),
  'assetsviewencrypted-custom-fields' =>
  array (
    'name' => 'Peržiūrėti šifruotus pritaikytus laukus',
    'note' => 'Leidžia naudotojui peržiūrėti ir redaguoti turto šifruotus pritaikytus laukus.',
  ),
  'accessories' =>
  array (
    'name' => 'Priedai',
    'note' => 'Suteikia prieigą prie programos Priedų skilties.',
  ),
  'accessoriesview' =>
  array (
    'name' => 'Peržiūrėti priedus',
  ),
  'accessoriescreate' =>
  array (
    'name' => 'Sukurti priedus',
  ),
  'accessoriesedit' =>
  array (
    'name' => 'Redaguoti priedus',
  ),
  'accessoriesdelete' =>
  array (
    'name' => 'Ištrinti priedus',
  ),
  'accessoriescheckout' =>
  array (
    'name' => 'Išduoti priedus',
    'note' => 'Priskirti inventoriuje esančius priedus, juos išduodant.',
  ),
  'accessoriescheckin' =>
  array (
    'name' => 'Paimti priedus',
    'note' => 'Paimti šiuo metu išduotus priedus ir grąžinti atgal į inventorių.',
  ),
  'accessoriesfiles' =>
  array (
    'name' => 'Tvarkyti priedų failus',
    'note' => 'Leidžia naudotojui įkelti, atsisiųsti ir ištrinti su priedais susijusius failus.',
  ),
  'consumables' =>
  array (
    'name' => 'Eksploatacinės medžiagos',
    'note' => 'Suteikia prieigą prie programos Eksploatacinių medžiagų skilties.',
  ),
  'consumablesview' =>
  array (
    'name' => 'Peržiūrėti eksploatacines medžiagas',
  ),
  'consumablescreate' =>
  array (
    'name' => 'Sukurti eksploatacines medžiagas',
  ),
  'consumablesedit' =>
  array (
    'name' => 'Redaguoti eksploatacines medžiagas',
  ),
  'consumablesdelete' =>
  array (
    'name' => 'Ištrinti eksploatacines medžiagas',
  ),
  'consumablescheckout' =>
  array (
    'name' => 'Išduoti eksploatacines medžiagas',
    'note' => 'Priskirti inventoriuje esančias eksploatacines medžiagas, jas išduodant.',
  ),
  'consumablesfiles' =>
  array (
    'name' => 'Tvarkyti eksploatacinių medžiagų failus',
    'note' => 'Leidžia naudotojui įkelti, atsisiųsti ir ištrinti su eksploatacinėmis medžiagomis susijusius failus.',
  ),
  'licenses' =>
  array (
    'name' => 'Licencijos',
    'note' => 'Suteikia prieigą prie programos Licencijų skilties.',
  ),
  'licensesview' =>
  array (
    'name' => 'Peržiūrėti licencijas',
  ),
  'licensescreate' =>
  array (
    'name' => 'Sukurti licencijas',
  ),
  'licensesedit' =>
  array (
    'name' => 'Redaguoti licencijas',
  ),
  'licensesdelete' =>
  array (
    'name' => 'Ištrinti licencijas',
  ),
  'licensescheckout' =>
  array (
    'name' => 'Priskirti licencijas',
    'note' => 'Leidžia naudotojui priskirti licencijas turtui arba naudotojams.',
  ),
  'licensescheckin' =>
  array (
    'name' => 'Atšaukti licencijų priskyrimą',
    'note' => 'Leidžia naudotojui atšaukti licencijų priskyrimą turtui arba naudotojams.',
  ),
  'licensesfiles' =>
  array (
    'name' => 'Tvarkyti licencijų failus',
    'note' => 'Leidžia naudotojui įkelti, atsisiųsti ir ištrinti su licencijomis susijusius failus.',
  ),
  'licenseskeys' =>
  array (
    'name' => 'Tvarkyti licencijų raktus',
    'note' => 'Leidžia naudotojui peržiūrėti su licencijomis susietus produkto kodus.',
  ),
  'components' =>
  array (
    'name' => 'Komponentai',
    'note' => 'Suteikia prieigą prie programos Komponentų skilties.',
  ),
  'componentsview' =>
  array (
    'name' => 'Peržiūrėti komponentus',
  ),
  'componentscreate' =>
  array (
    'name' => 'Sukurti komponentus',
  ),
  'componentsedit' =>
  array (
    'name' => 'Redaguoti komponentus',
  ),
  'componentsdelete' =>
  array (
    'name' => 'Ištrinti komponentus',
  ),
  'componentsfiles' =>
  array (
    'name' => 'Tvarkyti komponentų failus',
    'note' => 'Leidžia naudotojui įkelti, atsisiųsti ir ištrinti su komponentais susijusius failus.',
  ),
  'componentscheckout' =>
  array (
    'name' => 'Išduoti komponentus',
    'note' => 'Priskirti inventoriuje esančius komponentus, juos išduodant.',
  ),
  'componentscheckin' =>
  array (
    'name' => 'Paimti komponentus',
    'note' => 'Paimti šiuo metu išduotus komponentus ir grąžinti atgal į inventorių.',
  ),
  'kits' =>
  array (
    'name' => 'Turto rinkiniai',
    'note' => 'Suteikia prieigą prie programos Turto rinkinių skilties.',
  ),
  'kitsview' =>
  array (
    'name' => 'Peržiūrėti iš anksto nustatytus rinkinius',
  ),
  'kitscreate' =>
  array (
    'name' => 'Sukurti iš anksto nustatytus rinkinius',
  ),
  'kitsedit' =>
  array (
    'name' => 'Redaguoti iš anksto nustatytus rinkinius',
  ),
  'kitsdelete' =>
  array (
    'name' => 'Ištrinti iš anksto nustatytus rinkinius',
  ),
  'users' =>
  array (
    'name' => 'Naudotojai',
    'note' => 'Suteikia prieigą prie programos Naudotojų skilties.',
  ),
  'usersview' =>
  array (
    'name' => 'Peržiūrėti naudotojus',
  ),
  'userscreate' =>
  array (
    'name' => 'Sukurti naudotojus',
  ),
  'usersedit' =>
  array (
    'name' => 'Redaguoti naudotojus',
  ),
  'usersdelete' =>
  array (
    'name' => 'Ištrinti naudotojus',
  ),
  'models' =>
  array (
    'name' => 'Modeliai',
    'note' => 'Suteikia prieigą prie programos Modelių skilties.',
  ),
  'modelsview' =>
  array (
    'name' => 'Peržiūrėti modelius',
  ),
  'modelscreate' =>
  array (
    'name' => 'Sukurti modelius',
  ),
  'modelsedit' =>
  array (
    'name' => 'Redaguoti modelius',
  ),
  'modelsdelete' =>
  array (
    'name' => 'Ištrinti modelius',
  ),
  'categories' =>
  array (
    'name' => 'Kategorijos',
    'note' => 'Suteikia prieigą prie programos Kategorijų skilties.',
  ),
  'categoriesview' =>
  array (
    'name' => 'Peržiūrėti kategorijas',
  ),
  'categoriescreate' =>
  array (
    'name' => 'Sukurti kategorijas',
  ),
  'categoriesedit' =>
  array (
    'name' => 'Redaguoti kategorijas',
  ),
  'categoriesdelete' =>
  array (
    'name' => 'Ištrinti kategorijas',
  ),
  'departments' =>
  array (
    'name' => 'Skyriai',
    'note' => 'Suteikia prieigą prie programos Skyrių skilties.',
  ),
  'departmentsview' =>
  array (
    'name' => 'Peržiūrėti skyrius',
  ),
  'departmentscreate' =>
  array (
    'name' => 'Sukurti skyrius',
  ),
  'departmentsedit' =>
  array (
    'name' => 'Atnaujinti skyrius',
  ),
  'departmentsdelete' =>
  array (
    'name' => 'Ištrinti skyrius',
  ),
  'locations' =>
  array (
    'name' => 'Vietos',
    'note' => 'Suteikia prieigą prie programos Vietų skilties.',
  ),
  'locationsview' =>
  array (
    'name' => 'Peržiūrėti vietas',
  ),
  'locationscreate' =>
  array (
    'name' => 'Sukurti vietas',
  ),
  'locationsedit' =>
  array (
    'name' => 'Redaguoti vietas',
  ),
  'locationsdelete' =>
  array (
    'name' => 'Ištrinti vietas',
  ),
  'status-labels' =>
  array (
    'name' => 'Būsenos žymos',
    'note' => 'Suteikia prieigą prie turto naudojamų Būsenos žymų skilties programoje.',
  ),
  'statuslabelsview' =>
  array (
    'name' => 'Peržiūrėti būsenos žymas',
  ),
  'statuslabelscreate' =>
  array (
    'name' => 'Sukurti būsenos žymas',
  ),
  'statuslabelsedit' =>
  array (
    'name' => 'Redaguoti būsenos žymas',
  ),
  'statuslabelsdelete' =>
  array (
    'name' => 'Ištrinti būsenos žymas',
  ),
  'custom-fields' =>
  array (
    'name' => 'Pritaikyti laukai',
    'note' => 'Suteikia prieigą prie programos Pritaikytų laukų skilties.',
  ),
  'customfieldsview' =>
  array (
    'name' => 'Peržiūrėti pritaikytus laukus',
  ),
  'customfieldscreate' =>
  array (
    'name' => 'Sukurti pritaikytus laukus',
  ),
  'customfieldsedit' =>
  array (
    'name' => 'Redaguoti pritaikytus laukus',
  ),
  'customfieldsdelete' =>
  array (
    'name' => 'Ištrinti pritaikytus laukus',
  ),
  'suppliers' =>
  array (
    'name' => 'Tiekėjai',
    'note' => 'Suteikia prieigą prie programos Tiekėjų skilties.',
  ),
  'suppliersview' =>
  array (
    'name' => 'Peržiūrėti tiekėjus',
  ),
  'supplierscreate' =>
  array (
    'name' => 'Sukurti tiekėją',
  ),
  'suppliersedit' =>
  array (
    'name' => 'Redaguoti tiekėjus',
  ),
  'suppliersdelete' =>
  array (
    'name' => 'Ištrinti tiekėjus',
  ),
  'customers' =>
  array (
    'name' => 'Customers',
    'note' => 'Grants access to the Customers section of the application.',
  ),
  'customersview' =>
  array (
    'name' => 'View Customers',
  ),
  'customerscreate' =>
  array (
    'name' => 'Create New Customers',
  ),
  'customersedit' =>
  array (
    'name' => 'Edit Customers',
  ),
  'customersdelete' =>
  array (
    'name' => 'Delete Customers',
  ),
  'contracts' =>
  array (
    'name' => 'Contracts',
    'note' => 'Grants access to customer contracts and subscriptions.',
  ),
  'contractsview' =>
  array (
    'name' => 'View Contracts',
  ),
  'contractscreate' =>
  array (
    'name' => 'Create New Contracts',
  ),
  'contractsedit' =>
  array (
    'name' => 'Edit Contracts',
  ),
  'contractsdelete' =>
  array (
    'name' => 'Delete Contracts',
  ),
  'manufacturers' =>
  array (
    'name' => 'Gamintojai',
    'note' => 'Suteikia prieigą prie programos Gamintojų skilties.',
  ),
  'manufacturersview' =>
  array (
    'name' => 'Peržiūrėti gamintojus',
  ),
  'manufacturerscreate' =>
  array (
    'name' => 'Sukurti gamintojus',
  ),
  'manufacturersedit' =>
  array (
    'name' => 'Redaguoti gamintojus',
  ),
  'manufacturersdelete' =>
  array (
    'name' => 'Ištrinti gamintojus',
  ),
  'companies' =>
  array (
    'name' => 'Įmonės',
    'note' => 'Suteikia prieigą prie programos Įmonių skilties.',
  ),
  'companiesview' =>
  array (
    'name' => 'Peržiūrėti įmones',
  ),
  'companiescreate' =>
  array (
    'name' => 'Sukurti įmones',
  ),
  'companiesedit' =>
  array (
    'name' => 'Redaguoti įmones',
  ),
  'companiesdelete' =>
  array (
    'name' => 'Ištrinti įmones',
  ),
  'user-self-accounts' =>
  array (
    'name' => 'Naudotojų asmeninės paskyros',
    'note' => 'Administratoriaus teisių neturintiems naudotojams suteikia galimybę tvarkyti tam tikrus jų naudotojo paskyros aspektus.',
  ),
  'selftwo-factor' =>
  array (
    'name' => 'Tvarkyti dviejų veiksnių autentifikaciją',
    'note' => 'Leidžia naudotojams įjungti, išjungti ir valdyti dviejų veiksnių autentifikavimą savo paskyroms.',
  ),
  'selfapi' =>
  array (
    'name' => 'Tvarkyti API prieigos raktus',
    'note' => 'Leidžia naudotojams kurti, peržiūrėti ir atšaukti savo API prieigos raktus. Naudotojo prieigos raktai turės tokias pačias teises kaip ir juos sukūręs naudotojas.',
  ),
  'selfedit-location' =>
  array (
    'name' => 'Redaguoti vietą',
    'note' => 'Leidžia naudotojams redaguoti su jų naudotojo paskyra susietą vietą.',
  ),
  'selfcheckout-assets' =>
  array (
    'name' => 'Savarankiškai prisiskirti turtą',
    'note' => 'Leidžia naudotojams patiems prisiskirti turtą be administratoriaus įsikišimo.',
  ),
  'selfview-purchase-cost' =>
  array (
    'name' => 'Peržiūrėti įsigijimo kainą',
    'note' => 'Leidžia naudotojams peržiūrėti įsigijimo kainą jų paskyros rodinyje.',
  ),
  'depreciations' =>
  array (
    'name' => 'Nusidėvėjimo valdymas',
    'note' => 'Leidžia naudotojams valdyti ir peržiūrėti turto nusidėvėjimo informaciją.',
  ),
  'depreciationsview' =>
  array (
    'name' => 'Peržiūrėti nusidėvėjimo informaciją',
  ),
  'depreciationsedit' =>
  array (
    'name' => 'Redaguoti nusidėvėjimo nustatymus',
  ),
  'depreciationsdelete' =>
  array (
    'name' => 'Ištrinti nusidėvėjimo įrašus',
  ),
  'depreciationscreate' =>
  array (
    'name' => 'Sukurti nusidėvėjimo įrašus',
  ),
  'grant_all' => 'Suteikti visas teises į :area',
  'deny_all' => 'Nesuteikti visų teisių į :area',
  'inherit_all' => 'Paveldėti visas teises į :area iš teisių grupių',
  'grant' => 'Suteikti teisę į :area',
  'deny' => 'Nesuteikti teisės „:area“',
  'inherit' => 'Paveldėti teisę į :area iš teisių grupių',
  'use_groups' => 'Siekiant lengvesnio valdymo, primygtinai rekomenduojame naudoti leidimų grupes, o ne priskirti individualius leidimus.',
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
  'customersfiles' =>
  array (
    'name' => 'Manage Customer Files',
    'note' => 'Allows the user to upload, download, and delete files associated with customers. (This only makes sense with view privileges or higher.)',
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

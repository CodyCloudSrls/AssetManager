<?php

return array (
  'superuser' =>
  array (
    'name' => 'Super użytkownik',
    'note' => 'Determines whether the user has full access to all aspects of the admin. This setting overrides ALL more specific and restrictive permissions throughout the system. ',
  ),
  'admin' =>
  array (
    'name' => 'Dostęp administratora',
    'note' => 'Determines whether the user has access to most aspects of the system EXCEPT the System Admin Settings. These users will be able to manage users, locations, categories, etc, but ARE constrained by Full Multiple Company Support if it is enabled.',
  ),
  'import' =>
  array (
    'name' => 'Importuj CSV',
    'note' => 'Pozwoli to użytkownikom importować, nawet jeśli dostęp do użytkowników, zasobów itd. został odebrany w innym miejscu.',
  ),
  'reports' =>
  array (
    'name' => 'Reports Access',
    'note' => 'Określa czy użytkownik ma dostęp do sekcji "Raporty".',
  ),
  'assets' =>
  array (
    'name' => 'Środki',
    'note' => 'Udziela dostęp do sekcji "Zasoby".',
  ),
  'assetsview' =>
  array (
    'name' => 'Pokaż zasoby',
  ),
  'assetscreate' =>
  array (
    'name' => 'Stwórz nowe zasoby',
  ),
  'assetsedit' =>
  array (
    'name' => 'Edytuj zasoby',
  ),
  'assetsdelete' =>
  array (
    'name' => 'Usuń zasoby',
  ),
  'assetscheckin' =>
  array (
    'name' => 'Zarejestruj',
    'note' => 'Zwróć obecnie zajęte zasoby, do puli dostępnych.',
  ),
  'assetscheckout' =>
  array (
    'name' => '',
    'note' => 'Assign assets in inventory by checking them out.',
  ),
  'assetsaudit' =>
  array (
    'name' => 'Audyt zasobów',
    'note' => 'Allows the user to mark an asset as physically inventoried.',
  ),
  'assetsviewrequestable' =>
  array (
    'name' => 'View Requestable Assets',
    'note' => 'Allows the user to view assets that are marked as requestable.',
  ),
  'assetsviewencrypted-custom-fields' =>
  array (
    'name' => 'View Encrypted Custom Fields',
    'note' => 'Allows the user to view and modify encrypted custom fields on assets.',
  ),
  'accessories' =>
  array (
    'name' => 'Akcesoria',
    'note' => 'Grants access to the Accessories section of the application.',
  ),
  'accessoriesview' =>
  array (
    'name' => 'View Accessories',
  ),
  'accessoriescreate' =>
  array (
    'name' => 'Create New Accessories',
  ),
  'accessoriesedit' =>
  array (
    'name' => 'Edit Accessories',
  ),
  'accessoriesdelete' =>
  array (
    'name' => 'Delete Accessories',
  ),
  'accessoriescheckout' =>
  array (
    'name' => 'Check Out Accessories',
    'note' => 'Assign accessories in inventory by checking them out.',
  ),
  'accessoriescheckin' =>
  array (
    'name' => 'Check In Accessories',
    'note' => 'Check accessories back into inventory that are currently checked out.',
  ),
  'accessoriesfiles' =>
  array (
    'name' => 'Manage Accessory Files',
    'note' => 'Allows the user to upload, download, and delete files associated with accessories.',
  ),
  'consumables' =>
  array (
    'name' => 'Materiały eksploatacyjne',
    'note' => 'Grants access to the Consumables section of the application.',
  ),
  'consumablesview' =>
  array (
    'name' => 'View Consumables',
  ),
  'consumablescreate' =>
  array (
    'name' => 'Create New Consumables',
  ),
  'consumablesedit' =>
  array (
    'name' => 'Edit Consumables',
  ),
  'consumablesdelete' =>
  array (
    'name' => 'Delete Consumables',
  ),
  'consumablescheckout' =>
  array (
    'name' => 'Check Out Consumables',
    'note' => 'Assign consumables in inventory by checking them out.',
  ),
  'consumablesfiles' =>
  array (
    'name' => 'Manage Consumable Files',
    'note' => 'Allows the user to upload, download, and delete files associated with consumables.',
  ),
  'licenses' =>
  array (
    'name' => 'Licencje',
    'note' => 'Udziela dostępu do części licencyjnej aplikacji.',
  ),
  'licensesview' =>
  array (
    'name' => 'Pokaż licencje',
  ),
  'licensescreate' =>
  array (
    'name' => 'Stwórz nowe licencje',
  ),
  'licensesedit' =>
  array (
    'name' => 'Edytuj licencje',
  ),
  'licensesdelete' =>
  array (
    'name' => 'Usuń licencje',
  ),
  'licensescheckout' =>
  array (
    'name' => 'Przypisz licencje',
    'note' => 'Pozwala użytkownikowi przypisać licencję do zasobu bądź użytkownika.',
  ),
  'licensescheckin' =>
  array (
    'name' => 'Odepnij licencje',
    'note' => 'Allows the user to unassign licenses from assets or users.',
  ),
  'licensesfiles' =>
  array (
    'name' => 'Manage License Files',
    'note' => 'Allows the user to upload, download, and delete files associated with licenses.',
  ),
  'licenseskeys' =>
  array (
    'name' => 'Manage License Keys',
    'note' => 'Allows the user to view product keys associated with licenses.',
  ),
  'components' =>
  array (
    'name' => 'Składniki',
    'note' => 'Grants access to the Components section of the application.',
  ),
  'componentsview' =>
  array (
    'name' => 'View Components',
  ),
  'componentscreate' =>
  array (
    'name' => 'Create New Components',
  ),
  'componentsedit' =>
  array (
    'name' => 'Edit Components',
  ),
  'componentsdelete' =>
  array (
    'name' => 'Delete Components',
  ),
  'componentsfiles' =>
  array (
    'name' => 'Manage Component Files',
    'note' => 'Allows the user to upload, download, and delete files associated with components.',
  ),
  'componentscheckout' =>
  array (
    'name' => 'Check Out Components',
    'note' => 'Assign components in inventory by checking them out.',
  ),
  'componentscheckin' =>
  array (
    'name' => 'Check In Components',
    'note' => 'Check components back into inventory that are currently checked out.',
  ),
  'kits' =>
  array (
    'name' => 'Predefiniowane zestawy',
    'note' => 'Grants access to the Predefined Kits section of the application.',
  ),
  'kitsview' =>
  array (
    'name' => 'View Predefined Kits',
  ),
  'kitscreate' =>
  array (
    'name' => 'Create New Predefined Kits',
  ),
  'kitsedit' =>
  array (
    'name' => 'Edit Predefined Kits',
  ),
  'kitsdelete' =>
  array (
    'name' => 'Delete Predefined Kits',
  ),
  'users' =>
  array (
    'name' => 'Użytkownicy',
    'note' => 'Grants access to the Users section of the application.',
  ),
  'usersview' =>
  array (
    'name' => 'Przeglądaj użytkowników',
  ),
  'userscreate' =>
  array (
    'name' => 'Create New Users',
  ),
  'usersedit' =>
  array (
    'name' => 'Edit Users',
  ),
  'usersdelete' =>
  array (
    'name' => 'Delete Users',
  ),
  'models' =>
  array (
    'name' => 'Models',
    'note' => 'Grants access to the Models section of the application.',
  ),
  'modelsview' =>
  array (
    'name' => 'Pokaż Modele',
  ),
  'modelscreate' =>
  array (
    'name' => 'Create New Models',
  ),
  'modelsedit' =>
  array (
    'name' => 'Edit Models',
  ),
  'modelsdelete' =>
  array (
    'name' => 'Delete Models',
  ),
  'categories' =>
  array (
    'name' => 'Kategorie',
    'note' => 'Grants access to the Categories section of the application.',
  ),
  'categoriesview' =>
  array (
    'name' => 'View Categories',
  ),
  'categoriescreate' =>
  array (
    'name' => 'Create New Categories',
  ),
  'categoriesedit' =>
  array (
    'name' => 'Edit Categories',
  ),
  'categoriesdelete' =>
  array (
    'name' => 'Delete Categories',
  ),
  'departments' =>
  array (
    'name' => 'Działy',
    'note' => 'Grants access to the Departments section of the application.',
  ),
  'departmentsview' =>
  array (
    'name' => 'View Departments',
  ),
  'departmentscreate' =>
  array (
    'name' => 'Create New Departments',
  ),
  'departmentsedit' =>
  array (
    'name' => 'Edit Departments',
  ),
  'departmentsdelete' =>
  array (
    'name' => 'Delete Departments',
  ),
  'locations' =>
  array (
    'name' => 'Lokalizacje',
    'note' => 'Grants access to the Locations section of the application.',
  ),
  'locationsview' =>
  array (
    'name' => 'View Locations',
  ),
  'locationscreate' =>
  array (
    'name' => 'Create New Locations',
  ),
  'locationsedit' =>
  array (
    'name' => 'Edit Locations',
  ),
  'locationsdelete' =>
  array (
    'name' => 'Delete Locations',
  ),
  'status-labels' =>
  array (
    'name' => 'Status',
    'note' => 'Grants access to the Status Labels section of the application used by Assets.',
  ),
  'statuslabelsview' =>
  array (
    'name' => 'View Status Labels',
  ),
  'statuslabelscreate' =>
  array (
    'name' => 'Create New Status Labels',
  ),
  'statuslabelsedit' =>
  array (
    'name' => 'Edit Status Labels',
  ),
  'statuslabelsdelete' =>
  array (
    'name' => 'Delete Status Labels',
  ),
  'custom-fields' =>
  array (
    'name' => 'Pola niestandardowe',
    'note' => 'Grants access to the Custom Fields section of the application used by Assets.',
  ),
  'customfieldsview' =>
  array (
    'name' => 'View Custom Fields',
  ),
  'customfieldscreate' =>
  array (
    'name' => 'Create New Custom Fields',
  ),
  'customfieldsedit' =>
  array (
    'name' => 'Edit Custom Fields',
  ),
  'customfieldsdelete' =>
  array (
    'name' => 'Delete Custom Fields',
  ),
  'suppliers' =>
  array (
    'name' => 'Dostawcy',
    'note' => 'Grants access to the Suppliers section of the application.',
  ),
  'suppliersview' =>
  array (
    'name' => 'View Suppliers',
  ),
  'supplierscreate' =>
  array (
    'name' => 'Create New Suppliers',
  ),
  'suppliersedit' =>
  array (
    'name' => 'Edit Suppliers',
  ),
  'suppliersdelete' =>
  array (
    'name' => 'Delete Suppliers',
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
    'name' => 'Producenci',
    'note' => 'Grants access to the Manufacturers section of the application.',
  ),
  'manufacturersview' =>
  array (
    'name' => 'View Manufacturers',
  ),
  'manufacturerscreate' =>
  array (
    'name' => 'Create New Manufacturers',
  ),
  'manufacturersedit' =>
  array (
    'name' => 'Edit Manufacturers',
  ),
  'manufacturersdelete' =>
  array (
    'name' => 'Delete Manufacturers',
  ),
  'companies' =>
  array (
    'name' => 'Firmy',
    'note' => 'Grants access to the Companies section of the application.',
  ),
  'companiesview' =>
  array (
    'name' => 'View Companies',
  ),
  'companiescreate' =>
  array (
    'name' => 'Create New Companies',
  ),
  'companiesedit' =>
  array (
    'name' => 'Edit Companies',
  ),
  'companiesdelete' =>
  array (
    'name' => 'Delete Companies',
  ),
  'user-self-accounts' =>
  array (
    'name' => 'User Self Accounts',
    'note' => 'Grants non-admin users the ability to manage certain aspects of their own user accounts.',
  ),
  'selftwo-factor' =>
  array (
    'name' => 'Manage Two-Factor Authentication',
    'note' => 'Allows users to enable, disable, and manage two-factor authentication for their own accounts.',
  ),
  'selfapi' =>
  array (
    'name' => 'Manage API Tokens',
    'note' => 'Allows users to create, view, and revoke their own API tokens. User tokens will have the same permissions as the user who created them.',
  ),
  'selfedit-location' =>
  array (
    'name' => 'Edit Location',
    'note' => 'Allows users to edit the location associated with their own user account.',
  ),
  'selfcheckout-assets' =>
  array (
    'name' => 'Self Check Out Assets',
    'note' => 'Allows users to check out assets to themselves without admin intervention.',
  ),
  'selfview-purchase-cost' =>
  array (
    'name' => 'View Purchase Cost',
    'note' => 'Allows users to view the purchase cost of items in their account view.',
  ),
  'depreciations' =>
  array (
    'name' => 'Depreciation Management',
    'note' => 'Allows users to manage and view asset depreciation details.',
  ),
  'depreciationsview' =>
  array (
    'name' => 'View Depreciation Details',
  ),
  'depreciationsedit' =>
  array (
    'name' => 'Edit Depreciation Settings',
  ),
  'depreciationsdelete' =>
  array (
    'name' => 'Delete Depreciation Records',
  ),
  'depreciationscreate' =>
  array (
    'name' => 'Create Depreciation Records',
  ),
  'grant_all' => 'Grant all permissions for :area',
  'deny_all' => 'Deny all permissions for :area',
  'inherit_all' => 'Inherit all permissions for :area from permission groups',
  'grant' => 'Grant Permission for :area',
  'deny' => 'Deny Permission for :area',
  'inherit' => 'Inherit Permission for :area from permission groups',
  'use_groups' => 'We strongly suggest using Permission Groups instead of assigning individual permissions for easier management.',
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

<?php

return array (
  'superuser' =>
  array (
    'name' => 'Super user',
    'note' => 'Meghatározza, hogy a felhasználó teljes hozzáféréssel rendelkezik-e az adminisztráció minden területéhez. Ez a beállítás felülír minden egyéb, a rendszerben megadott specifikusabb és korlátozóbb jogosultságot ',
  ),
  'admin' =>
  array (
    'name' => 'Adminisztrátori hozzáférés',
    'note' => 'Meghatározza, hogy a felhasználó hozzáfér-e a rendszer legtöbb területéhez, KIVÉVE a Rendszeradminisztrátori beállításokat. Ezek a felhasználók kezelhetik a felhasználókat, helyszíneket, kategóriákat stb., de a Teljes többvállalatos támogatás beállításai korlátozzák őket, amennyiben az engedélyezve van.',
  ),
  'import' =>
  array (
    'name' => 'CSV betöltés',
    'note' => 'Ez lehetővé teszi a felhasználók számára az importálást akkor is, ha máshol a rendszerben nincs hozzáférésük a felhasználókhoz, eszközökhöz stb.',
  ),
  'reports' =>
  array (
    'name' => 'Hozzáférés a jelentésekhez',
    'note' => 'Meghatározza, hogy a felhasználó hozzáfér-e az alkalmazás Jelentések menüpontjához.',
  ),
  'assets' =>
  array (
    'name' => 'Eszközök',
    'note' => 'Hozzáférést biztosít az alkalmazás Eszközök menüpontjához.',
  ),
  'assetsview' =>
  array (
    'name' => 'Eszközök megtekintése',
  ),
  'assetscreate' =>
  array (
    'name' => 'Új eszközök létrehozása',
  ),
  'assetsedit' =>
  array (
    'name' => 'Eszközök szerkesztése',
  ),
  'assetsdelete' =>
  array (
    'name' => 'Eszközök törlése',
  ),
  'assetscheckin' =>
  array (
    'name' => 'Visszavételezés',
    'note' => 'A jelenleg kiadott eszközök visszavételezése a készletbe.',
  ),
  'assetscheckout' =>
  array (
    'name' => 'Kiadás',
    'note' => 'Eszközök hozzárendelése a készletből kiadással.',
  ),
  'assetsaudit' =>
  array (
    'name' => 'Eszközök auditálása',
    'note' => 'Lehetővé teszi a felhasználó számára, hogy egy eszközt fizikailag leltározottként jelöljön meg.',
  ),
  'assetsviewrequestable' =>
  array (
    'name' => 'Igényelhető eszközök megtekintése',
    'note' => 'Allows the user to view assets that are marked as requestable.',
  ),
  'assetsviewencrypted-custom-fields' =>
  array (
    'name' => 'View Encrypted Custom Fields',
    'note' => 'Allows the user to view and modify encrypted custom fields on assets.',
  ),
  'accessories' =>
  array (
    'name' => 'Tartozékok',
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
    'name' => 'Fogyóeszközök',
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
    'name' => 'Licencek',
    'note' => 'Grants access to the Licenses section of the application.',
  ),
  'licensesview' =>
  array (
    'name' => 'View Licenses',
  ),
  'licensescreate' =>
  array (
    'name' => 'Create New Licenses',
  ),
  'licensesedit' =>
  array (
    'name' => 'Edit Licenses',
  ),
  'licensesdelete' =>
  array (
    'name' => 'Delete Licenses',
  ),
  'licensescheckout' =>
  array (
    'name' => 'Assign Licenses',
    'note' => 'Allows the user to assign licenses to assets or users.',
  ),
  'licensescheckin' =>
  array (
    'name' => 'Unassign Licenses',
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
    'name' => 'Alkatrészek',
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
    'name' => 'Előre definiált csomagok',
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
    'name' => 'Felhasználók',
    'note' => 'Grants access to the Users section of the application.',
  ),
  'usersview' =>
  array (
    'name' => 'Felhasználók megtekintése',
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
    'name' => 'Modellek megtekintése',
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
    'name' => 'Kategóriák',
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
    'name' => 'Osztályok',
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
    'name' => 'Helyek',
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
    'name' => 'Státusz címkék',
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
    'name' => 'Egyéni mezők',
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
    'name' => 'Beszállítók',
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
  'manufacturers' =>
  array (
    'name' => 'Gyártók',
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
    'name' => 'Cégek',
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

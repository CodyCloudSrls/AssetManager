<?php

return array (
  'superuser' =>
  array (
    'name' => 'Super User',
    'note' => 'Determines whether the user has full access to all aspects of the admin. This setting overrides ALL more specific and restrictive permissions throughout the system. ',
  ),
  'admin' =>
  array (
    'name' => 'Admin Access',
    'note' => 'Determines whether the user has access to most aspects of the system EXCEPT the System Admin Settings. These users will be able to manage users, locations, categories, etc, but ARE constrained by Full Multiple Company Support if it is enabled.',
  ),
  'import' =>
  array (
    'name' => 'CSV Import',
    'note' => 'This will allow users to import even if access to users, assets, etc is denied elsewhere.',
  ),
  'reports' =>
  array (
    'name' => 'Reports Access',
    'note' => 'Determines whether the user has access to the Reports section of the application.',
  ),
  'assets' =>
  array (
    'name' => 'Tillgångar',
    'note' => 'Grants access to the Assets section of the application.',
  ),
  'assetsview' =>
  array (
    'name' => 'View Assets',
  ),
  'assetscreate' =>
  array (
    'name' => 'Create New Assets',
  ),
  'assetsedit' =>
  array (
    'name' => 'Edit Assets',
  ),
  'assetsdelete' =>
  array (
    'name' => 'Delete Assets',
  ),
  'assetscheckin' =>
  array (
    'name' => 'Check In',
    'note' => 'Check assets back into inventory that are currently checked out.',
  ),
  'assetscheckout' =>
  array (
    'name' => 'Check Out',
    'note' => 'Assign assets in inventory by checking them out.',
  ),
  'assetsaudit' =>
  array (
    'name' => 'Audit Assets',
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
    'name' => 'Tillbehör',
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
    'name' => 'Förbrukningsmaterial',
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
    'name' => 'Licenser',
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
    'name' => 'Komponenter',
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
    'name' => 'Fördefinierade paket',
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
    'name' => 'Användare',
    'note' => 'Grants access to the Users section of the application.',
  ),
  'usersview' =>
  array (
    'name' => 'Visa användare',
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
    'name' => 'Visa modeller',
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
    'name' => 'Kategorier',
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
    'name' => 'Avdelningar',
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
    'name' => 'Platser',
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
    'name' => 'Statusetiketter',
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
    'name' => 'Anpassade fält',
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
    'name' => 'Leverantörer',
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
    'name' => 'Tillverkare',
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
    'name' => 'Företag',
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

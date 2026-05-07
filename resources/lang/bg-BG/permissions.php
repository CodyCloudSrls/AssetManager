<?php

return array (
  'superuser' =>
  array (
    'name' => 'Супер Администратор',
    'note' => 'Определя дали потребителят има пълен достъп до всички аспекти на администраторския панел. Тази настройка отменя ВСИЧКИ по-специфични и ограничителни разрешения в системата. ',
  ),
  'admin' =>
  array (
    'name' => 'Администраторски достъп',
    'note' => 'Определя дали потребителят има достъп до повечето аспекти на системата, ОСВЕН настройките на системния администратор. Тези потребители ще могат да управляват потребители, местоположения, категории и др., но СА ограничени от пълната поддръжка за множество компании, ако е активирана.',
  ),
  'import' =>
  array (
    'name' => 'CVS Импорт',
    'note' => 'Това ще позволи импортиране на потребители, дори и ако достъпа до списък с потребители или активи и др. е забранен на друго място.',
  ),
  'reports' =>
  array (
    'name' => 'Достъп Спавки',
    'note' => 'Определя дали потребителя има достъп до справките в програмата.',
  ),
  'assets' =>
  array (
    'name' => 'Активи',
    'note' => 'Дава достъп до раздел активи в програмата.',
  ),
  'assetsview' =>
  array (
    'name' => 'Преглед на активи',
  ),
  'assetscreate' =>
  array (
    'name' => 'Създаване на нови активи',
  ),
  'assetsedit' =>
  array (
    'name' => 'Редакция на активи',
  ),
  'assetsdelete' =>
  array (
    'name' => 'Изтриване на активи',
  ),
  'assetscheckin' =>
  array (
    'name' => 'Вписване',
    'note' => 'Дава достъп за вписване на активи обраното в системата.',
  ),
  'assetscheckout' =>
  array (
    'name' => 'Изписване',
    'note' => 'Дава достъп за изписване на активи към потребители.',
  ),
  'assetsaudit' =>
  array (
    'name' => 'Инвентаризация на активи',
    'note' => 'Дава достъп на потребителя да прави инвентаризация.',
  ),
  'assetsviewrequestable' =>
  array (
    'name' => 'Вижда активите за поискване',
    'note' => 'Дава достъп на потребителя да вижда активите, които са разрешени за поискване.',
  ),
  'assetsviewencrypted-custom-fields' =>
  array (
    'name' => 'Вижда криптирани полета',
    'note' => 'Дава достъп на потребителя да вижда и да модифицира криптираните полета на активите.',
  ),
  'accessories' =>
  array (
    'name' => 'Аксесоари',
    'note' => 'Дава достъп до раздел аксесоари в програмата.',
  ),
  'accessoriesview' =>
  array (
    'name' => 'Вижда аксесоарите',
  ),
  'accessoriescreate' =>
  array (
    'name' => 'Създава нови аксесоари',
  ),
  'accessoriesedit' =>
  array (
    'name' => 'Редактира аксесоарите',
  ),
  'accessoriesdelete' =>
  array (
    'name' => 'Изтрива аксесоарите',
  ),
  'accessoriescheckout' =>
  array (
    'name' => 'Изписва аксесоарите',
    'note' => 'Дава достъп за изписване на аскесоарите към потребители.',
  ),
  'accessoriescheckin' =>
  array (
    'name' => 'Вписва аксесоари',
    'note' => 'Дава достъп за вписване на аксесоари обратно в системата.',
  ),
  'accessoriesfiles' =>
  array (
    'name' => 'Управление на файловете на аксесоарите',
    'note' => 'Дава достъп за качване, сваляне и изтриване на файлове към аксесоарите.',
  ),
  'consumables' =>
  array (
    'name' => 'Консумативи',
    'note' => 'Дава достъп до раздел консумативи в програмата.',
  ),
  'consumablesview' =>
  array (
    'name' => 'Вижда консумативи',
  ),
  'consumablescreate' =>
  array (
    'name' => 'Създава консумативи',
  ),
  'consumablesedit' =>
  array (
    'name' => 'Редактира консумативи',
  ),
  'consumablesdelete' =>
  array (
    'name' => 'Изтрива консумативи',
  ),
  'consumablescheckout' =>
  array (
    'name' => 'Изписва консумативи',
    'note' => 'Дава достъп за изписване на консумативи към потребители.',
  ),
  'consumablesfiles' =>
  array (
    'name' => 'Управление на файловете на консумативите',
    'note' => 'Дава достъп за качване, сваляне и изтриване на файловете към консумативите.',
  ),
  'licenses' =>
  array (
    'name' => 'Лицензи',
    'note' => 'Дава достъп до раздел лицензи в програмата.',
  ),
  'licensesview' =>
  array (
    'name' => 'Вижда лицензи',
  ),
  'licensescreate' =>
  array (
    'name' => 'Създава лицензи',
  ),
  'licensesedit' =>
  array (
    'name' => 'Редактира лицензи',
  ),
  'licensesdelete' =>
  array (
    'name' => 'Изтрива лицензи',
  ),
  'licensescheckout' =>
  array (
    'name' => 'Изписва лицензи',
    'note' => 'Дава достъп за изписване на лицензи към потребители.',
  ),
  'licensescheckin' =>
  array (
    'name' => 'Вписва лицензи',
    'note' => 'Дава достъп за вписване на лицензи обратно в системата.',
  ),
  'licensesfiles' =>
  array (
    'name' => 'Управление на файловете на лицензите',
    'note' => 'Дава достъп за качване, сваляне и изтриване на файлове към лицензите.',
  ),
  'licenseskeys' =>
  array (
    'name' => 'Управление на лицензионни ключове',
    'note' => 'Дава достъп на потребителя да вижда лицензионни ключове към лицензите.',
  ),
  'components' =>
  array (
    'name' => 'Компоненти',
    'note' => 'Дава достъп до раздел компоненти в програмата.',
  ),
  'componentsview' =>
  array (
    'name' => 'Вижда компоненти',
  ),
  'componentscreate' =>
  array (
    'name' => 'Създава компоненти',
  ),
  'componentsedit' =>
  array (
    'name' => 'Редактира компоненти',
  ),
  'componentsdelete' =>
  array (
    'name' => 'Изтрива компоненти',
  ),
  'componentsfiles' =>
  array (
    'name' => 'Управлява файловете за компоненти',
    'note' => 'Дава достъп за качване, сваляне и изтриване на файлове към компонентите.',
  ),
  'componentscheckout' =>
  array (
    'name' => 'Изписване на компоненти',
    'note' => 'Дава достъп за изписване на компоненти към потребители.',
  ),
  'componentscheckin' =>
  array (
    'name' => 'Вписване на компоненти',
    'note' => 'Дава достъп за вписване на компоненти обратно в системата.',
  ),
  'kits' =>
  array (
    'name' => 'Комплекти',
    'note' => 'Дава достъп до раздел комплекти в програмата.',
  ),
  'kitsview' =>
  array (
    'name' => 'Вижда комплекти',
  ),
  'kitscreate' =>
  array (
    'name' => 'Създава комплекти',
  ),
  'kitsedit' =>
  array (
    'name' => 'Редактира комплекти',
  ),
  'kitsdelete' =>
  array (
    'name' => 'Изтрива комплекти',
  ),
  'users' =>
  array (
    'name' => 'Потребители',
    'note' => 'Дава достъп до раздел потребители в програмата.',
  ),
  'usersview' =>
  array (
    'name' => 'Преглед на потребителите',
  ),
  'userscreate' =>
  array (
    'name' => 'Създава нови потребители',
  ),
  'usersedit' =>
  array (
    'name' => 'Редактира потребители',
  ),
  'usersdelete' =>
  array (
    'name' => 'Изтрива потребители',
  ),
  'models' =>
  array (
    'name' => 'Модели',
    'note' => 'Дава достъп до раздел модели в програмата.',
  ),
  'modelsview' =>
  array (
    'name' => 'Преглед на моделите',
  ),
  'modelscreate' =>
  array (
    'name' => 'Създава нови модели',
  ),
  'modelsedit' =>
  array (
    'name' => 'Редактира модели',
  ),
  'modelsdelete' =>
  array (
    'name' => 'Изтрива модели',
  ),
  'categories' =>
  array (
    'name' => 'Категории',
    'note' => 'Дава достъп до раздел категорий в програмата.',
  ),
  'categoriesview' =>
  array (
    'name' => 'Вижда категорий',
  ),
  'categoriescreate' =>
  array (
    'name' => 'Създава нови категорий',
  ),
  'categoriesedit' =>
  array (
    'name' => 'Редактира категорий',
  ),
  'categoriesdelete' =>
  array (
    'name' => 'Изтрива категорий',
  ),
  'departments' =>
  array (
    'name' => 'Отдели',
    'note' => 'Дава достъп до раздел отдели в програмата.',
  ),
  'departmentsview' =>
  array (
    'name' => 'Вижда отдели',
  ),
  'departmentscreate' =>
  array (
    'name' => 'Създава нови отдели',
  ),
  'departmentsedit' =>
  array (
    'name' => 'Редактира отдели',
  ),
  'departmentsdelete' =>
  array (
    'name' => 'Изтрива отдели',
  ),
  'locations' =>
  array (
    'name' => 'Местоположения',
    'note' => 'Дава достъп до раздел местоположения в програмата.',
  ),
  'locationsview' =>
  array (
    'name' => 'Вижда местоположения',
  ),
  'locationscreate' =>
  array (
    'name' => 'Създава нови местоположения',
  ),
  'locationsedit' =>
  array (
    'name' => 'Редактира местоположения',
  ),
  'locationsdelete' =>
  array (
    'name' => 'Изтрива местоположения',
  ),
  'status-labels' =>
  array (
    'name' => 'Статус Етикети',
    'note' => 'Дава достъп до раздел статус етикети в програмата.',
  ),
  'statuslabelsview' =>
  array (
    'name' => 'Вижда статус етикети',
  ),
  'statuslabelscreate' =>
  array (
    'name' => 'Създава нови статус етикети',
  ),
  'statuslabelsedit' =>
  array (
    'name' => 'Редактира статус етикети',
  ),
  'statuslabelsdelete' =>
  array (
    'name' => 'Изтрива статус етикети',
  ),
  'custom-fields' =>
  array (
    'name' => 'Потребителски полета',
    'note' => 'Дава достъп до раздел потребителски полета в програмата.',
  ),
  'customfieldsview' =>
  array (
    'name' => 'Вижда потребителски полета',
  ),
  'customfieldscreate' =>
  array (
    'name' => 'Създава нови потребителски полета',
  ),
  'customfieldsedit' =>
  array (
    'name' => 'Редактира потребителски полета',
  ),
  'customfieldsdelete' =>
  array (
    'name' => 'Изтрива потребителски полета',
  ),
  'suppliers' =>
  array (
    'name' => 'Доставчици',
    'note' => 'Дава достъп до раздел доставчици в програмата.',
  ),
  'suppliersview' =>
  array (
    'name' => 'Вижда доставчици',
  ),
  'supplierscreate' =>
  array (
    'name' => 'Създава нови доставчици',
  ),
  'suppliersedit' =>
  array (
    'name' => 'Редактира доставчици',
  ),
  'suppliersdelete' =>
  array (
    'name' => 'Изтрива доставчици',
  ),
  'manufacturers' =>
  array (
    'name' => 'Производители',
    'note' => 'Дава достъп до раздел производители в програмата.',
  ),
  'manufacturersview' =>
  array (
    'name' => 'Вижда производители',
  ),
  'manufacturerscreate' =>
  array (
    'name' => 'Създава нови производители',
  ),
  'manufacturersedit' =>
  array (
    'name' => 'Редактира производители',
  ),
  'manufacturersdelete' =>
  array (
    'name' => 'Изтрива производители',
  ),
  'companies' =>
  array (
    'name' => 'Компании',
    'note' => 'Дава достъп до раздел компании в програмата.',
  ),
  'companiesview' =>
  array (
    'name' => 'Вижда компании',
  ),
  'companiescreate' =>
  array (
    'name' => 'Създава нови компании',
  ),
  'companiesedit' =>
  array (
    'name' => 'Редактира компании',
  ),
  'companiesdelete' =>
  array (
    'name' => 'Изтрива компании',
  ),
  'user-self-accounts' =>
  array (
    'name' => 'Собствен потребителски акаунт',
    'note' => 'Дава достъп на потребителя да редактира информация за техния собствен акаунт.',
  ),
  'selftwo-factor' =>
  array (
    'name' => 'Двуфакторно удостоверяване',
    'note' => 'Позволява на потребителите да включват, изключват и управляват двуфакторно удостоверяване на техните акаунти.',
  ),
  'selfapi' =>
  array (
    'name' => 'Управление на API ключове',
    'note' => 'Дава достъп на потребителите да създават, виждат и премахват техни лични API ключове. Ключовете ще имат същите права, като потребите от който са създадени.',
  ),
  'selfedit-location' =>
  array (
    'name' => 'Редактира местоположение',
    'note' => 'Дава достъп на потребителя да редактира местоположението на техния потребителски акаунт.',
  ),
  'selfcheckout-assets' =>
  array (
    'name' => 'Изписване на активи',
    'note' => 'Дава достъп за изписване на активи без намесата на админ.',
  ),
  'selfview-purchase-cost' =>
  array (
    'name' => 'Вижда цена на закупуване',
    'note' => 'Дава достъп на потребителя да вижда цената на която е закупен артикула.',
  ),
  'depreciations' =>
  array (
    'name' => 'Управление Амортизации',
    'note' => 'Дава достъп на потребителя да вижда информация за амортизации на активите.',
  ),
  'depreciationsview' =>
  array (
    'name' => 'Вижда Амортизации',
  ),
  'depreciationsedit' =>
  array (
    'name' => 'Редактира настройки на Амортизации',
  ),
  'depreciationsdelete' =>
  array (
    'name' => 'Изтрива записи на Амортизации',
  ),
  'depreciationscreate' =>
  array (
    'name' => 'Създава записи на Амортизации',
  ),
  'grant_all' => 'Всички права за :area',
  'deny_all' => 'Без права за :area',
  'inherit_all' => 'Наследяване на всички права за :area от група',
  'grant' => 'Права за :area',
  'deny' => 'Без права за :area',
  'inherit' => 'Наследяване на права за :area от група',
  'use_groups' => 'Силно се препоръчва да се използват групи за достъп вместо даване на индивидуални права за по лесно управление.',
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

<?php

namespace App\Presenters;

class ComplianceDomainPresenter extends Presenter
{
    public static function dataTableLayout()
    {
        $layout = [
            [
                'field' => 'id',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.id'),
                'visible' => false,
            ],
            [
                'field' => 'name',
                'searchable' => true,
                'sortable' => true,
                'switchable' => false,
                'title' => trans('admin/compliancedomains/table.name'),
                'visible' => true,
                'formatter' => 'compliancedomainsLinkFormatter',
            ],
            [
                'field' => 'key',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/compliancedomains/table.key'),
                'visible' => true,
            ],
            [
                'field' => 'is_active',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/compliancedomains/table.is_active'),
                'visible' => true,
                'formatter' => 'trueFalseFormatter',
            ],
            [
                'field' => 'is_system',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/compliancedomains/table.is_system'),
                'visible' => false,
                'formatter' => 'trueFalseFormatter',
            ],
            [
                'field' => 'sort_order',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/compliancedomains/table.sort_order'),
                'visible' => true,
            ],
            [
                'field' => 'description',
                'searchable' => true,
                'sortable' => false,
                'switchable' => true,
                'title' => trans('admin/compliancedomains/table.description'),
                'visible' => false,
            ],
            [
                'field' => 'created_by',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('general.created_by'),
                'visible' => false,
                'formatter' => 'usersLinkObjFormatter',
            ],
            [
                'field' => 'updated_at',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.updated_at'),
                'visible' => true,
                'formatter' => 'dateDisplayFormatter',
            ],
            [
                'field' => 'actions',
                'searchable' => false,
                'sortable' => false,
                'switchable' => false,
                'title' => trans('table.actions'),
                'visible' => true,
                'formatter' => 'compliancedomainsActionsFormatter',
                'printIgnore' => true,
                'class' => 'hidden-print',
            ],
        ];

        return json_encode($layout);
    }
}

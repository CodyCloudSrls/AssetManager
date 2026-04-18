<?php

namespace App\Presenters;

class DocumentFrameworkPresenter extends Presenter
{
    public static function dataTableLayout()
    {
        $layout = [
            [
                'field' => 'checkbox',
                'checkbox' => true,
                'titleTooltip' => trans('general.select_all_none'),
                'printIgnore' => true,
                'class' => 'hidden-print',
            ],
            [
                'field' => 'id',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.id'),
                'visible' => false,
            ],
            [
                'field' => 'company',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.company'),
                'visible' => false,
                'formatter' => 'companiesLinkObjFormatter',
            ],
            [
                'field' => 'visibility_label',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.template_visibility.label'),
                'visible' => false,
            ],
            [
                'field' => 'name',
                'searchable' => true,
                'sortable' => true,
                'switchable' => false,
                'title' => trans('admin/documentframeworks/table.name'),
                'visible' => true,
                'formatter' => 'documentframeworksLinkFormatter',
            ],
            [
                'field' => 'slug',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/documentframeworks/table.slug'),
                'visible' => true,
            ],
            [
                'field' => 'is_active',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/documentframeworks/table.is_active'),
                'visible' => true,
                'formatter' => 'trueFalseFormatter',
            ],
            [
                'field' => 'sort_order',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('admin/documentframeworks/table.sort_order'),
                'visible' => true,
            ],
            [
                'field' => 'documents_count',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.documents'),
                'visible' => true,
                'class' => 'css-barcode',
            ],
            [
                'field' => 'description',
                'searchable' => true,
                'sortable' => false,
                'switchable' => true,
                'title' => trans('admin/documentframeworks/table.description'),
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
                'field' => 'created_at',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.created_at'),
                'visible' => false,
                'formatter' => 'dateDisplayFormatter',
            ],
            [
                'field' => 'updated_at',
                'searchable' => true,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.updated_at'),
                'visible' => false,
                'formatter' => 'dateDisplayFormatter',
            ],
            [
                'field' => 'actions',
                'searchable' => false,
                'sortable' => false,
                'switchable' => false,
                'title' => trans('table.actions'),
                'visible' => true,
                'formatter' => 'documentframeworksActionsFormatter',
                'printIgnore' => true,
                'class' => 'hidden-print',
            ],
        ];

        return json_encode($layout);
    }
}

<?php

namespace App\Presenters;

class CustomerPresenter extends Presenter
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
                'field' => 'name',
                'searchable' => true,
                'sortable' => true,
                'switchable' => false,
                'title' => trans('general.name'),
                'visible' => true,
                'formatter' => 'customersLinkFormatter',
            ],
            [
                'field' => 'customer_number',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/customers/table.customer_number'),
                'visible' => true,
            ],
            [
                'field' => 'company',
                'searchable' => false,
                'sortable' => false,
                'switchable' => true,
                'title' => trans('general.company'),
                'visible' => true,
                'formatter' => 'companiesLinkObjFormatter',
            ],
            [
                'field' => 'status_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('general.status'),
                'visible' => true,
            ],
            [
                'field' => 'nis_profile_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/customers/table.nis_profile'),
                'visible' => true,
            ],
            [
                'field' => 'nis_service_role_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/customers/table.nis_service_role'),
                'visible' => true,
            ],
            [
                'field' => 'nis_criticality_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/customers/table.nis_criticality'),
                'visible' => true,
            ],
            [
                'field' => 'contracts_count',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/contracts/general.contracts'),
                'visible' => true,
            ],
            [
                'field' => 'contact',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/customers/table.contact'),
                'visible' => false,
            ],
            [
                'field' => 'email',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/customers/table.email'),
                'visible' => false,
                'formatter' => 'emailFormatter',
            ],
            [
                'field' => 'nis_next_review_at',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/customers/table.nis_next_review_at'),
                'visible' => false,
                'formatter' => 'dateDisplayFormatter',
            ],
            [
                'field' => 'updated_at',
                'searchable' => true,
                'sortable' => true,
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
                'formatter' => 'customersActionsFormatter',
                'printIgnore' => true,
            ],
        ];

        return json_encode($layout);
    }

    public function nameUrl()
    {
        return '<a href="'.route('customers.show', $this->model).'">'.e($this->model->name).'</a>';
    }

    public function fullName()
    {
        return $this->model->name;
    }
}

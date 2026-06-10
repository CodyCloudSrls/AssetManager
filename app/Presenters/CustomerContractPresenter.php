<?php

namespace App\Presenters;

class CustomerContractPresenter extends Presenter
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
                'title' => trans('general.name'),
                'visible' => true,
                'formatter' => 'contractsLinkFormatter',
            ],
            [
                'field' => 'customer',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('general.customer'),
                'visible' => true,
                'formatter' => 'customersLinkObjFormatter',
            ],
            [
                'field' => 'contract_number',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/contracts/general.contract_number'),
                'visible' => true,
            ],
            [
                'field' => 'status_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('general.status'),
                'visible' => true,
            ],
            [
                'field' => 'service_code',
                'searchable' => false,
                'sortable' => false,
                'switchable' => true,
                'title' => trans('admin/contracts/general.service_code'),
                'visible' => false,
            ],
            [
                'field' => 'monthly_revenue',
                'searchable' => false,
                'sortable' => false,
                'title' => trans('admin/contracts/general.monthly_revenue'),
                'visible' => true,
            ],
            [
                'field' => 'monthly_cost',
                'searchable' => false,
                'sortable' => false,
                'title' => trans('admin/contracts/general.monthly_cost'),
                'visible' => true,
            ],
            [
                'field' => 'monthly_net',
                'searchable' => false,
                'sortable' => false,
                'title' => trans('admin/contracts/general.monthly_net'),
                'visible' => true,
            ],
            [
                'field' => 'starts_at',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/contracts/general.starts_at'),
                'visible' => false,
                'formatter' => 'dateDisplayFormatter',
            ],
            [
                'field' => 'ends_at',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/contracts/general.ends_at'),
                'visible' => true,
                'formatter' => 'dateDisplayFormatter',
            ],
            [
                'field' => 'created_at',
                'searchable' => false,
                'sortable' => true,
                'switchable' => true,
                'title' => trans('general.created_at'),
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
                'formatter' => 'contractsActionsFormatter',
                'printIgnore' => true,
            ],
        ];

        return json_encode($layout);
    }

    public function nameUrl()
    {
        return '<a href="'.route('contracts.show', $this->model).'">'.e($this->model->name).'</a>';
    }

    public function fullName()
    {
        return $this->model->name;
    }
}

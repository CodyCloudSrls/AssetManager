<?php

namespace App\Presenters;

class TicketPresenter extends Presenter
{
    public static function dataTableLayout()
    {
        $layout = [
            ['field' => 'id', 'searchable' => false, 'sortable' => true, 'switchable' => false, 'title' => trans('general.id'), 'visible' => false],
            ['field' => 'ticket_number', 'searchable' => true, 'sortable' => true, 'title' => trans('admin/tickets/form.ticket_number'), 'visible' => true, 'formatter' => 'ticketsLinkFormatter'],
            ['field' => 'subject', 'searchable' => true, 'sortable' => true, 'title' => trans('general.subject'), 'visible' => true],
            ['field' => 'status', 'searchable' => true, 'sortable' => true, 'title' => trans('general.status'), 'visible' => true],
            ['field' => 'priority', 'searchable' => true, 'sortable' => true, 'title' => trans('admin/tickets/form.priority'), 'visible' => true],
            ['field' => 'type', 'searchable' => true, 'sortable' => true, 'title' => trans('admin/tickets/form.type'), 'visible' => true],
            ['field' => 'requester', 'searchable' => true, 'sortable' => true, 'title' => trans('admin/tickets/form.requester'), 'visible' => true, 'formatter' => 'ticketPersonFormatter'],
            ['field' => 'assignee', 'searchable' => true, 'sortable' => true, 'title' => trans('admin/tickets/form.assignee'), 'visible' => true, 'formatter' => 'ticketPersonFormatter'],
            ['field' => 'company', 'searchable' => true, 'sortable' => true, 'title' => trans('general.company'), 'visible' => false, 'formatter' => 'companiesLinkObjFormatter'],
            ['field' => 'source', 'searchable' => true, 'sortable' => true, 'title' => trans('admin/tickets/form.source'), 'visible' => true],
            ['field' => 'created_at', 'searchable' => true, 'sortable' => true, 'title' => trans('general.created_at'), 'visible' => false, 'formatter' => 'dateDisplayFormatter'],
            ['field' => 'updated_at', 'searchable' => true, 'sortable' => true, 'title' => trans('general.updated_at'), 'visible' => true, 'formatter' => 'dateDisplayFormatter'],
            ['field' => 'actions', 'searchable' => false, 'sortable' => false, 'switchable' => false, 'title' => trans('table.actions'), 'visible' => true, 'formatter' => 'ticketsActionsFormatter', 'printIgnore' => true],
        ];

        return json_encode($layout);
    }

    public function nameUrl()
    {
        return '<a href="'.route('tickets.show', $this->model).'">'.e($this->model->ticket_number).'</a>';
    }

    public function fullName()
    {
        return $this->model->display_name;
    }
}

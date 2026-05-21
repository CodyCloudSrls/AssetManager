<?php

namespace App\Presenters;

class DocumentPresenter extends Presenter
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
                'formatter' => 'documentsLinkFormatter',
            ],
            [
                'field' => 'document_number',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.document_number'),
                'visible' => true,
            ],
            [
                'field' => 'document_type',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.document_type'),
                'visible' => true,
            ],
            [
                'field' => 'framework',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.framework'),
                'visible' => true,
            ],
            [
                'field' => 'requirements',
                'searchable' => false,
                'sortable' => false,
                'switchable' => true,
                'title' => trans('admin/documentframeworks/general.requirements_tab'),
                'visible' => true,
                'formatter' => 'documentRequirementsFormatter',
            ],
            [
                'field' => 'files_count',
                'searchable' => false,
                'sortable' => false,
                'switchable' => true,
                'title' => trans('general.files'),
                'visible' => true,
                'formatter' => 'documentFilesCountFormatter',
            ],
            [
                'field' => 'status',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('general.status'),
                'visible' => true,
            ],
            [
                'field' => 'version',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.version'),
                'visible' => true,
            ],
            [
                'field' => 'owner',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.owner'),
                'visible' => true,
                'formatter' => 'usersLinkObjFormatter',
            ],
            [
                'field' => 'assigned_to',
                'searchable' => false,
                'sortable' => false,
                'title' => trans('admin/hardware/table.assigned_to'),
                'visible' => true,
                'formatter' => 'documentAssignmentsFormatter',
            ],
            [
                'field' => 'company',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('general.company'),
                'visible' => false,
                'formatter' => 'companiesLinkObjFormatter',
            ],
            [
                'field' => 'classification',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.classification'),
                'visible' => false,
            ],
            [
                'field' => 'issued_at',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.issued_at'),
                'visible' => false,
                'formatter' => 'dateDisplayFormatter',
            ],
            [
                'field' => 'effective_at',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.effective_at'),
                'visible' => true,
                'formatter' => 'dateDisplayFormatter',
            ],
            [
                'field' => 'next_review_at',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documents/form.next_review_at'),
                'visible' => true,
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
                'formatter' => 'documentsActionsFormatter',
                'printIgnore' => true,
            ],
        ];

        return json_encode($layout);
    }

    public function nameUrl()
    {
        return '<a href="'.route('documents.show', $this->model).'">'.e($this->model->name).'</a>';
    }

    public function fullName()
    {
        return $this->model->name;
    }
}

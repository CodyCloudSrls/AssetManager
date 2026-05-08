<?php

namespace App\Presenters;

class DocumentAssignmentPresenter extends Presenter
{
    public static function dataTableLayout()
    {
        $layout = [
            ['field' => 'id', 'searchable' => false, 'sortable' => true, 'switchable' => true, 'title' => trans('general.id'), 'visible' => false],
            ['field' => 'document', 'searchable' => true, 'sortable' => false, 'title' => trans('general.document'), 'visible' => true, 'formatter' => 'documentsLinkObjFormatter'],
            ['field' => 'document_number', 'searchable' => true, 'sortable' => false, 'title' => trans('admin/documents/form.document_number'), 'visible' => true],
            ['field' => 'document_type', 'searchable' => true, 'sortable' => false, 'title' => trans('admin/documents/form.document_type'), 'visible' => false],
            ['field' => 'assignable_type_label', 'searchable' => false, 'sortable' => false, 'title' => trans('admin/documents/form.assignable_type'), 'visible' => true],
            ['field' => 'target', 'searchable' => false, 'sortable' => false, 'title' => trans('admin/documents/form.assignable_target'), 'visible' => true, 'formatter' => 'documentAssignmentTargetFormatter'],
            ['field' => 'relation_type_label', 'searchable' => false, 'sortable' => false, 'title' => trans('admin/documents/form.assignment_relation'), 'visible' => true],
            ['field' => 'status_label', 'searchable' => false, 'sortable' => false, 'title' => trans('admin/documents/form.assignment_status'), 'visible' => true, 'formatter' => 'documentAssignmentStatusFormatter'],
            ['field' => 'approval_status_label', 'searchable' => false, 'sortable' => false, 'title' => trans('admin/documents/form.assignment_approval_status'), 'visible' => true, 'formatter' => 'documentAssignmentApprovalFormatter'],
            ['field' => 'renewal_due_at', 'searchable' => false, 'sortable' => true, 'title' => trans('admin/documents/form.assignment_renewal_due_at'), 'visible' => true, 'formatter' => 'dateDisplayFormatter'],
            ['field' => 'expires_at', 'searchable' => false, 'sortable' => true, 'title' => trans('admin/documents/form.assignment_expires_at'), 'visible' => false, 'formatter' => 'dateDisplayFormatter'],
            ['field' => 'issuer', 'searchable' => true, 'sortable' => false, 'title' => trans('admin/documents/form.assignment_issuer'), 'visible' => false, 'formatter' => 'usersLinkObjFormatter'],
            ['field' => 'reviewer', 'searchable' => true, 'sortable' => false, 'title' => trans('admin/documents/form.assignment_reviewer'), 'visible' => false, 'formatter' => 'usersLinkObjFormatter'],
            ['field' => 'reviewed_at', 'searchable' => false, 'sortable' => true, 'title' => trans('admin/documents/form.assignment_reviewed_at'), 'visible' => false, 'formatter' => 'dateDisplayFormatter'],
            ['field' => 'reference_number', 'searchable' => true, 'sortable' => true, 'title' => trans('admin/documents/form.assignment_reference_number'), 'visible' => false],
            ['field' => 'company', 'searchable' => true, 'sortable' => false, 'title' => trans('general.company'), 'visible' => false, 'formatter' => 'companiesLinkObjFormatter'],
            ['field' => 'notes', 'searchable' => true, 'sortable' => false, 'title' => trans('admin/documents/form.assignment_notes'), 'visible' => false],
            ['field' => 'review_notes', 'searchable' => true, 'sortable' => false, 'title' => trans('admin/documents/form.assignment_review_notes'), 'visible' => false],
            ['field' => 'updated_at', 'searchable' => false, 'sortable' => true, 'title' => trans('general.updated_at'), 'visible' => false, 'formatter' => 'dateDisplayFormatter'],
            ['field' => 'actions', 'searchable' => false, 'sortable' => false, 'switchable' => false, 'title' => trans('table.actions'), 'visible' => true, 'formatter' => 'documentAssignmentActionsFormatter', 'printIgnore' => true],
        ];

        return json_encode($layout);
    }
}

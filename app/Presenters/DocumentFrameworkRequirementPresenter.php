<?php

namespace App\Presenters;

class DocumentFrameworkRequirementPresenter extends Presenter
{
    public static function dataTableLayout(): string
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
                'visible' => false,
                'title' => trans('general.id'),
            ],
            [
                'field' => 'code',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documentframeworkrequirements/table.code'),
                'visible' => true,
                'formatter' => 'documentframeworkrequirementsLinkFormatter',
            ],
            [
                'field' => 'title',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documentframeworkrequirements/table.title'),
                'visible' => true,
            ],
            [
                'field' => 'domain',
                'searchable' => true,
                'sortable' => true,
                'title' => trans('admin/documentframeworkrequirements/table.domain'),
                'visible' => true,
            ],
            [
                'field' => 'coverage_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/documentframeworkrequirements/table.coverage'),
                'visible' => true,
            ],
            [
                'field' => 'risk_level_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/documentframeworkrequirements/table.risk_level'),
                'visible' => true,
            ],
            [
                'field' => 'delegation_level_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/documentframeworkrequirements/table.delegation_level'),
                'visible' => true,
            ],
            [
                'field' => 'evidence_type_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/documentframeworkrequirements/table.evidence_type'),
                'visible' => false,
            ],
            [
                'field' => 'obligation_type_label',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/documentframeworkrequirements/table.obligation_type'),
                'visible' => false,
            ],
            [
                'field' => 'parent_requirement_codes',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/documentframeworkrequirements/table.parent'),
                'visible' => false,
            ],
            [
                'field' => 'documents_count',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('general.documents'),
                'visible' => true,
                'formatter' => 'documentFrameworkRequirementDocumentsCountFormatter',
            ],
            [
                'field' => 'minimum_required_documents',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/documentframeworkrequirements/table.minimum_required_documents'),
                'visible' => false,
            ],
            [
                'field' => 'owner',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/documentframeworkrequirements/table.owner'),
                'visible' => true,
                'formatter' => 'usersLinkObjFormatter',
            ],
            [
                'field' => 'default_document_type',
                'searchable' => true,
                'sortable' => false,
                'title' => trans('admin/documentframeworkrequirements/table.default_document_type'),
                'visible' => true,
            ],
            [
                'field' => 'review_frequency_months',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/documentframeworkrequirements/table.review_frequency_months'),
                'visible' => false,
            ],
            [
                'field' => 'is_mandatory',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/documentframeworkrequirements/table.is_mandatory'),
                'visible' => false,
                'formatter' => 'trueFalseFormatter',
            ],
            [
                'field' => 'is_active',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/documentframeworkrequirements/table.is_active'),
                'visible' => false,
                'formatter' => 'trueFalseFormatter',
            ],
            [
                'field' => 'sort_order',
                'searchable' => false,
                'sortable' => true,
                'title' => trans('admin/documentframeworkrequirements/table.sort_order'),
                'visible' => false,
            ],
            [
                'field' => 'actions',
                'searchable' => false,
                'sortable' => false,
                'title' => trans('table.actions'),
                'visible' => true,
                'formatter' => 'documentframeworkrequirementsActionsFormatter',
                'printIgnore' => true,
                'class' => 'hidden-print',
            ],
        ];

        return json_encode($layout);
    }
}

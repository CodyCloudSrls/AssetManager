<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\Compliance\ComplianceDomainAccess;
use App\Support\Documents\DocumentAreaAccess;

class DocumentPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documents';
    }

    public function before(User $user, $ability, $item = null)
    {
        if ($item instanceof Document) {
            if (! ComplianceDomainAccess::canAccessDocument($item, $user)) {
                return false;
            }

            if (! DocumentAreaAccess::can($user, $item->document_area, $ability)) {
                return false;
            }
        }

        return parent::before($user, $ability, $item);
    }

    public function view(User $user, $item = null)
    {
        return parent::view($user, $item)
            && (! $item instanceof Document || DocumentAreaAccess::can($user, $item->document_area, 'view'));
    }

    public function update(User $user, $item = null)
    {
        return parent::update($user, $item)
            && (! $item instanceof Document || DocumentAreaAccess::can($user, $item->document_area, 'update'));
    }

    public function delete(User $user, $item = null)
    {
        return parent::delete($user, $item)
            && (! $item instanceof Document || DocumentAreaAccess::can($user, $item->document_area, 'delete'));
    }

    public function files(User $user, $item = null)
    {
        return parent::files($user, $item)
            && (! $item instanceof Document || DocumentAreaAccess::can($user, $item->document_area, 'files'));
    }

    public function viewFiles(User $user, $item = null)
    {
        return parent::viewFiles($user, $item)
            && (! $item instanceof Document || DocumentAreaAccess::can($user, $item->document_area, 'viewFiles'));
    }

    public function mapRequirements(User $user, $item = null)
    {
        if ($item instanceof Document) {
            return $user->hasAccess('documents.requirements.map')
                && ComplianceDomainAccess::canAccessDocument($item, $user)
                && DocumentAreaAccess::can($user, $item->document_area, 'update');
        }

        return $user->hasAccess('documents.requirements.map');
    }

    public function restore(User $user, $item = null)
    {
        return $user->hasAccess('documents.restore')
            && (! $item instanceof Document || DocumentAreaAccess::can($user, $item->document_area, 'restore'));
    }

    public function forceDelete(User $user, $item = null)
    {
        return $user->hasAccess('documents.force_delete')
            && (! $item instanceof Document || ($item->trashed() && DocumentAreaAccess::can($user, $item->document_area, 'forceDelete')));
    }
}

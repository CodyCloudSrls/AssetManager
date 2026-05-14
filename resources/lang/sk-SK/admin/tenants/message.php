<?php

return array (
  'create' =>
  array (
    'success' => 'Tenant created successfully.',
    'error' => 'Tenant could not be created.',
  ),
  'delete' =>
  array (
    'success' => 'Tenant deleted successfully.',
    'not_deletable' => 'This tenant cannot be deleted because it still contains operational data or tenant memberships.',
  ),
  'membership' =>
  array (
    'create' =>
    array (
      'success' => 'Tenant user assigned successfully.',
    ),
    'update' =>
    array (
      'success' => 'Tenant role updated successfully.',
    ),
    'delete' =>
    array (
      'success' => 'Tenant user removed successfully.',
    ),
  ),
  'helpdesk' =>
  array (
    'update' =>
    array (
      'success' => 'Helpdesk configuration updated successfully.',
    ),
    'disabled' => 'The public helpdesk portal is not enabled for this tenant.',
    'attachments_disabled' => 'Attachments are disabled for this tenant public helpdesk portal.',
    'no_public_types' => 'Enable at least one public ticket type before activating the helpdesk portal.',
  ),
  'mail' =>
  array (
    'update' =>
    array (
      'success' => 'Tenant mail settings updated successfully.',
    ),
  ),
  'settings' =>
  array (
    'update' =>
    array (
      'success' => 'Tenant settings updated successfully.',
    ),
    'bootstrap' =>
    array (
      'success' => 'Bootstrap completed for :locale: :frameworks framework(s) and :requirements requirement(s) created.',
            'safe_update_success' => 'Compliance pack safe update completed for :locale: :applied pack(s) applied, :frameworks framework(s) created, :requirements requirement(s) created, :manual_review manual review, :skipped skipped.',
    ),
  ),
);

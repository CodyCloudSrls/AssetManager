<?php

return array (
  'create' => 'Create framework requirement',
  'update' => 'Update framework requirement',
  'coverage' =>
  array (
    'missing' => 'Missing',
    'supporting_only' => 'Supporting Only',
    'at_risk' => 'At Risk',
    'covered' => 'Covered',
  ),
  'obligation_types' =>
  array (
    'governance' => 'Governance',
    'registration' => 'Registration and information updates',
    'risk_management' => 'Risk Management',
    'incident_reporting' => 'Incident Reporting',
    'supply_chain' => 'Supply Chain',
    'asset_inventory' => 'Asset Inventory',
    'business_continuity' => 'Business Continuity',
    'training' => 'Training',
    'privacy_governance' => 'Privacy Governance',
    'custom' => 'Custom',
  ),
  'evidence_types' =>
  array (
    'policy' => 'Policy',
    'procedure' => 'Procedure',
    'register' => 'Register',
    'assessment' => 'Assessment',
    'contract' => 'Contract',
    'technical_report' => 'Technical Report',
    'incident_record' => 'Incident Record',
    'training_record' => 'Training Record',
    'attestation' => 'Attestation',
    'other' => 'Other',
  ),
  'delegation_levels' =>
  array (
    'owner_review' => 'Delegable with owner review',
    'delegable' => 'Delegable',
    'external_evidence' => 'External evidence',
    'consultant_only' => 'Consultant only',
  ),
  'risk_levels' =>
  array (
    'not_applicable' => 'Not applicable',
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'critical' => 'Critical',
  ),
  'work_queue' => 'Requirement Work Queue',
  'all_frameworks' => 'All frameworks',
  'all_coverage_statuses' => 'All coverage statuses',
  'apply_filters' => 'Apply filters',
  'clear_filters' => 'Clear filters',
  'coverage_help' => 'Coverage is calculated from linked documents: missing when there is no evidence, supporting only when there is no primary evidence, at risk when primary evidence is expired or not active, covered when valid primary evidence exists.',
  'nis_risk_help' => 'NIS2 requirements do not carry a manual risk score here. Risk is calculated in the NIS2 risk matrix from assets, services and suppliers, then reported separately.',
  'risk_level_help' => 'Use this value only as a framework-level priority or residual-risk note when the framework explicitly requires it.',
  'parent_help' => 'Select one or more parent requirements when the control maps to multiple upstream NIS2 references.',
  'parent_cycle_error' => 'Parent requirements cannot create a circular requirement relationship.',
);

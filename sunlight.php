<?php

require_once 'sunlight.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function sunlight_civicrm_config(&$config) {
  _sunlight_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function sunlight_civicrm_xmlMenu(&$files) {
  _sunlight_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sunlight_civicrm_install() {
    //Congress API
    $congress_job_params = array(
      'sequential' => 1,
      'name'          => 'Sunlight Foundation Congress API - Legislators',
      'description'   => 'Creates US legislator contacts via the Sunlight Foundation Congress API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'Congress',
      'api_action'    => 'legs',
      'is_active'     => 0,
    );
    $congress_job = civicrm_api3('job', 'create', $congress_job_params);

    //Contact US districts
    $congress_districts_job_params = array(
      'sequential' => 1,
      'name'          => 'Sunlight Foundation Congress API - Districts',
      'description'   => 'Adds US legislative districts to contacts',
      'run_frequency' => 'Daily',
      'api_entity'    => 'Congress',
      'api_action'    => 'districts',
      'is_active'     => 0,
    );
    $congress_districts_job = civicrm_api3('job', 'create', $congress_districts_job_params);

    //OpenStates API
    $openstates_job_params = array(
      'sequential' => 1,
      'name'          => 'Sunlight Foundation Open States API - Representatives',
      'description'   => 'Creates state representative contacts via the Sunlight Foundation Open States API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'OpenStates',
      'api_action'    => 'reps',
      'is_active'     => 0,
    );
    $openstates_job = civicrm_api3('job', 'create', $openstates_job_params);

    //Contact state districts
    $openstates_districts_job_params = array(
      'sequential' => 1,
      'name'          => 'Sunlight Foundation Open States API - Districts',
      'description'   => 'Adds state representative districts to contacts',
      'run_frequency' => 'Daily',
      'api_entity'    => 'OpenStates',
      'api_action'    => 'districts',
      'is_active'     => 0,
    );
    $openstates_districts_job = civicrm_api3('job', 'create', $openstates_districts_job_params);

    return _sunlight_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function sunlight_civicrm_uninstall() {
  // create a sync job
  $jobs = civicrm_api3('Job', 'get', array(
    'return' => "id",
    'name' => array('LIKE' => "Sunlight Foundation%"),
  ));

  foreach ($jobs['values'] as $job) {
    $job_delete = civicrm_api3('job', 'delete', array('id' => $job['id'] ));
  }

  return _sunlight_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function sunlight_civicrm_enable() {
  _sunlight_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function sunlight_civicrm_disable() {
  _sunlight_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function sunlight_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sunlight_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function sunlight_civicrm_managed(&$entities) {
  _sunlight_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function sunlight_civicrm_caseTypes(&$caseTypes) {
  _sunlight_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function sunlight_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _sunlight_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

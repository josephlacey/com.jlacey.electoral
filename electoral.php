<?php

require_once 'electoral.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function electoral_civicrm_config(&$config) {
  _electoral_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function electoral_civicrm_xmlMenu(&$files) {
  _electoral_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_navigationMenu
 *
 * @param $params array
 */
function electoral_civicrm_navigationMenu(&$params) {
  $path = "Administer/System Settings";
  $item = array(
    'label' => ts('Electoral API', array('coop.palantetech.electoral')),
    'name' => 'Electoral API',
    'url' => 'civicrm/admin/setting/electoral',
    'permission' => 'administer CiviCRM',
    'operator' => '',
    'separator' => '',
    'active' => 1,
  );

  $navigation = _electoral_civix_insert_navigation_menu($params, $path, $item);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function electoral_civicrm_install() {
  _electoral_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function electoral_civicrm_uninstall() {
  _electoral_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function electoral_civicrm_enable() {
  _electoral_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function electoral_civicrm_disable() {
  _electoral_civix_civicrm_disable();
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
function electoral_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _electoral_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function electoral_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'coop.palantetech.electoral',
    'name' => 'googlecivicinfo_country_districts',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - Country Districts',
      'description'   => 'Adds US legislative districts to contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'districts',
      'parameters'    => "level=country\nlimit=100\nupdate=0",
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'coop.palantetech.electoral',
    'name' => 'googlecivicinfo_state_province_districts',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - State and Province Districts',
      'description'   => 'Adds state and province districts to contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'districts',
      'parameters'    => "level=administrativeArea1\nlimit=100\nupdate=0",
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'coop.palantetech.electoral',
    'name' => 'googlecivicinfo_county_districts',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - County Districts',
      'description'   => 'Adds county legislative districts to contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'districts',
      'parameters'    => "level=administrativeArea2\nlimit=100\nupdate=0",
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'coop.palantetech.electoral',
    'name' => 'googlecivicinfo_city_districts',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - City Districts',
      'description'   => 'Adds city legislative districts to contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'districts',
      'parameters'    => "level=locality\nlimit=100\nupdate=0",
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'coop.palantetech.electoral',
    'name' => 'googlecivicinfo_reps',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - Representatives',
      'description'   => 'Creates representative contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'reps',
      'is_active'     => 0,
    ),
  );
  _electoral_civix_civicrm_managed($entities);
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
function electoral_civicrm_caseTypes(&$caseTypes) {
  _electoral_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function electoral_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _electoral_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

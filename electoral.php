<?php

require_once 'electoral.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function electoral_civicrm_config(&$config) {
  _electoral_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function electoral_civicrm_xmlMenu(&$files) {
  _electoral_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 */
function electoral_civicrm_navigationMenu(&$params) {
  $path = "Administer/System Settings";
  $item = array(
    'label' => ts('Electoral API', array('com.jlacey.electoral')),
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
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function electoral_civicrm_install() {

  $demTagExists = civicrm_api3('Tag', 'getcount', ['name' => "Democrat"]);
  if ($demTagExists == 0) {
    $demTag = civicrm_api3('Tag', 'create', ['name' => "Democrat"]);
  }
  $repTagExists = civicrm_api3('Tag', 'getcount', ['name' => "Republican"]);
  if ($repTagExists == 0) {
    $repTag = civicrm_api3('Tag', 'create', ['name' => "Republican"]);
  }
  $indTagExists = civicrm_api3('Tag', 'getcount', ['name' => "Independent"]);
  if ($indTagExists == 0) {
    $indTag = civicrm_api3('Tag', 'create', ['name' => "Independent"]);
  }

  _electoral_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function electoral_civicrm_uninstall() {
  _electoral_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function electoral_civicrm_enable() {
  _electoral_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function electoral_civicrm_disable() {
  _electoral_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function electoral_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _electoral_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function electoral_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'com.jlacey.electoral',
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
    'module' => 'com.jlacey.electoral',
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
    'module' => 'com.jlacey.electoral',
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
    'module' => 'com.jlacey.electoral',
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
    'module' => 'com.jlacey.electoral',
    'name' => 'googlecivicinfo_country_reps',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - Country Representatives',
      'description'   => 'Adds US representative contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'reps',
      'parameters'    => "level=country\nroles=legislatorUpperBody,legislatorLowerBody",
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'com.jlacey.electoral',
    'name' => 'googlecivicinfo_state_province_reps',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - State and Province Representatives',
      'description'   => 'Adds US state representive contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'reps',
      'parameters'    => "level=administrativeArea1\nroles=legislatorUpperBody,legislatorLowerBody",
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'com.jlacey.electoral',
    'name' => 'googlecivicinfo_county_reps',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - County Representatives',
      'description'   => 'Adds US county represenative contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'reps',
      'parameters'    => "level=administrativeArea2",
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'com.jlacey.electoral',
    'name' => 'googlecivicinfo_city_reps',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - City Representatives',
      'description'   => 'Adds US city representative contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'reps',
      'parameters'    => "level=locality",
      'is_active'     => 0,
    ),
  );
  _electoral_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
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
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function electoral_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _electoral_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

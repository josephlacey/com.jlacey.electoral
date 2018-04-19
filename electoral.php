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
    return _electoral_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function electoral_civicrm_uninstall() {
  return _electoral_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function electoral_civicrm_enable() {
  electoral_create_custom_fields();
  return _electoral_civix_civicrm_enable();
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
    'name' => 'googlecivicinfo_districts',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - Districts',
      'description'   => 'Adds US legislative districts to contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'districts',
      'parameters'    => 'limit=100',
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'coop.palantetech.electoral',
    'name' => 'googlecivicinfo_reps',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Google Civic Information API - Legislators',
      'description'   => 'Creates US legislator contacts via the Google Civic Information API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'GoogleCivicInformation',
      'api_action'    => 'reps',
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'coop.palantetech.electoral',
    'name' => 'openstates_districts',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'         => 'Open States API - Districts',
      'description'   => 'Adds state representative districts to contacts',
      'run_frequency' => 'Daily',
      'api_entity'    => 'openStates',
      'api_action'    => 'districts',
      'parameters'    => 'limit=100',
      'is_active'     => 0,
    ),
  );
  $entities[] = array(
    'module' => 'coop.palantetech.electoral',
    'name' => 'openstates_reps',
    'entity' => 'Job',
    'params' => array(
      'version' => 3,
      'name'          => 'Open States API - Representatives',
      'description'   => 'Creates state representative contacts via the Open States API',
      'run_frequency' => 'Daily',
      'api_entity'    => 'openStates',
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

/**
 * Create the custom fields used to record subject and body
 *
 */
function electoral_create_custom_fields() {
  //Check if Representation Details custom data group already exists
  $rd_group = civicrm_api3('CustomGroup', 'get', array( 'title' => "Representative Details", ));

  if ($rd_group['count'] == 0) {
    //If not, create it
    $rd_group_create = civicrm_api3('CustomGroup', 'create', array(
      'sequential' => 1,
      'title' => "Representative Details",
      'extends' => "Contact",
      'name' => "Representative_Details",
      'style' => "Tab with table",
      'is_multiple' => 1,
    ));

    //Create Level Option Group and Values
    $rd_level_og = civicrm_api3('OptionGroup', 'create', array(
      'name' => "electoral_level_options",
      'title' => "Level",
      'is_active' => 1,
    ));
    $rd_level_id = $rd_level_og['id'];
    $rd_level_congress = civicrm_api3('OptionValue', 'create', array(
      'option_group_id' => "electoral_level_options",
      'label' => "Federal",
      'value' => "congress",
      'name' => "Federal",
      'weight' => 1,
      'is_active' => 1,
    ));
    $rd_level_openstates = civicrm_api3('OptionValue', 'create', array(
      'option_group_id' => "electoral_level_options",
      'label' => "State/Province",
      'value' => "openstates",
      'name' => "State/Province",
      'weight' => 2,
      'is_active' => 1,
    ));
    $rd_level_city = civicrm_api3('OptionValue', 'create', array(
      'option_group_id' => "electoral_level_options",
      'label' => "City",
      'value' => "nytimes",
      'name' => "City",
      'weight' => 3,
      'is_active' => 1,
    ));
    
    //Create Chamber Option Group and Values
    $rd_chamber_og = civicrm_api3('OptionGroup', 'create', array(
      'name' => "electoral_chamber_options",
      'title' => "Chamber",
      'is_active' => 1,
    ));
    $rd_chamber_id = $rd_chamber_og['id'];
    $rd_chamber_upper = civicrm_api3('OptionValue', 'create', array(
      'option_group_id' => "electoral_chamber_options",
      'label' => "Upper",
      'value' => "upper",
      'name' => "Upper",
      'weight' => 1,
      'is_active' => 1,
    ));
    $rd_chamber_lower = civicrm_api3('OptionValue', 'create', array(
      'option_group_id' => "electoral_chamber_options",
      'label' => "Lower",
      'value' => "lower",
      'name' => "Lower",
      'weight' => 2,
      'is_active' => 1,
    ));
    
    //Create Representative Details Fields
    $rd_level_field = civicrm_api3('CustomField', 'create', array(
      'custom_group_id' => "Representative_Details",
      'label' => "Level",
      'name' => "electoral_level",
      'data_type' => "String",
      'html_type' => "Select",
      'is_searchable' => 1,
      'weight' => 1,
      'is_active' => 1,
      'option_group_id' => $rd_level_id,
      'in_selector' => 1,
    ));
    $rd_states_provinces_field = civicrm_api3('CustomField', 'create', array(
      'custom_group_id' => "Representative_Details",
      'label' => "States/Provinces",
      'name' => "electoral_states_provinces",
      'data_type' => "StateProvince",
      'html_type' => "Select State/Province",
      'is_searchable' => 1,
      'weight' => 2,
      'is_active' => 1,
      'in_selector' => 1,
    ));
    $rd_chamber_field = civicrm_api3('CustomField', 'create', array(
      'custom_group_id' => "Representative_Details",
      'label' => "Chamber",
      'name' => "electoral_chamber",
      'data_type' => "String",
      'html_type' => "Select",
      'is_searchable' => 1,
      'weight' => 3,
      'is_active' => 1,
      'option_group_id' => $rd_chamber_id,
      'in_selector' => 1,
    ));
    $rd_district_field = civicrm_api3('CustomField', 'create', array(
      'custom_group_id' => "Representative_Details",
      'label' => "District",
      'name' => "electoral_district",
      'data_type' => "String",
      'html_type' => "Text",
      'is_searchable' => 1,
      'weight' => 4,
      'is_active' => 1,
      'in_selector' => 1,
    ));
    $rd_in_office_field = civicrm_api3('CustomField', 'create', array(
      'sequential' => 1,
      'custom_group_id' => "Representative_Details",
      'label' => "In Office?",
      'name' => "electoral_in_office",
      'data_type' => "Boolean",
      'html_type' => "Radio",
      'is_searchable' => 1,
      'weight' => 5,
      'is_active' => 1,
      'in_selector' => 1,
    ));
  }
}


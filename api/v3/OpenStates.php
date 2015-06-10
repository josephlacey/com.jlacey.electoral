<?php 

/**
 * Sunlight Foundation Open States API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */ 
function civicrm_api3_open_states_reps($params) {

  //sunlight_open_states_reps('upper', 'NY');
  //sunlight_open_states_reps('lower', 'NY');
  //sunlight_open_states_reps('upper', 'CA');
  //sunlight_open_states_reps('lower', 'CA');
  
  return civicrm_api3_create_success(array(1), array("Sunlight Foundation Open States API - Representatives successful."));

}

function sunlight_open_states_reps($chamber, $state = NULL) {

  //FIXME Update API key to CCR specific one
  //FIXME Admin UI field?
  $apikey = 'fd2e8ef1c3554b7ebf030670e34e3763';

  //Assemble the API URL
  $url = "http://openstates.org/api/v1/legislators/?apikey=$apikey&active=true&per_page=all&chamber=$chamber";
  if ($state != NULL  ){
    $url .= "&state=$state";
  }

  //Get results from API and decode the JSON
  $reps = json_decode(file_get_contents($url), TRUE);
  //CRM_Core_Error::debug_var('reps', $reps);

  $states = CRM_Core_PseudoConstant::stateProvinceForCountry(1228, 'abbreviation');

  $rep_count = 0;

  foreach( $reps as $rep ) {

    $rep_count++;

    //Clear variables before each create
    $rep_id = $civicrm_contact_id = $cid = '';
    $rep_contact_params = $rep_contact = '';
    $rep_email_params = $rep_email = $rep_email_exist = $rep_email_exist_id = '';

    //CRM_Core_Error::debug_var('rep', $rep);

    //Use the Sunlight Leg ID as the external ID
    //and find if representative exists already.
    $rep_id = $rep['leg_id'];
    //CRM_Core_Error::debug_var('rep_id', $rep_id);

    $civicrm_contact_id = civicrm_api3('Contact', 'get', array(
      'return' => 'id',
      'external_identifier' => "$rep_id",
    ));
    //CRM_Core_Error::debug_var('civicrm_contact_id', $civicrm_contact_id);

    if ($civicrm_contact_id['count'] == 1 ) {
      $cid = $civicrm_contact_id['id'];
    }
    //CRM_Core_Error::debug_var('cid', $cid);

    //Create the CiviCRM Contact
    $rep_first_name = $rep['first_name'];
    $rep_last_name = $rep['last_name'];
    $rep_chamber = $rep['chamber'];
    $rep_state = array_search(strtoupper($rep['state']), $states);
    $rep_district = $rep['district'];

    $rep_contact_params = array(
      'external_identifier' => "$rep_id",
      'contact_type' => 'Individual',
      'first_name' => "$rep_first_name",
      'last_name' => "$rep_last_name",
    );
    //Only add the CiviCRM Contact ID as a create param if set   
    if ($cid != '') {
      $rep_contact_params['id'] = $cid;
    }
    //CRM_Core_Error::debug_var('rep_contact_params', $rep_contact_params);

    $rep_contact = civicrm_api3('Contact', 'create', $rep_contact_params);
    //CRM_Core_Error::debug_var('rep_contact', $rep_contact);

    //Repeating the Contact ID set in case this is a contact creation
    //and it's not set above.
    $cid = $rep_contact['id'];

    $rep_details = civicrm_api3('CustomValue', 'get', array(
      'sequential' => 1,
      'entity_id' => $cid,
      'custom_group_id' => 16,
    ));
    //FIXME this doesn't update an existing entry on a multi-value custom data set
    $rep_details_update = civicrm_api3('CustomValue', 'create', array(
      'entity_id' => $cid,
      'custom_Representative_Details:Level' => 'openstates',
      'custom_Representative_Details:Chamber' => "$rep_chamber",
      'custom_Representative_Details:State' => "$rep_state",
      'custom_Representative_Details:District' => "$rep_district",
      'custom_Representative_Details:In office?' => 1,
    ));

    //Create the Email address
    if ($rep['email'] != '') {
      //CRM_Core_Error::debug_var('cid', $cid);
  
      $rep_email = $rep['email'];
      //Check if contact has an email addres set, Main location type
      $rep_email_exist = civicrm_api3('Email', 'get', array(
        'return' => "email",
        'contact_id' => $cid,
        'is_primary' => 1,
        'location_type_id' => 3,
      ));
      //CRM_Core_Error::debug_var('rep_email_exist', $rep_email_exist);
  
      //If there is an existing email address,
      //set the id and the Sunlight email address for comparison
      if ($rep_email_exist['count'] > 0) {
        $rep_email_exist_id = $rep_email_exist['id'];
      }
  
      //Add an updated email address or a new one if none exist, 
      //and set it to primary
      if ( ($rep_email_exist['count'] == 1 && $rep_email_exist['values'][$rep_email_exist_id]['email'] != strtolower($rep_email)) || 
           $rep_email_exist['count'] == 0 ) {
        $rep_email_params = array(
          'contact_id' => $cid,
          'location_type_id' => 3,
          'is_primary' => 1,
          'email' => $rep_email,
        );
  
        $rep_email = civicrm_api3('Email', 'create', $rep_email_params);
        //CRM_Core_Error::debug_var('rep_email', $rep_email);
      }
    }
  }
  CRM_Core_Error::debug_var("Number of $state $chamber chamber representatives created", $rep_count);
}

function civicrm_api3_open_states_districts($params) {

  dsm($params);
  return civicrm_api3_create_success(array(1), array("Sunlight Foundation Open States API - Districts successful."));

}


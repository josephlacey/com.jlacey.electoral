<?php 

/**
 * Sunlight Foundation Congress API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */ 
function civicrm_api3_congress_legs($params) {

  sunlight_congress_legs('senate');
  sunlight_congress_legs('house');
  
  return civicrm_api3_create_success(array(1), array("Sunlight Foundation Congress API - Legislators successful."));

}

function sunlight_congress_legs($chamber) {

  $apikey = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'sunlightFoundationAPIKey'));

  //Assemble the API URL
  $url = "https://congress.api.sunlightfoundation.com/legislators?apikey=$apikey&in_office=true&per_page=all&chamber=$chamber";

  //Get results from API and decode the JSON
  $legislators = json_decode(file_get_contents($url), TRUE);
  //CRM_Core_Error::debug_var('legislators', $legislators);

  $states = CRM_Core_PseudoConstant::stateProvinceForCountry(1228, 'abbreviation');
  $leg_count = 0;

  foreach( $legislators['results'] as $legislator ) {

    $leg_count++;

    //Clear variables before each create
    $leg_id = $civicrm_contact_id = $cid = '';
    $leg_contact_params = $leg_contact = '';
    $leg_email_params = $leg_email = $leg_email_exist = $leg_email_exist_id = '';

    //CRM_Core_Error::debug_var('legislator', $legislator);

    //Use the Sunlight Bio Guide ID as the external ID
    //and find if legislator exists already.
    $leg_id = $legislator['bioguide_id'];
    //CRM_Core_Error::debug_var('leg_id', $leg_id);

    $civicrm_contact_id = civicrm_api3('Contact', 'get', array(
      'return' => 'id',
      'external_identifier' => "$leg_id",
    ));
    //CRM_Core_Error::debug_var('civicrm_contact_id', $civicrm_contact_id);

    if ( $civicrm_contact_id['count'] == 1 ) {
      $cid = $civicrm_contact_id['id'];
    }
    //CRM_Core_Error::debug_var('cid', $cid);

    //Create or update the CiviCRM Contact
    $leg_first_name = $legislator['first_name'];
    $leg_last_name = $legislator['last_name'];
    $leg_state = array_search($legislator['state'], $states);
    $leg_chamber = $legislator['chamber'];
    switch ($leg_chamber) {
      case 'senate':
        $leg_chamber = 'upper';
        break;
      case 'house':
        $leg_chamber = 'lower';
        break;
    }
    $leg_district = $legislator['district'];

    $leg_contact_params = array(
      'external_identifier' => "$leg_id",
      'contact_type' => 'Individual',
      'first_name' => "$leg_first_name",
      'last_name' => "$leg_last_name",
      'do_not_email' => 1,
    );
    //Only add the CiviCRM Contact ID as a create param if set   
    if ( $cid != '' ) {
      $leg_contact_params['id'] = $cid;
    }
    //CRM_Core_Error::debug_var('leg_contact_params', $leg_contact_params);

    $leg_contact = civicrm_api3('Contact', 'create', $leg_contact_params);
    //CRM_Core_Error::debug_var('leg_contact', $leg_contact);

    //Repeating the Contact ID set in case this is a contact creation
    //and it's not set above.
    $cid = $leg_contact['id'];

    $leg_details = civicrm_api3('CustomValue', 'get', array(
      'sequential' => 1,
      'entity_id' => $cid,
      'custom_group_id' => 16,
    ));
    //FIXME this doesn't update an existing entry on a multi-value custom data set
    $leg_details_update = civicrm_api3('CustomValue', 'create', array(
      'entity_id' => $cid,
      'custom_Representative_Details:Level' => 'congress',
      'custom_Representative_Details:State' => "$leg_state",
      'custom_Representative_Details:Chamber' => "$leg_chamber",
      'custom_Representative_Details:District' => "$leg_district",
      'custom_Representative_Details:In office?' => 1,
    ));

    //Create the Email address
    //Check if contact has an email addres set, Main location type
    $leg_email_exist = civicrm_api3('Email', 'get', array(
      'return' => "email",
      'contact_id' => $cid,
      'is_primary' => 1,
      'location_type_id' => 3,
    ));
    //CRM_Core_Error::debug_var('leg_email_exist', $leg_email_exist);

    //If there is an existing email address, set the id for comparison
    if ($leg_email_exist['count'] > 0) {
      $leg_email_exist_id = $leg_email_exist['id'];
    }
    if ( $legislator['oc_email'] != NULL ) {
      $leg_email = $legislator['oc_email'];
      
      //Add an updated email address or a new one if none exist, 
      //and set it to primary
      if ( ( $leg_email_exist['count'] == 1 && $leg_email_exist['values'][$leg_email_exist_id]['email'] != strtolower($leg_email ) ) || 
           $leg_email_exist['count'] == 0 ) {
        $leg_email_params = array(
          'contact_id' => $cid,
          'location_type_id' => 3,
          'is_primary' => 1,
          'email' => $leg_email,
        );
      
        $leg_email = civicrm_api3('Email', 'create', $leg_email_params);
        //CRM_Core_Error::debug_var('leg_email', $leg_email);
      }
    }
  }
  CRM_Core_Error::debug_var("Number of $chamber legislators created", $leg_count);
}

function civicrm_api3_congress_districts($params) {

  $limit = '';
  if (isset($params['limit']) && is_numeric($params['limit'])) {
    $limit = $params['limit'];
  } else {
    return civicrm_api3_create_error(array(1), array("Sunlight Foundation Congress API - Districts limit is not an integer."));
  }
  sunlight_congress_districts($params['limit']);
  return civicrm_api3_create_success(array(1), array("Sunlight Foundation Congress API - Districts successful."));

}

function sunlight_congress_districts($limit) {

  $apikey = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'sunlightFoundationAPIKey'));

  $states = CRM_Core_PseudoConstant::stateProvinceForCountry(1228, 'abbreviation');

  //geo_code1 = latitude
  //geo_code2 = longitude
  //FIXME this assumes the Home location type because we're assuming that's where folks are registered
  //this obviously assumes a certain data model.  Should this assume the primary address?  
  //the default address type?  Or should there be an address flag that is checked?
  $contact_addresses = civicrm_api3('Address', 'get', array(
    'return' => "contact_id,geo_code_1,geo_code_2",
    //'contact_id' => array('IS NOT NULL' => 1),
    'contact_id' => 202,
    'location_type_id' => 1,
    'country_id' => 1228,
    'geo_code_1' => array('IS NOT NULL' => 1),
    'geo_code_2' => array('IS NOT NULL' => 1),
    'options' => array('limit' => $limit),
  ));

  foreach($contact_addresses['values'] as $address) {
    //CRM_Core_Error::debug_var('address', $address);

    $latitude = $longitude = $districts = $contact_id = '';
    
    $latitude = $address['geo_code_1'];
    $longitude = $address['geo_code_2'];

    //Assemble the API URL
    $url = "https://congress.api.sunlightfoundation.com/districts/locate?apikey=$apikey&latitude=$latitude&longitude=$longitude";
    //CRM_Core_Error::debug_var('url', $url);

    //Get results from API and decode the JSON
    $districts = json_decode(file_get_contents($url), TRUE);
    //CRM_Core_Error::debug_var('districts', $districts);

    if( $districts['count'] == 1 ) {
      $contact_id = $address['contact_id'];
      $contact_state = array_search($districts['results'][0]['state'], $states);
      $contact_district = $districts['results'][0]['district'];

      //Update the CiviCRM Contact
      //FIXME this doesn't update an existing entry on a multi-value custom data set
      $contact_rep_details_update = civicrm_api3('CustomValue', 'create', array(
        'entity_id' => $contact_id,
        'custom_Representative_Details:Level' => 'congress',
        'custom_Representative_Details:State' => "$contact_state",
        'custom_Representative_Details:District' => "$contact_district",
      ));
    }
  }
}

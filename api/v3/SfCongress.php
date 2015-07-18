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
function civicrm_api3_sf_congress_legs($params) {

  electoral_sf_congress_legs('senate');
  electoral_sf_congress_legs('house');
  
  return civicrm_api3_create_success(array(1), array("Sunlight Foundation Congress API - Legislators successful."));

}

function electoral_sf_congress_legs($chamber) {

  $apikey = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'sunlightFoundationAPIKey'));

  //Assemble the API URL
  $url = "https://congress.api.sunlightfoundation.com/legislators?apikey=$apikey&in_office=true&per_page=all&chamber=$chamber";

  //Intitalize curl
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  //Get results from API and decode the JSON
  $legislators = json_decode(curl_exec($ch), TRUE);

  //Close curl
  curl_close($ch);

  $states = CRM_Core_PseudoConstant::stateProvinceForCountry(1228, 'abbreviation');
  $leg_count = 0;

  foreach( $legislators['results'] as $legislator ) {

    $leg_count++;

    //Clear variables before each create
    $leg_id = $civicrm_contact_id = $contact_id = '';
    $leg_contact_params = $leg_contact = '';
    $leg_email_params = $leg_email = $leg_email_exist = $leg_email_exist_id = '';


    //Use the Sunlight Bio Guide ID as the external ID
    //and find if legislator exists already.
    $leg_id = $legislator['bioguide_id'];

    $civicrm_contact_id = civicrm_api3('Contact', 'get', array(
      'return' => 'id',
      'external_identifier' => "$leg_id",
    ));

    if ( $civicrm_contact_id['count'] == 1 ) {
      $contact_id = $civicrm_contact_id['id'];
    }

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
    if ( $contact_id != '' ) {
      $leg_contact_params['id'] = $contact_id;
    }

    $leg_contact = civicrm_api3('Contact', 'create', $leg_contact_params);

    //Repeating the Contact ID set in case this is a contact creation
    //and it's not set above.
    $contact_id = $leg_contact['id'];

    //Need to determine if this is a create or an update, 
    //so need to find is there's a value for the custom data
    //Find Level custom field id number
    //FIXME Updates to the multi-value custom data sets aren't currently working
    //We're keeping this check in place to avoid duplicate data
    $rep_details_level_id = civicrm_api3('CustomField', 'getvalue', array(
      'return' => "id",
      'custom_group_id' => "Representative_Details",
      'label' => "Level",
    ));
    $rep_details_level_field = 'custom_' . $rep_details_level_id;
    $leg_rep_details_exists = civicrm_api3('Contact', 'get', array(
      'return' => "id",
      'id' => $contact_id,
      "$rep_details_level_field" => "congress",
    ));

    if ($leg_rep_details_exists['count'] == 1) {
      /*
      $rep_details_states_provinces_id = civicrm_api3('CustomField', 'getvalue', array(
        'return' => "id",
        'custom_group_id' => "Representative_Details",
        'label' => "States/Provinces",
      ));
      $rep_details_states_provinces_field = 'custom_' . $rep_details_states_provinces_id;
      $rep_details_chamber_id = civicrm_api3('CustomField', 'getvalue', array(
        'return' => "id",
        'custom_group_id' => "Representative_Details",
        'label' => "Chamber",
      ));
      $rep_details_chamber_field = 'custom_' . $rep_details_chamber_id;
      $rep_details_district_id = civicrm_api3('CustomField', 'getvalue', array(
        'return' => "id",
        'custom_group_id' => "Representative_Details",
        'label' => "District",
      ));
      $rep_details_district_field = 'custom_' . $rep_details_district_id;
      $rep_details_in_office_id = civicrm_api3('CustomField', 'getvalue', array(
        'return' => "id",
        'custom_group_id' => "Representative_Details",
        'label' => "In Office?",
      ));
      $rep_details_in_office_field = 'custom_' . $rep_details_in_office_id;
      $leg_rep_details_update = civicrm_api3('Contact', 'create', array(
        'id' => $contact_id,
        'contact_type' => "Individual",
        "$rep_details_level_field" => "congress",
        "$rep_details_states_provinces_field" => "$leg_state",
        "$rep_details_chamber_field" => "$leg_chamber",
        "$rep_details_district_field" => "$leg_district",
        "$rep_details_in_office_field" => 1,
      ));
      */
    } else {
      $leg_rep_details_update = civicrm_api3('CustomValue', 'create', array(
        'entity_id' => $contact_id,
        'custom_Representative_Details:Level' => 'congress',
        'custom_Representative_Details:States/Provinces' => "$leg_state",
        'custom_Representative_Details:Chamber' => "$leg_chamber",
        'custom_Representative_Details:District' => "$leg_district",
        'custom_Representative_Details:In office?' => 1,
      ));
    }

    //Create the Email address
    //Check if contact has an email addres set, Main location type
    $leg_email_exist = civicrm_api3('Email', 'get', array(
      'return' => "email",
      'contact_id' => $contact_id,
      'is_primary' => 1,
      'location_type_id' => 3,
    ));

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
          'contact_id' => $contact_id,
          'location_type_id' => 3,
          'is_primary' => 1,
          'email' => $leg_email,
        );
      
        $leg_email = civicrm_api3('Email', 'create', $leg_email_params);
      }
    }
  }
  CRM_Core_Error::debug_var("Number of $chamber legislators created", $leg_count);
}

function civicrm_api3_sf_congress_districts($params) {

  electoral_sf_congress_districts();
  return civicrm_api3_create_success(array(1), array("Sunlight Foundation Congress API - Districts successful."));

}

function electoral_sf_congress_districts() {

  $apikey = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'sunlightFoundationAPIKey'));
  $addressLocationType = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'addressLocationType'));
  $states = CRM_Core_PseudoConstant::stateProvinceForCountry(1228, 'abbreviation');

  //geo_code1 = latitude
  //geo_code2 = longitude
  /* FIXME throttling doesn't work
  $rep_details_level_id = civicrm_api3('CustomField', 'getvalue', array(
    'return' => "id",
    'custom_group_id' => "Representative_Details",
    'label' => "Level",
  ));
  $rep_details_level_field = 'custom_' . $rep_details_level_id;
  */
  $contact_addresses = civicrm_api3('Address', 'get', array(
    'sequential' => 1, 
    'return' => "contact_id,geo_code_1,geo_code_2", 
    'geo_code_1' => array('IS NOT NULL' => 1), 
    'geo_code_2' => array('IS NOT NULL' => 1), 
    'country_id' => "US", 
    'location_type_id' => $addressLocationType,
    //'api.Contact.get' => array(
      //'sequential' => 1, 
      //'return' => 'id',
      //'id' => '$value.id',
      //"$rep_details_level_field" => array('!=' => 'congress'),
    //),
  ));

  foreach($contact_addresses['values'] as $address) {

    $latitude = $longitude = $districts = $contact_id = '';
    
    $latitude = $address['geo_code_1'];
    $longitude = $address['geo_code_2'];

    //Assemble the API URL
    $url = "https://congress.api.sunlightfoundation.com/districts/locate?apikey=$apikey&latitude=$latitude&longitude=$longitude";

    //Intitalize curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    //Get results from API and decode the JSON
    $districts = json_decode(curl_exec($ch), TRUE);

    //Close curl
    curl_close($ch);

    if( $districts['count'] == 1 ) {
      $contact_id = $address['contact_id'];
      //$contact_state = array_search($districts['results'][0]['state'], $states);
      $contact_state = $districts['results'][0]['state'];
      $contact_district = $districts['results'][0]['district'];

      //Need to determine if this is a create or an update, 
      //so need to find is there's a value for the custom data
      //Find Level custom field id number
      //FIXME Updates to the multi-value custom data sets aren't currently working
      //We're keeping this check in place to avoid duplicate data
      $contact_rep_details_exists = civicrm_api3('Contact', 'get', array(
        'return' => "id",
        'id' => $contact_id,
        "$rep_details_level_field" => "congress",
      ));

      if ($contact_rep_details_exists['count'] == 1) {
        /*
        $rep_details_states_provinces_id = civicrm_api3('CustomField', 'getvalue', array(
          'return' => "id",
          'custom_group_id' => "Representative_Details",
          'label' => "States/Provinces",
        ));
        $rep_details_states_provinces_field = 'custom_' . $rep_details_states_provinces_id;
        $rep_details_district_id = civicrm_api3('CustomField', 'getvalue', array(
          'return' => "id",
          'custom_group_id' => "Representative_Details",
          'label' => "District",
        ));
        $rep_details_district_field = 'custom_' . $rep_details_district_id;
        $contact_rep_details_update = civicrm_api3('Contact', 'create', array(
          'id' => $contact_id,
          'contact_type' => "Individual",
          "$rep_details_level_field" => "congress",
          "$rep_details_states_provinces_field" => "$contact_state",
          "$rep_details_district_field" => "$contact_district",
        ));
        */
      } else {
        //Create the CiviCRM Contact
        $contact_rep_details_create = civicrm_api3('CustomValue', 'create', array(
          'version' => 3,
          'entity_id' => $contact_id,
          'custom_Representative_Details:Level' => 'congress',
          'custom_Representative_Details:States/Provinces' => "$contact_state",
          'custom_Representative_Details:District' => "$contact_district",
        ));
      }

    }
  }
}

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
function civicrm_api3_sf_open_states_reps($params) {

  $open_states = civicrm_api3('Setting', 'getvalue', array('name' => 'includedOpenStates'));
  foreach ($open_states as $state_id) {
    $state_abbr = CRM_Core_PseudoConstant::stateProvinceAbbreviation($state_id);
    electoral_sf_open_states_reps('upper', "$state_abbr");
    electoral_sf_open_states_reps('lower', "$state_abbr");
  }
  
  return civicrm_api3_create_success(array(1), array("Open States API - Representatives successful."));
}

function electoral_sf_open_states_reps($chamber, $state) {

  $apikey = civicrm_api3('Setting', 'getvalue', array('name' => 'openStatesAPIKey'));

  //Assemble the API URL
  //Unfortunately HTTPS isn't supported currently
  $url = "https://openstates.org/api/v1/legislators/?apikey=$apikey&active=true&per_page=all&chamber=$chamber&state=$state";

  //Intitalize curl
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  //Get results from API and decode the JSON
  $reps = json_decode(curl_exec($ch), TRUE);

  //Close curl
  curl_close($ch);

  $states = CRM_Core_PseudoConstant::stateProvinceForCountry(1228, 'abbreviation');

  $rep_count = 0;
  foreach( $reps as $rep ) {

    $rep_count++;

    //Clear variables before each create
    $rep_id = $civicrm_contact_id = $contact_id = '';
    $rep_contact_params = $rep_contact = '';
    $rep_email_params = $rep_email = $rep_email_exist = $rep_email_exist_id = '';

    //Use the Sunlight Leg ID as the external ID
    //and find if representative exists already.
    $rep_id = $rep['leg_id'];

    $civicrm_contact_id = civicrm_api3('Contact', 'get', array(
      'return' => 'id',
      'external_identifier' => "$rep_id",
    ));

    if ($civicrm_contact_id['count'] == 1 ) {
      $contact_id = $civicrm_contact_id['id'];
    }

    //Create the CiviCRM Contact
    $rep_first_name = $rep['first_name'];
    $rep_last_name = $rep['last_name'];
    $rep_state = array_search(strtoupper($rep['state']), $states);
    $rep_chamber = $rep['chamber'];
    $rep_district = $rep['district'];

    $rep_contact_params = array(
      'external_identifier' => "$rep_id",
      'contact_type' => 'Individual',
      'first_name' => "$rep_first_name",
      'last_name' => "$rep_last_name",
      'do_not_email' => 1,
    );
    //Only add the CiviCRM Contact ID as a create param if set   
    if ($contact_id != '') {
      $rep_contact_params['id'] = $contact_id;
    }

    $rep_contact = civicrm_api3('Contact', 'create', $rep_contact_params);

    //Repeating the Contact ID set in case this is a contact creation
    //and it's not set above.
    $contact_id = $rep_contact['id'];

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
    $rep_rep_details_exists = civicrm_api3('Contact', 'get', array(
      'return' => "id",
      'id' => $contact_id,
      "$rep_details_level_field" => "openstates",
    ));

    if ($rep_rep_details_exists['count'] == 1) {
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
      $rep_rep_details_update = civicrm_api3('Contact', 'create', array(
        'id' => $contact_id,
        'contact_type' => "Individual",
        "$rep_details_level_field" => "congress",
        "$rep_details_states_provinces_field" => "$rep_state",
        "$rep_details_chamber_field" => "$rep_chamber",
        "$rep_details_district_field" => "$rep_district",
        "$rep_details_in_office_field" => 1,
      ));
      */
    } else {
      $rep_rep_details_update = civicrm_api3('CustomValue', 'create', array(
        'entity_id' => $contact_id,
        'custom_Representative_Details:Level' => 'openstates',
        'custom_Representative_Details:States/Provinces' => "$rep_state",
        'custom_Representative_Details:Chamber' => "$rep_chamber",
        'custom_Representative_Details:District' => "$rep_district",
        'custom_Representative_Details:In office?' => 1,
      ));
    }

    //Create the Email address
    if ($rep['email'] != '') {
  
      $rep_email = $rep['email'];
      //Check if contact has an email addres set, Main location type
      $rep_email_exist = civicrm_api3('Email', 'get', array(
        'return' => "email",
        'contact_id' => $contact_id,
        'is_primary' => 1,
        'location_type_id' => 3,
      ));
  
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
          'contact_id' => $contact_id,
          'location_type_id' => 3,
          'is_primary' => 1,
          'email' => $rep_email,
        );
  
        $rep_email = civicrm_api3('Email', 'create', $rep_email_params);
      }
    }
  }
  CRM_Core_Error::debug_var("Number of $state $chamber chamber representatives created", $rep_count);
}

function civicrm_api3_sf_open_states_districts($params) {

  $open_states = civicrm_api3('Setting', 'getvalue', array('name' => 'includedOpenStates'));
  foreach ($open_states as $state_id) {
    if (isset($params['limit']) && is_numeric($params['limit']) ) {
      electoral_sf_open_states_districts($params['limit'], $state_id);
    } else {
      electoral_sf_open_states_districts(100, $state_id);
    }
  }
  return civicrm_api3_create_success(array(1), array("Open States API - Districts successful."));

}

function electoral_sf_open_states_districts($limit, $state_id) {

  $apikey = civicrm_api3('Setting', 'getvalue', array('name' => 'openStatesAPIKey'));

  // The custom group table name and field column names aren't included because
  // coming from the API presumably their sanitized AND
  // Civi quotes the string, so the query returns with a syntax error
  $rep_details_table_name = civicrm_api3('CustomGroup', 'getvalue', array(
    'return' => "table_name",
    'name' => "Representative_Details",
    'label' => "Level",
  ));
  $rep_details_level_column_name = civicrm_api3('CustomField', 'getvalue', array(
    'return' => "column_name",
    'custom_group_id' => "Representative_Details",
    'label' => "Level",
  ));
  $rep_details_chamber_column_name = civicrm_api3('CustomField', 'getvalue', array(
    'return' => "column_name",
    'custom_group_id' => "Representative_Details",
    'label' => "Chamber",
  ));

  // Set params for address lookup
  $addressLocationType = civicrm_api3('Setting', 'getvalue', array('name' => 'addressLocationType'));
  $address_sql_params = array(
    1 => array($addressLocationType, 'Integer'),
    2 => array($limit, 'Integer'),
  );

  //geo_code1 = latitude
  //geo_code2 = longitude
  $address_sql = "
       SELECT ca.geo_code_1,
              ca.geo_code_2,
              ca.contact_id
         FROM civicrm_address ca
   INNER JOIN civicrm_contact cc
           ON ca.contact_id = cc.id
    LEFT JOIN $rep_details_table_name upper
           ON ca.contact_id = upper.entity_id
          AND upper.$rep_details_level_column_name = 'openstates'
          AND upper.$rep_details_chamber_column_name = 'upper'
    LEFT JOIN $rep_details_table_name lower
           ON ca.contact_id = lower.entity_id
          AND lower.$rep_details_level_column_name = 'openstates'
          AND lower.$rep_details_chamber_column_name = 'lower'
        WHERE ca.geo_code_1 IS NOT NULL
          AND ca.geo_code_2 IS NOT NULL
          AND ca.street_address IS NOT NULL
          AND ca.country_id = 1228
          AND ca.state_province_id = $state_id
          AND cc.is_deceased != 1
  ";
  //TODO Including these WHERE clauses will only check contacts without existing OpenStates Rep Details
  //Updating them is a bit more complicated with the current state of the API. See below.
  $address_sql .= "
          AND (upper.id IS NULL OR lower.id IS NULL)
  ";
  //Handle a location type of Primary.
  if ($addressLocationType == 0) {
    $address_sql .= "
         AND ca.is_primary = 1
    ";
  } else {
    $address_sql .= "
          AND ca.location_type_id = %1
    ";
  }
  //Throttling
  $address_sql .= "
        LIMIT %2
  ";

  $contact_addresses = CRM_Core_DAO::executeQuery($address_sql, $address_sql_params);

  while ($contact_addresses->fetch()) {

    $latitude = $longitude = $districts = $contact_id = '';
    
    $latitude = $contact_addresses->geo_code_1;
    $longitude = $contact_addresses->geo_code_2;

    //Assemble the API URL
    $url = "https://openstates.org/api/v1/legislators/geo/?apikey=$apikey&lat=$latitude&long=$longitude";

    //Intitalize curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    //Get results from API and decode the JSON
    $districts = json_decode(curl_exec($ch), TRUE);

    //Close curl
    curl_close($ch);

    foreach ( $districts as $district ) {
      $contact_id = $contact_addresses->contact_id;
      $contact_district = $district['district'];
      $contact_chamber = $district['chamber'];

      //Need to determine if this is a create or an update, 
      //so need to find is there's a value for the custom data
      //Find Level custom field id number
      //TODO Updates to the multi-value custom data sets aren't currently working
      //We're keeping this check in place to avoid duplicate data
      $rep_details_level_id = civicrm_api3('CustomField', 'getvalue', array(
        'return' => "id",
        'custom_group_id' => "Representative_Details",
        'label' => "Level",
      ));
      $rep_details_level_field = 'custom_' . $rep_details_level_id;
      $rep_details_chamber_id = civicrm_api3('CustomField', 'getvalue', array(
        'return' => "id",
        'custom_group_id' => "Representative_Details",
        'label' => "Chamber",
      ));
      $rep_details_chamber_field = 'custom_' . $rep_details_chamber_id;
      $contact_rep_details_exists = civicrm_api3('Contact', 'get', array(
        'return' => "id",
        'id' => $contact_id,
        "$rep_details_chamber_field" => "$contact_chamber", 
        "$rep_details_level_field" => "openstates",
      ));

      if ($contact_rep_details_exists['count'] == 1) {
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
        $rep_rep_details_update = civicrm_api3('Contact', 'create', array(
          'id' => $contact_id,
          'contact_type' => "Individual",
          "$rep_details_level_field" => "congress",
          "$rep_details_states_provinces_field" => "$state_id",
          "$rep_details_chamber_field" => "$contact_chamber",
          "$rep_details_district_field" => "$contact_district",
        ));
        */
      } else {
        $contact_rep_details_update = civicrm_api3('CustomValue', 'create', array(
          'entity_id' => $contact_id,
          'custom_Representative_Details:Level' => 'openstates',
          'custom_Representative_Details:States/Provinces' => "$state_id",
          'custom_Representative_Details:Chamber' => "$contact_chamber",
          'custom_Representative_Details:District' => "$contact_district",
        ));
      }

    }
  }
}

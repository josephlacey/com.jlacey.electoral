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
  $apikey = civicrm_api3('Setting', 'getvalue', array('name' => 'sunlightFoundationAPIKey'));

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
    $leg_title = $legislator['title'] . '.';
    $prefix_exist = civicrm_api3('OptionValue', 'get', array('label' => "$leg_title", ));
    if ($prefix_exist['count'] == 0) {
      $prefix = civicrm_api3('OptionValue', 'create', array('option_group_id' => 'individual_prefix', 'label' => "$leg_title", ));
    }
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
      'prefix_id' => "$leg_title",
      'first_name' => "$leg_first_name",
      'last_name' => "$leg_last_name",
      'do_not_email' => 1,
      'image_URL' => "https://theunitedstates.io/images/congress/225x275/$leg_id.jpg",
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
    //Check if contact has an email address set, Main location type
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
          'email' => "$leg_email",
        );

        $leg_email = civicrm_api3('Email', 'create', $leg_email_params);
      }
    }

    //Create the Phone number
    //Check if contact has a phone set, Main location type
    $leg_phone_exist = civicrm_api3('Phone', 'get', array(
      'return' => "phone",
      'contact_id' => $contact_id,
      'is_primary' => 1,
      'location_type_id' => 3,
    ));

    //If there is an existing phone number, set the id for comparison
    if ($leg_phone_exist['count'] > 0) {
      $leg_phone_exist_id = $leg_phone_exist['id'];
    }
    if ( $legislator['phone'] != NULL ) {
      $leg_phone = $legislator['phone'];

      //Add an updated phone number or a new one if none exist,
      //and set it to primary
      if ( ( $leg_phone_exist['count'] == 1 && $leg_phone_exist['values'][$leg_phone_exist_id]['phone'] != strtolower($leg_phone ) ) ||
           $leg_phone_exist['count'] == 0 ) {
        $leg_phone_params = array(
          'contact_id' => $contact_id,
          'location_type_id' => 3,
          'phone_type_id' => 1,
          'is_primary' => 1,
          'phone' => "$leg_phone",
        );

        $leg_phone = civicrm_api3('Phone', 'create', $leg_phone_params);
      }
    }

    //Create the Address address
    //Check if contact has an address set, Main location type
    $leg_address_exist = civicrm_api3('Address', 'get', array(
      'return' => "street_address",
      'contact_id' => $contact_id,
      'is_primary' => 1,
    ));

    //If there is an existing address address, set the id for comparison
    if ($leg_address_exist['count'] > 0) {
      $leg_address_exist_id = $leg_address_exist['id'];
    }
    if ( $legislator['office'] != NULL ) {
      $leg_address = $legislator['office'];

      //Add an updated address address or a new one if none exist,
      //and set it to primary
      if ( ( $leg_address_exist['count'] == 1 && $leg_address_exist['values'][$leg_address_exist_id]['street_address'] != $leg_address ) ||
           $leg_address_exist['count'] == 0 ) {
        $leg_address_params = array(
          'contact_id' => $contact_id,
          'location_type_id' => 3,
          'is_primary' => 1,
          'street_address' => "$leg_address",
          'city' => 'Washington',
          'state_province_id' => 1050,
          'postal_code' => '20510',
        );

        $leg_address = civicrm_api3('Address', 'create', $leg_address_params);
      }
    }

    //Create website
    //Check if contact has a phone set, Main location type
    $leg_website_exist = civicrm_api3('Website', 'get', array(
      'return' => "url",
      'contact_id' => $contact_id,
      'website_type_id' => 2
    ));

    //If there is an existing website, set the id for comparison
    if ($leg_website_exist['count'] > 0) {
      $leg_website_exist_id = $leg_website_exist['id'];
    }
    if ( $legislator['website'] != NULL ) {
      $leg_website = $legislator['website'];

      //Add an updated website or a new one if none exist,
      //and set it to primary
      if ( ( $leg_website_exist['count'] == 1 && $leg_website_exist['values'][$leg_website_exist_id]['url'] != $leg_website ) ||
           $leg_website_exist['count'] == 0 ) {
        $leg_website_params = array(
          'contact_id' => $contact_id,
          'url' => "$leg_website",
          'website_type_id' => 2
        );

        $leg_website = civicrm_api3('Website', 'create', $leg_website_params);
      }
    }

    //Create Facebook
    //Check if contact has a phone set, Main location type
    $leg_facebook_exist = civicrm_api3('Website', 'get', array(
      'return' => "url",
      'contact_id' => $contact_id,
      'website_type_id' => 3
    ));

    //If there is an existing facebook, set the id for comparison
    if ($leg_facebook_exist['count'] > 0) {
      $leg_facebook_exist_id = $leg_facebook_exist['id'];
    }
    if ( $legislator['facebook_id'] != NULL ) {
      $leg_facebook = 'https://facebook.com/' . $legislator['facebook_id'];

      //Add an updated facebook or a new one if none exist,
      //and set it to primary
      if ( ( $leg_facebook_exist['count'] == 1 && $leg_facebook_exist['values'][$leg_facebook_exist_id]['url'] != $leg_facebook) ||
           $leg_facebook_exist['count'] == 0 ) {
        $leg_facebook_params = array(
          'contact_id' => $contact_id,
          'url' => "$leg_facebook",
          'website_type_id' => 3
        );

        $leg_facebook = civicrm_api3('Website', 'create', $leg_facebook_params);
      }
    }

    //Create Twitter
    //Check if contact has a phone set, Main location type
    $leg_twitter_exist = civicrm_api3('Website', 'get', array(
      'return' => "url",
      'contact_id' => $contact_id,
      'website_type_id' => 11
    ));

    //If there is an existing twitter, set the id for comparison
    if ($leg_twitter_exist['count'] > 0) {
      $leg_twitter_exist_id = $leg_twitter_exist['id'];
    }
    if ( $legislator['twitter_id'] != NULL ) {
      $leg_twitter = 'https://twitter.com/' . $legislator['twitter_id'];

      //Add an updated twitter or a new one if none exist,
      //and set it to primary
      if ( ( $leg_twitter_exist['count'] == 1 && $leg_twitter_exist['values'][$leg_twitter_exist_id]['url'] != $leg_twitter) ||
           $leg_twitter_exist['count'] == 0 ) {
        $leg_twitter_params = array(
          'contact_id' => $contact_id,
          'url' => "$leg_twitter",
          'website_type_id' => 11
        );

        $leg_twitter = civicrm_api3('Website', 'create', $leg_twitter_params);
      }
    }
  }
  CRM_Core_Error::debug_var("Number of $chamber legislators created", $leg_count);
}

function civicrm_api3_sf_congress_districts($params) {

  if (isset($params['limit']) && is_numeric($params['limit']) ) {
    electoral_sf_congress_districts($params['limit']);
  } else {
    electoral_sf_congress_districts(100);
  }
  return civicrm_api3_create_success(array(1), array("Sunlight Foundation Congress API - Districts successful."));

}

function electoral_sf_congress_districts($limit) {

  $apikey = civicrm_api3('Setting', 'getvalue', array('name' => 'sunlightFoundationAPIKey'));
  $states = CRM_Core_PseudoConstant::stateProvinceForCountry(1228, 'abbreviation');

  // The custom group table name and field column name aren't included because
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

  // Set params for address lookup
  $addressLocationType = civicrm_api3('Setting', 'getvalue', array('name' => 'addressLocationType'));
  $address_sql_params = array(
    1 => array($addressLocationType, 'Integer'),
    2 => array($limit, 'Integer'),
  );

  //geo_code1 = latitude
  //geo_code2 = longitude
  // Assemble address lookup query
  $address_sql = "
       SELECT ca.geo_code_1,
              ca.geo_code_2,
              ca.contact_id
         FROM civicrm_address ca
   INNER JOIN civicrm_contact cc
           ON ca.contact_id = cc.id
    LEFT JOIN $rep_details_table_name congress
           ON ca.contact_id = congress.entity_id
          AND congress.$rep_details_level_column_name = 'congress'
        WHERE ca.geo_code_1 IS NOT NULL
          AND ca.geo_code_2 IS NOT NULL
          AND ca.country_id = 1228
          AND cc.is_deceased != 1
  ";
  //TODO Including these WHERE clauses will only check contacts without an existing Rep Details
  //Updating them is a bit more complicated. See below.
  $address_sql .= "
          AND congress.id IS NULL
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
      $contact_id = $contact_addresses->contact_id;
      $contact_state = array_search($districts['results'][0]['state'], $states);
      $contact_district = $districts['results'][0]['district'];

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
      $contact_rep_details_exists = civicrm_api3('Contact', 'get', array(
        'return' => "id",
        'id' => $contact_id,
        "custom_$rep_details_level_id" => "congress",
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
          'entity_id' => $contact_id,
          'custom_Representative_Details:Level' => 'congress',
          'custom_Representative_Details:States/Provinces' => "$contact_state",
          'custom_Representative_Details:District' => "$contact_district",
        ));
      }
    }
  }
}

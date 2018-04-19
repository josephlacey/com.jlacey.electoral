<?php 

/**
 * NY Times API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */ 
function civicrm_api3_google_civic_information_districts($params) {

  if (isset($params['limit']) && is_numeric($params['limit']) ) {
    google_civic_information_districts($params['limit']);
  } else {
    google_civic_information_districts(100);
  }
  return civicrm_api3_create_success(array(1), array("Google Civic Informatin API successful."));

}

function google_civic_information_districts($limit) {

  $apikey = civicrm_api3('Setting', 'getvalue', array('name' => 'googleCivicInformationAPIKey'));

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

  // Assemble address lookup query
  $address_sql = "
       SELECT ca.street_address,
              ca.city,
              ca.state
              ca.contact_id
         FROM civicrm_address ca
   INNER JOIN civicrm_contact cc
           ON ca.contact_id = cc.id
    LEFT JOIN $rep_details_table_name civicinfo
           ON ca.contact_id = civicinfo.entity_id
          AND civicinfo.$rep_details_level_column_name = 'congress'
        WHERE ca.street_address IS NOT NULL
          AND ca.city IS NOT NULL
          AND ca.state IS NOT NULL
          AND ca.country_id = 1228
          AND cc.is_deceased != 1
  ";
  //TODO Including these WHERE clauses will only check contacts without an existing Rep Details
  //Updating them is a bit more complicated. See below.
  $address_sql .= "
          AND civicinfo.id IS NULL
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

    $street_address = $city = $state = $postal_code = $districts = $contact_id = '';
    
    $street_addres = rawurlencode($contact_address->street_address);
    $city = rawurlencode($contact_address->city);
    $state = $contact_address->state;

    //Assemble the API URL
    //$url = "https://api.opencivicdata.org/divisions/?apikey=$apikey&";
    $url = "https://www.googleapis.com/civicinfo/v2/representatives?key=$apikey&address=$street_address%20$city%20$state";
    CRM_Core_Error::debug_var('url', $url);

    //Intitalize curl
    $verifySSL = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'verifySSL'));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);

    //Get results from API and decode the JSON
    $districts = json_decode(curl_exec($ch), TRUE);

    //Close curl
    curl_close($ch);

    CRM_Core_Error::debug_var('districts', $districts);
    if( $districts['status'] == 'OK' ) {
      $contact_id = $contact_addresses->contact_id;
      foreach ($districts['results'] as $district) {
        if ($district['level'] == 'City Council') {
          $city_council_district = $district['district'];
          
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
          $contact_rep_details_exists = civicrm_api3('Contact', 'get', array(
            'return' => "id",
            'id' => $contact_id,
            "$rep_details_level_field" => "city",
          ));

          if ($contact_rep_details_exists['count'] == 1) {
            /*
            $rep_details_district_id = civicrm_api3('CustomField', 'getvalue', array(
              'return' => "id",
              'custom_group_id' => "Representative_Details",
              'label' => "District",
            ));
            $rep_details_district_field = 'custom_' . $rep_details_district_id;
            $contact_rep_details_update = civicrm_api3('Contact', 'create', array(
              'id' => $contact_id,
              'contact_type' => "Individual",
              "$rep_details_level_field" => "city",
              "$rep_details_district_field" => "$city_council_district",
            ));
            */
          } else {
            $contact_rep_details_update = civicrm_api3('CustomValue', 'create', array(
              'entity_id' => $contact_id,
              'custom_Representative_Details:Level' => 'city',
              'custom_Representative_Details:District' => "$city_council_district",
            ));
          }
        }
      }
    }
  }
}

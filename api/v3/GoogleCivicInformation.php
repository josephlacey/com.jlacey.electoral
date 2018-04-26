<?php 

/**
 * Google Civic Information API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */ 
function civicrm_api3_google_civic_information_country_districts($params, $level) {

  if (isset($params['limit']) && is_numeric($params['limit']) ) {
    $result = google_civic_information_districts($params['limit'], $level);
  } else {
    $result = google_civic_information_districts(100, $level);
  }

  if (isset($result['error'])) {
    $reason = $result['error']['errors'][0]['reason'];
    $code = $result['error']['code'];
    $message = $result['error']['message'];
    return civicrm_api3_create_error("$message ($code): $reason");
  } else {
    return civicrm_api3_create_success("$result");
  }

}

function google_civic_information_districts($limit, $level) {

  $addresses_districted = 0;
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
              ca.state_province_id,
              ca.contact_id
         FROM civicrm_address ca
   INNER JOIN civicrm_contact cc
           ON ca.contact_id = cc.id
    LEFT JOIN $rep_details_table_name civicinfo
           ON ca.contact_id = civicinfo.entity_id
          AND civicinfo.$rep_details_level_column_name = 'congress'
        WHERE ca.street_address IS NOT NULL
          AND ca.city IS NOT NULL
          AND ca.state_province_id IS NOT NULL
          AND ca.country_id = 1228
          AND cc.is_deceased != 1
          AND cc.is_deleted != 1
  ";
  //TODO Include a WHERE clause to only check recently updated addresses.
  //$address_sql .= "
  //        AND civicinfo.id IS NULL
  //";
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
    
    $street_address = rawurlencode($contact_addresses->street_address);
    $city = rawurlencode($contact_addresses->city);
    $state = CRM_Core_PseudoConstant::stateProvinceAbbreviation($contact_addresses->state_province_id);

    //Assemble the API URL
    //$url = "https://api.opencivicdata.org/divisions/?apikey=$apikey&";
    $url = "https://www.googleapis.com/civicinfo/v2/representatives?key=$apikey&address=$street_address%20$city%20$state";
    //CRM_Core_Error::debug_var('url', $url);

    //Intitalize curl
    $verifySSL = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'verifySSL'));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);

    //Get results from API and decode the JSON
    $districts = json_decode(curl_exec($ch), TRUE);
    dsm($districts, 'districts');

    //Close curl
    curl_close($ch);

    if ( isset($districts['error']) ) {
      return $districts;
    } else {
      $contact_id = $contact_addresses->contact_id;
      foreach ($districts['divisions'] as $ocdId => $name) {
        $ocdLevels = explode('/', substr($ocdId, 13));
        $levels = array();
        foreach( $ocdLevels as $ocdLevel ) {
          $levels[strstr($ocdLevel, ':', TRUE)] = substr(strstr($ocdLevel, ':'), 1);
        }
        if ( isset($levels['cd']) ) {
          $cd = $levels['cd'];
          //Check if this level exists already
          $contact_rep_details_exists = rep_details_exists($contact_id, 'congress');
          if ($contact_rep_details_exists['count'] == 1) {
            //Get the custom value set id
            $rep_details_table_name_id = rep_details_table_name_id();
            $rep_details_id = $contact_rep_details_exists['values'][$contact_id][$rep_details_table_name_id];
            //Update
            $contact_rep_details_update = civicrm_api3('CustomValue', 'create', array(
              'entity_id' => $contact_id,
              "custom_Representative_Details:Level:$rep_details_id" => "congress",
              "custom_Representative_Details:States/Provinces:$rep_details_id" => "$contact_addresses->state_province_id",
              "custom_Representative_Details:District:$rep_details_id" => "$cd",
            ));
          } else {
            //Create
            $contact_rep_details_create = civicrm_api3('CustomValue', 'create', array(
              'entity_id' => $contact_id,
              'custom_Representative_Details:Level' => 'congress',
              'custom_Representative_Details:States/Provinces' => "$contact_addresses->state_province_id",
              'custom_Representative_Details:District' => "$cd",
            ));
          }
        }
      }
    }
    $addresses_districted++;
  }
  return "$addresses_districted addresses districted.";
}

function rep_details_exists($contact_id, $level) {
  $rep_details_level_id = civicrm_api3('CustomField', 'getvalue', array(
    'return' => "id",
    'custom_group_id' => "Representative_Details",
    'name' => "electoral_level",
  ));
  $rep_details_level_field = 'custom_' . $rep_details_level_id;
  $rep_details_exists = civicrm_api3('Contact', 'get', array(
    'return' => "id",
    'id' => $contact_id,
    "$rep_details_level_field" => "$level",
  ));

  return $rep_details_exists;
}

function rep_details_table_name_id() {
  $rep_details_table_name = civicrm_api3('CustomGroup', 'getvalue', array(
    'return' => "table_name",
    'name' => "Representative_Details",
  ));
  return $rep_details_table_name . "_id";
}

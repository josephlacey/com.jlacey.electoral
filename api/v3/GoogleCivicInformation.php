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
function civicrm_api3_google_civic_information_districts($params) {

  if (isset($params['limit']) && is_numeric($params['limit']) ) {
    $result = google_civic_information_districts($params['level'], $params['limit']);
  } else {
    $result = google_civic_information_districts($params['level'], 100);
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

function google_civic_information_districts($level, $limit) {

  $addresses_districted = 0;
  $statesProvinces = $counties = $cities = array();

  $apikey = civicrm_api3('Setting', 'getvalue', array('name' => 'googleCivicInformationAPIKey'));
  $addressLocationType = civicrm_api3('Setting', 'getvalue', array('name' => 'addressLocationType'));
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', array('name' => 'includedStatesProvinces'));
  foreach( $includedStatesProvinces as $state_province_id) {
    $statesProvinces[$state_province_id] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($state_province_id));
  }
  $includedCounties = civicrm_api3('Setting', 'getvalue', array('name' => 'includedCounties'));
  foreach( $includedCounties as $county_id) {
    $counties[$county_id] = strtolower(CRM_Core_PseudoConstant::county($county_id));
  }
  $includedCities = explode(',', civicrm_api3('Setting', 'getvalue', array('name' => 'includedCities')));
  foreach( $includedCities as $city) {
    $cities[] = strtolower($city);
  }

  $address_states_provinces = implode(', ', $includedStatesProvinces);
  // Set params for address lookup
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
        WHERE ca.street_address IS NOT NULL
          AND ca.city IS NOT NULL
          AND ca.state_province_id IN ($address_states_provinces)
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
  //CRM_Core_Error::debug_var('address_sql', $address_sql);
  //CRM_Core_Error::debug_var('address_sql_params', $address_sql_params);

  $contact_addresses = CRM_Core_DAO::executeQuery($address_sql, $address_sql_params);

  while ($contact_addresses->fetch()) {

    $street_address = $city = $state = $postal_code = $districts = $contact_id = '';
    
    $street_address = rawurlencode($contact_addresses->street_address);
    $city = rawurlencode($contact_addresses->city);
    $state = CRM_Core_PseudoConstant::stateProvinceAbbreviation($contact_addresses->state_province_id);

    //Assemble the API URL
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

    //Close curl
    curl_close($ch);

    if ( isset($districts['error']) ) {
      return $districts;
    } else {
      foreach ($districts['divisions'] as $ocdId => $name) {
        $ocdDivisions = explode('/', substr($ocdId, 13));
        foreach( $ocdDivisions as $ocdDivision ) {
          $ocdInfo[strstr($ocdDivision, ':', TRUE)] = substr(strstr($ocdDivision, ':'), 1);
          $ocdValue = '';
          foreach($ocdInfo as $ocdKey => $ocdValue) {
            switch ($ocdKey) {
              case 'county':
                if (in_array($ocdValue, $counties)) {
                  $county = ucwords($ocdValue);
                }
                break;
              case 'place':
                if (in_array($ocdValue, $cities)) {
                  $city = ucwords($ocdValue);
                }
                break;
              case 'cd':
                if ($level == 'country') {
                  rep_details_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, NULL, NULL, NULL, $ocdValue);
                }
                break;
              case 'sldu':
                if ($level == 'administrativeArea1') {
                  rep_details_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, NULL, NULL, 'upper', $ocdValue);
                }
                break;
              case 'sldl':
                if ($level == 'administrativeArea1') {
                  rep_details_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, NULL, NULL, 'lower', $ocdValue);
                }
                break;
              case 'council_district':
                if ($level == 'administrativeArea2' && !empty($county)) {
                  rep_details_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, $county, NULL, NULL, $ocdValue);
                }
                if ($level == 'locality' && !empty($city)) {
                  rep_details_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, NULL, $city, NULL, $ocdValue);
                }
                break;
              default:
                continue;
            }
            $ocdValue = '';
          }
          $ocdInfo = array();
        }
        $county = $city = '';
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

function rep_details_create_update($contact_id, $level, $state_province_id = NULL, $county_id = NULL, $city = NULL, $chamber = NULL, $district = NULL) {
  //Check if this level exists already
  $contact_rep_details_exists = rep_details_exists($contact_id, "$level");
  if ($contact_rep_details_exists['count'] == 1) {
    //Get the custom value set id
    $rep_details_table_name_id = rep_details_table_name_id();
    $rep_details_id = $contact_rep_details_exists['values'][$contact_id][$rep_details_table_name_id];
    //Update
    $contact_rep_details_update = civicrm_api3('CustomValue', 'create', array(
      'entity_id' => $contact_id,
      "custom_Representative_Details:Level:$rep_details_id" => "$level",
      "custom_Representative_Details:States/Provinces:$rep_details_id" => "$state_province_id",
      "custom_Representative_Details:County:$rep_details_id" => "$county_id",
      "custom_Representative_Details:City:$rep_details_id" => "$city",
      "custom_Representative_Details:Chamber:$rep_details_id" => "$chamber",
      "custom_Representative_Details:District:$rep_details_id" => "$district",
    ));
  } else {
    //Create
    $contact_rep_details_create = civicrm_api3('CustomValue', 'create', array(
      'entity_id' => $contact_id,
      'custom_Representative_Details:Level' => "$level",
      'custom_Representative_Details:States/Provinces' => "$state_province_id",
      "custom_Representative_Details:County" => "$county_id",
      "custom_Representative_Details:City" => "$city",
      'custom_Representative_Details:Chamber' => "$chamber",
      'custom_Representative_Details:District' => "$district",
    ));
  }
}

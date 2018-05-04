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

  $limit = 100;
  $update = 0;
  if (isset($params['limit']) && is_numeric($params['limit']) ) {
    $limit = $params['limit'];
  }
  if (isset($params['update']) && is_numeric($params['update']) ) {
    $update = $params['update'];
  }

  $result = google_civic_information_districts($params['level'], $limit, $update);

  if (isset($result['error'])) {
    $reason = $result['error']['errors'][0]['reason'];
    $code = $result['error']['code'];
    $message = $result['error']['message'];
    return civicrm_api3_create_error("$message ($code): $reason");
  } else {
    return civicrm_api3_create_success("$result");
  }

}

function google_civic_information_districts($level, $limit, $update) {

  $addresses_districted = $parsing_errors = 0;
  $statesProvinces = $counties = $cities =  array();
  $contacts_with_address_parsing_errors = '';

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

  $ed_table_name = civicrm_api3('CustomGroup', 'getvalue', array(
    'return' => "table_name",
    'name' => "electoral_districts",
  ));

  // Assemble address lookup query
  $address_sql = "
       SELECT ca.street_address,
              ca.city,
              ca.state_province_id,
              ca.contact_id
         FROM civicrm_address ca
    LEFT JOIN $ed_table_name ed
           ON ca.contact_id = ed.entity_id
          AND ed.electoral_districts_level = '$level'
   INNER JOIN civicrm_contact cc
           ON ca.contact_id = cc.id
        WHERE ca.street_address IS NOT NULL
          AND ca.street_address NOT LIKE '%PO Box%'
          AND ca.city IS NOT NULL
          AND ca.state_province_id IN ($address_states_provinces)
          AND ca.country_id = 1228
          AND cc.is_deceased != 1
          AND cc.is_deleted != 1
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

  //FIXME there's probably a better way to do this
  if (!$update) {
    $address_sql .= "
          AND ed.id IS NULL
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
      //CRM_Core_Error::debug_var('url', $url);
      //CRM_Core_Error::debug_var('districts', $districts);
      if ($districts['error']['errors'][0]['reason'] == 'parseError') {
        $parsing_errors++;
        if ($contacts_with_address_parsing_errors == '') {
          $contacts_with_address_parsing_errors = "$contact_addresses->contact_id";
        } else {
          $contacts_with_address_parsing_errors .= ", $contact_addresses->contact_id";
        }
        continue;
      }
      return $districts;
    } else {
      foreach ($districts['divisions'] as $ocdId => $name) {
        $ocdDivisions = explode('/', substr($ocdId, 13));
        foreach( $ocdDivisions as $ocdDivision ) {
          $ocdInfo[strstr($ocdDivision, ':', TRUE)] = substr(strstr($ocdDivision, ':'), 1);
          $ocdValue = '';
          foreach($ocdInfo as $ocdKey => $ocdValue) {
            //CRM_Core_Error::debug_var("$ocdKey", $ocdValue);
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
                  ed_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, NULL, NULL, NULL, $ocdValue);
                }
                break;
              case 'sldu':
                if ($level == 'administrativeArea1') {
                  ed_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, NULL, NULL, 'upper', $ocdValue);
                }
                break;
              case 'sldl':
                if ($level == 'administrativeArea1') {
                  ed_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, NULL, NULL, 'lower', $ocdValue);
                }
                break;
              case 'council_district':
                if ($level == 'administrativeArea2' && !empty($county)) {
                  ed_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, $county, NULL, NULL, $ocdValue);
                }
                if ($level == 'locality' && !empty($city)) {
                  ed_create_update($contact_addresses->contact_id, $level, $contact_addresses->state_province_id, NULL, $city, NULL, $ocdValue);
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

  $ed_return = "$addresses_districted addresses districted.";
  if ($parsing_errors > 0) {
    $ed_return .= " $parsing_errors addresses with parsing errors: contact ids ($contacts_with_address_parsing_errors).";
  }
  return $ed_return;
}

function ed_create_update($contact_id, $level, $state_province_id = NULL, $county_id = NULL, $city = NULL, $chamber = NULL, $district = NULL) {
  //Check if this level exists already
  $contact_ed_exists = ed_exists($contact_id, "$level", "$chamber");
  if ($contact_ed_exists['count'] == 1) {
    //Get the custom value set id
    $ed_table_name_id = ed_table_name_id();
    $ed_id = $contact_ed_exists['values'][$contact_id][$ed_table_name_id];
    //Update
    $contact_ed_update = civicrm_api3('CustomValue', 'create', array(
      'entity_id' => $contact_id,
      "custom_electoral_districts:Level:$ed_id" => "$level",
      "custom_electoral_districts:States/Provinces:$ed_id" => "$state_province_id",
      "custom_electoral_districts:County:$ed_id" => "$county_id",
      "custom_electoral_districts:City:$ed_id" => "$city",
      "custom_electoral_districts:Chamber:$ed_id" => "$chamber",
      "custom_electoral_districts:District:$ed_id" => "$district",
    ));
  } else {
    //Create
    $contact_ed_create = civicrm_api3('CustomValue', 'create', array(
      'entity_id' => $contact_id,
      'custom_electoral_districts:Level' => "$level",
      'custom_electoral_districts:States/Provinces' => "$state_province_id",
      "custom_electoral_districts:County" => "$county_id",
      "custom_electoral_districts:City" => "$city",
      'custom_electoral_districts:Chamber' => "$chamber",
      'custom_electoral_districts:District' => "$district",
    ));
  }
}

function ed_exists($contact_id, $level, $chamber = NULL) {
  $ed_exists_params = array(
    'return' => "id",
    'id' => $contact_id,
  );
  $ed_level_id = civicrm_api3('CustomField', 'getvalue', array(
    'return' => "id",
    'custom_group_id' => "electoral_districts",
    'name' => "electoral_level",
  ));
  $ed_level_field = 'custom_' . $ed_level_id;
  $ed_exists_params[$ed_level_field] = "$level";
  if (!empty($chamber)) {
    $ed_chamber_id = civicrm_api3('CustomField', 'getvalue', array(
      'return' => "id",
      'custom_group_id' => "electoral_districts",
      'name' => "electoral_chamber",
    ));
    $ed_chamber_field = 'custom_' . $ed_chamber_id;
    $ed_exists_params[$ed_chamber_field] = "$chamber";
  }
  $ed_exists = civicrm_api3('Contact', 'get', $ed_exists_params);

  return $ed_exists;
}

function ed_table_name_id() {
  $ed_table_name = civicrm_api3('CustomGroup', 'getvalue', array(
    'return' => "table_name",
    'name' => "electoral_districts",
  ));
  return $ed_table_name . "_id";
}

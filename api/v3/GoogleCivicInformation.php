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

  switch ($params['level']) {
    case 'country':
      $result = google_civic_information_country_districts($params['level'], $limit, $update);
      break;
    case 'administrativeArea1':
      $result = google_civic_information_state_districts($params['level'], $limit, $update);
      break;
    case 'administrativeArea2':
      $result = google_civic_information_county_districts($params['level'], $limit, $update);
      break;
    case 'locality':
      $result = google_civic_information_city_districts($params['level'], $limit, $update);
      break;
  }
  return civicrm_api3_create_success("$result");

}

/*
 * Function to create country level districts
 */
function google_civic_information_country_districts($level, $limit, $update) {

  //Set variables
  $addressesDistricted = $addressesWithErrors = 0;

  //API Key
  $apikey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

  //States
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', ['name' => 'includedStatesProvinces']);
  foreach( $includedStatesProvinces as $stateProvinceId) {
    $statesProvinces[$stateProvinceId] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId));
  }

  $contactAddresses = electoral_district_addresses($limit, $level, $includedStatesProvinces, $update);

  while ($contactAddresses->fetch()) {

    $streetAddress = $city = $state = $districts = '';
    
    //Assemble the API URL
    $streetAddress = rawurlencode($contactAddresses->street_address);
    $city = rawurlencode($contactAddresses->city);
    $stateProvinceAbbrev = CRM_Core_PseudoConstant::stateProvinceAbbreviation($contactAddresses->state_province_id);
    $url = "https://www.googleapis.com/civicinfo/v2/representatives?levels=$level&roles=legislatorUpperBody&roles=legislatorLowerBody&key=$apikey&address=$streetAddress%20$city%20$stateProvinceAbbrev";

    $districts = electoral_curl($url);

    //Process the response
    //Check for errors first
    if ( isset($districts['error']) ) {
      $addressesWithErrors++;
      electoral_district_address_errors($districts, $contactAddresses->id);
    //Process divisions
    } else {
      $countryDivision = strtolower("ocd-division/country:us/state:$stateProvinceAbbrev");
      foreach($districts['divisions'] as $divisionKey => $division) {
        //Check if there's a district
        $divisionDistrict = ''; 
        if($countryDivision != $divisionKey) {
          $divisionParts = explode(':', str_replace($countryDivision, '', $divisionKey));
          $divisionDistrict = $divisionParts[1];
        }
        electoral_district_create_update($contactAddresses->contact_id, $level, $contactAddresses->state_province_id, NULL, NULL, NULL, $divisionDistrict);
      }
      $addressesDistricted++;
    }
  }

  $edDistrictReturn = "$addressesDistricted addresses districted.";
  if ($addressesWithErrors > 0) {
    $edDistrictReturn .= " $addressesWithErrors addresses with errors.";
  }
  return $edDistrictReturn;
}

/*
 * Function to create state level districts
 */
function google_civic_information_state_districts($level, $limit, $update) {

  //Set variables
  $addressesDistricted = $addressesWithErrors = 0;

  //API Key
  $apikey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

  //States
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', ['name' => 'includedStatesProvinces']);
  foreach( $includedStatesProvinces as $stateProvinceId) {
    $statesProvinces[$stateProvinceId] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId));
  }

  $contactAddresses = electoral_district_addresses($limit, $level, $includedStatesProvinces, $update);

  while ($contactAddresses->fetch()) {

    $streetAddress = $city = $state = $districts = '';
    
    //Assemble the API URL
    $streetAddress = rawurlencode($contactAddresses->street_address);
    $city = rawurlencode($contactAddresses->city);
    $stateProvinceAbbrev = CRM_Core_PseudoConstant::stateProvinceAbbreviation($contactAddresses->state_province_id);
    $url = "https://www.googleapis.com/civicinfo/v2/representatives?levels=$level&roles=legislatorUpperBody&roles=legislatorLowerBody&key=$apikey&address=$streetAddress%20$city%20$stateProvinceAbbrev";

    $districts = electoral_curl($url);

    //Process the response
    //Check for errors first
    if ( isset($districts['error']) ) {
      $addressesWithErrors++;
      electoral_district_address_errors($districts, $contactAddresses->id);
    //Process divisions
    } else {
      $countryDivision = strtolower("ocd-division/country:us/state:$stateProvinceAbbrev");
      foreach($districts['divisions'] as $divisionKey => $division) {
        //Check if there's a district
        $divisionDistrict = ''; 
        if($countryDivision != $divisionKey) {
          $divisionParts = explode(':', str_replace($countryDivision . '/', '', $divisionKey));
          if ($divisionParts[0] == 'sldu') {
            $chamber = 'upper';
          }
          if ($divisionParts[0] == 'sldl') {
            $chamber = 'lower';
          }
          $divisionDistrict = $divisionParts[1];
        }
        electoral_district_create_update($contactAddresses->contact_id, $level, $contactAddresses->state_province_id, NULL, NULL, $chamber, $divisionDistrict);
      }
      $addressesDistricted++;
    }
  }

  $edDistrictReturn = "$addressesDistricted addresses districted.";
  if ($addressesWithErrors > 0) {
    $edDistrictReturn .= " $addressesWithErrors addresses with errors.";
  }
  return $edDistrictReturn;
}

/*
 * Function to create county level districts
 */
function google_civic_information_county_districts($level, $limit, $update) {

  //Set variables
  $addressesDistricted = $addressesWithErrors = 0;

  //API Key
  $apikey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

  //States
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', ['name' => 'includedStatesProvinces']);
  foreach( $includedStatesProvinces as $stateProvinceId) {
    $statesProvinces[$stateProvinceId] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId));
  }

  //Counties
  $includedCounties = civicrm_api3('Setting', 'getvalue', ['name' => 'includedCounties']);
  foreach( $includedCounties as $countyId) {
    $counties[$countyId] = strtolower(CRM_Core_PseudoConstant::county($countyId));
  }

  $contactAddresses = electoral_district_addresses($limit, $level, $includedStatesProvinces, $update);

  while ($contactAddresses->fetch()) {

    $streetAddress = $city = $state = $districts = '';
    
    //Assemble the API URL
    $streetAddress = rawurlencode($contactAddresses->street_address);
    $city = rawurlencode($contactAddresses->city);
    $stateProvinceAbbrev = CRM_Core_PseudoConstant::stateProvinceAbbreviation($contactAddresses->state_province_id);
    $url = "https://www.googleapis.com/civicinfo/v2/representatives?key=$apikey&address=$streetAddress%20$city%20$stateProvinceAbbrev";

    $districts = electoral_curl($url);

    //Process the response
    //Check for errors first
    if ( isset($districts['error']) ) {
      $addressesWithErrors++;
      electoral_district_address_errors($districts, $contactAddresses->id);
    //Process divisions
    } else {
      $countyDivision = strtolower("ocd-division/country:us/state:$stateProvinceAbbrev");
      foreach($districts['divisions'] as $divisionKey => $division) {
        //Check if there's a district
        $divisionDistrict = ''; 
        $divisionParts = explode('/', str_replace($countyDivision . '/', '', $divisionKey));
        if(substr($divisionParts[0], 0, 6) == 'county' &&
           substr($divisionParts[1], 0, 16) == 'council_district' &&
           in_array(substr($divisionParts[0], 7), $counties)) {
           
          $county = ucwords(substr($divisionParts[0], 7));
          $divisionDistrict = substr($divisionParts[1], 17);
          electoral_district_create_update($contactAddresses->contact_id, $level, $contactAddresses->state_province_id, $county, NULL, NULL, $divisionDistrict);
        }
      }
      $addressesDistricted++;
    }
  }

  $edDistrictReturn = "$addressesDistricted addresses districted.";
  if ($addressesWithErrors > 0) {
    $edDistrictReturn .= " $addressesWithErrors addresses with errors.";
  }
  return $edDistrictReturn;
}

/*
 * Function to create city level districts
 */
function google_civic_information_city_districts($level, $limit, $update) {

  //Set variables
  $addressesDistricted = $addressesWithErrors = 0;

  //API Key
  $apikey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

  //States
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', ['name' => 'includedStatesProvinces']);
  foreach( $includedStatesProvinces as $stateProvinceId) {
    $statesProvinces[$stateProvinceId] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId));
  }

  //Cities
  $includedCities = explode(',', civicrm_api3('Setting', 'getvalue', ['name' => 'includedCities']));
  foreach( $includedCities as $city) {
    $cities[] = strtolower($city);
  }

  $contactAddresses = electoral_district_addresses($limit, $level, $includedStatesProvinces, $update);

  while ($contactAddresses->fetch()) {

    $streetAddress = $city = $state = $districts = '';
    
    //Assemble the API URL
    $streetAddress = rawurlencode($contactAddresses->street_address);
    $city = rawurlencode($contactAddresses->city);
    $stateProvinceAbbrev = CRM_Core_PseudoConstant::stateProvinceAbbreviation($contactAddresses->state_province_id);
    $url = "https://www.googleapis.com/civicinfo/v2/representatives?key=$apikey&address=$streetAddress%20$city%20$stateProvinceAbbrev";

    $districts = electoral_curl($url);

    //Process the response
    //Check for errors first
    if ( isset($districts['error']) ) {
      $addressesWithErrors++;
      electoral_district_address_errors($districts, $contactAddresses->id);
    //Process divisions
    } else {
      $cityDivision = strtolower("ocd-division/country:us/state:$stateProvinceAbbrev");
      foreach($districts['divisions'] as $divisionKey => $division) {
        //Check if there's a district
        $divisionDistrict = ''; 
        $divisionParts = explode('/', str_replace($cityDivision . '/', '', $divisionKey));
        if(substr($divisionParts[0], 0, 5) == 'place' &&
           substr($divisionParts[1], 0, 16) == 'council_district' &&
           in_array(substr($divisionParts[0], 6), $cities)) {
           
          $city = ucwords(substr($divisionParts[0], 6));
          $divisionDistrict = substr($divisionParts[1], 17);
          electoral_district_create_update($contactAddresses->contact_id, $level, $contactAddresses->state_province_id, NULL, $city, NULL, $divisionDistrict);
        }
      }
      $addressesDistricted++;
    }
  }

  $edDistrictReturn = "$addressesDistricted addresses districted.";
  if ($addressesWithErrors > 0) {
    $edDistrictReturn .= " $addressesWithErrors addresses with errors.";
  }
  return $edDistrictReturn;
}

/*
 * Helper function to assemble address district query
 */
function electoral_district_addresses($limit, $level, $statesProvinces, $update) {
  //Location Types
  $addressLocationType = civicrm_api3('Setting', 'getvalue', ['name' => 'addressLocationType']);

  // Set params for address lookup
  $addressSqlParams = array(
    1 => array($addressLocationType, 'Integer'),
    2 => array($limit, 'Integer'),
  );

  //Electoral District table
  $edTableName = civicrm_api3('CustomGroup', 'getvalue', ['return' => "table_name",'name' => "electoral_districts",]);

  //Electoral Status table
  $esTableName = civicrm_api3('CustomGroup', 'getvalue', ['return' => "table_name",'name' => "electoral_status",]);

  //States list used for SQL address lookup query
  $addressStatesProvinces = implode(', ', $statesProvinces);

  //Assemble address lookup query
  //TODO Why do we not include the postal code?
  $addressSql = "
       SELECT ca.id,
              ca.street_address,
              ca.city,
              ca.state_province_id,
              ca.contact_id
         FROM civicrm_address ca
    LEFT JOIN $edTableName ed
           ON ca.contact_id = ed.entity_id
          AND ed.electoral_districts_level = '$level'
    LEFT JOIN $esTableName es
           ON ca.id = es.entity_id
   INNER JOIN civicrm_contact cc
           ON ca.contact_id = cc.id
        WHERE ca.street_address IS NOT NULL
          AND ca.city IS NOT NULL
          AND ca.state_province_id IN ($addressStatesProvinces)
          AND ca.country_id = 1228
          AND cc.is_deceased != 1
          AND cc.is_deleted != 1
          AND es.electoral_status_error_code IS NULL
  ";

  //Handle a location type of Primary.
  if ($addressLocationType == 0) {
    $addressSql .= "
          AND ca.is_primary = 1
    ";
  } else {
    $addressSql .= "
          AND ca.location_type_id = %1
    ";
  }

  //FIXME there's probably a better way to do this
  if (!$update) {
    $addressSql .= "
          AND ed.id IS NULL
    ";
  }

  //Throttling
  $addressSql .= "
     GROUP BY cc.id
     ORDER BY cc.id DESC
        LIMIT %2
  ";
  //CRM_Core_Error::debug_var('addressSql', $addressSql);

  $addresses = CRM_Core_DAO::executeQuery($addressSql, $addressSqlParams);
  return $addresses;
}

/*
 * Helper function to save address errors when they occur
 */
function electoral_district_address_errors($districts, $addressId) {
  //Retain the error, so we can filter out the address on future runs until it's corrected
  $address_error_create = civicrm_api3('CustomValue', 'create', [
    'entity_id' => $addressId,
    'custom_electoral_status:Error Code' => $districts['error']['code'],
    'custom_electoral_status:Error Reason' => $districts['error']['errors'][0]['reason'],
    'custom_electoral_status:Error Message' => $districts['error']['message'],
  ]);
}

/*
 * Helper function to create or update electoral districts custom data
 */
function electoral_district_create_update($contactId, $level, $stateProvinceId = NULL, $countyId = NULL, $city = NULL, $chamber = NULL, $district = NULL, $inOffice = 0) {
  //Check if this level exists already
  $contactEdExists = electoral_district_exists($contactId, "$level", "$chamber");
  if ($contactEdExists['count'] == 1) {
    //Get the custom value set id
    $edTableNameId = electoral_district_table_name_id();
    $edId = $contactEdExists['values'][$contactId][$edTableNameId];
    //Update
    $contactEdUpdate = civicrm_api3('CustomValue', 'create', array(
      'entity_id' => $contactId,
      "custom_electoral_districts:Level:$edId" => "$level",
      "custom_electoral_districts:States/Provinces:$edId" => "$stateProvinceId",
      "custom_electoral_districts:County:$edId" => "$countyId",
      "custom_electoral_districts:City:$edId" => "$city",
      "custom_electoral_districts:Chamber:$edId" => "$chamber",
      "custom_electoral_districts:District:$edId" => "$district",
      "custom_electoral_districts:In office?:$edId" => $inOffice,
    ));
  } else {
    //Create
    $contactEdCreate = civicrm_api3('CustomValue', 'create', array(
      'entity_id' => $contactId,
      'custom_electoral_districts:Level' => "$level",
      'custom_electoral_districts:States/Provinces' => "$stateProvinceId",
      "custom_electoral_districts:County" => "$countyId",
      "custom_electoral_districts:City" => "$city",
      'custom_electoral_districts:Chamber' => "$chamber",
      'custom_electoral_districts:District' => "$district",
      'custom_electoral_districts:In office?' => $inOffice,
    ));
  }
}

/*
 * Helper function to check is Electoral Districts custom data already exists
 */
function electoral_district_exists($contactId, $level, $chamber = NULL) {
  $edExistsParams = array(
    'return' => "id",
    'id' => $contactId,
  );
  $edLevelId = civicrm_api3('CustomField', 'getvalue', ['return' => "id",'custom_group_id' => "electoral_districts",'name' => "electoral_level",]);
  $edLevelField = 'custom_' . $edLevelId;
  $edExistsParams[$edLevelField] = "$level";
  if (!empty($chamber)) {
    $edChamberId = civicrm_api3('CustomField', 'getvalue', ['return' => "id",'custom_group_id' => "electoral_districts",'name' => "electoral_chamber",]);
    $edChamberField = 'custom_' . $edChamberId;
    $edExistsParams[$edChamberField] = "$chamber";
  }
  $edExists = civicrm_api3('Contact', 'get', $edExistsParams);

  return $edExists;
}

/*
 * Helper function to get the table id 
 * of the Electoral Districts custom table
 */
function electoral_district_table_name_id() {
  $edTableName = civicrm_api3('CustomGroup', 'getvalue', ['return' => "table_name",'name' => "electoral_districts",]);
  return $edTableName . "_id";
}

/*
 * Google Civic Information Representatives API
 */
function civicrm_api3_google_civic_information_reps($params) {

  switch ($params['level']) {
    case 'country':
      $result = google_civic_information_country_reps($params['level'], $params['roles']);
      break;
    case 'administrativeArea1':
      $result = google_civic_information_state_reps($params['level'], $params['roles']);
      break;
    case 'administrativeArea2':
      $result = google_civic_information_county_reps($params['level']);
      break;
    case 'locality':
      $result = google_civic_information_city_reps($params['level']);
      break;
  }

  return civicrm_api3_create_success("$result");

}

/*
 * Function to create country level reps
 */
function google_civic_information_country_reps($level, $roles) {

  //Google API Key
  $apiKey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

  //Roles are equivalent to chambers
  $roles = explode(',' , $roles);

  //States
  $statesProvinces = array();
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', ['name' => 'includedStatesProvinces']);
  foreach( $includedStatesProvinces as $stateProvinceId) {
    $statesProvinces[$stateProvinceId] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId));
  }

  foreach($statesProvinces as $stateProvinceId => $stateProvinceAbbrev){
    foreach($roles as $role) {

      //Set the division for the lookup
      $countryDivision = "ocd-division/country:us/state:$stateProvinceAbbrev";
      $countryDivisionEncoded = urlencode($countryDivision);

      //Assemble the API URL
      $countryUrl = "https://www.googleapis.com/civicinfo/v2/representatives/$countryDivisionEncoded?levels=$level&recursive=true&roles=$role&key=$apiKey";

      //Do the lookup
      $countryReps = electoral_curl($countryUrl);

      //Process the reps
      $countryRepsCount = electoral_process_reps($countryReps, $countryDivision, $level, $stateProvinceId, NULL, NULL);
    }
  }

  $edRepReturn = "$countryRepsCount representatives created or updated.";
  return $edRepReturn;

}

/*
 * Function to create state level reps
 */
function google_civic_information_state_reps($level, $roles) {

  //Google API Key
  $apiKey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

  //Roles are equivalent to chambers
  $roles = explode(',' , $roles);

  //States
  $statesProvinces = array();
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', ['name' => 'includedStatesProvinces']);
  foreach( $includedStatesProvinces as $stateProvinceId) {
    $statesProvinces[$stateProvinceId] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId));
  }

  foreach($statesProvinces as $stateProvinceId => $stateProvinceAbbrev){
    foreach($roles as $role) {

      //Set the division for the lookup
      $stateDivision = "ocd-division/country:us/state:$stateProvinceAbbrev";
      $stateDivisionEncoded = urlencode($stateDivision);

      //Assemble the API URL
      $stateUrl = "https://www.googleapis.com/civicinfo/v2/representatives/$stateDivisionEncoded?levels=$level&recursive=true&roles=$role&key=$apiKey";

      //Do the lookup
      $stateReps = electoral_curl($stateUrl);

      //Process the reps
      $stateRepsCount = electoral_process_reps($stateReps, $stateDivision, $level, $stateProvinceId, NULL, NULL);
    }
  }

  $edRepReturn = "$stateRepsCount representatives created or updated.";
  return $edRepReturn;

}

/*
 * Function to get county reps
 */
function google_civic_information_county_reps($level) {

  //Google API Key
  $apiKey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

  //States
  $statesProvinces = array();
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', ['name' => 'includedStatesProvinces']);
  foreach( $includedStatesProvinces as $stateProvinceId) {
    $statesProvinces[$stateProvinceId] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId));
  }

  //Counties
  $includedCounties = civicrm_api3('Setting', 'getvalue', array('name' => 'includedCounties'));
  foreach( $includedCounties as $countyId) {
    $counties[$countyId] = strtolower(CRM_Core_PseudoConstant::county($countyId));
  }

  foreach($statesProvinces as $stateProvinceId => $stateProvinceAbbrev) {
    foreach($counties as $countyId => $county) {

      //Set the division for the lookup
      $countyDivision = "ocd-division/country:us/state:$stateProvinceAbbrev/county:$county";
      $countyDivisionEncoded = urlencode($countyDivision);

      //Assemble the API URL
      $countyUrl = "https://www.googleapis.com/civicinfo/v2/representatives/$countyDivisionEncoded?recursive=true&key=$apiKey";

      //Do the lookup
      $countyReps = electoral_curl($countyUrl);

      //Process the reps
      $countyRepsCount = electoral_process_reps($countyReps, $countyDivision, $level, $stateProvinceId, ucwords($county), NULL);
    }
  }

  $edRepReturn = "$countyRepsCount representatives created or updated.";
  return $edRepReturn;

}

/*
 * Function to get city reps
 */
function google_civic_information_city_reps($level) {

  //Google API Key
  $apiKey = civicrm_api3('Setting', 'getvalue', ['name' => 'googleCivicInformationAPIKey']);

  //States
  $statesProvinces = array();
  $includedStatesProvinces = civicrm_api3('Setting', 'getvalue', ['name' => 'includedStatesProvinces']);
  foreach( $includedStatesProvinces as $stateProvinceId) {
    $statesProvinces[$stateProvinceId] = strtolower(CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId));
  }
  //Cities
  $includedCities = explode(',', civicrm_api3('Setting', 'getvalue', array('name' => 'includedCities')));
  foreach( $includedCities as $city) {
    $cities[] = strtolower($city);
  }

  foreach($statesProvinces as $stateProvinceId => $stateProvinceAbbrev){
    foreach($cities as $city) {

      //Set the division for the lookup
      $cityDivision = "ocd-division/country:us/state:$stateProvinceAbbrev/place:$city";
      $cityDivisionEncoded = urlencode($cityDivision);

      //Assemble the API URL
      $cityUrl = "https://www.googleapis.com/civicinfo/v2/representatives/$cityDivisionEncoded?recursive=true&key=$apiKey";

      //Do the lookup
      $cityReps = electoral_curl($cityUrl);

      //Process the reps
      $cityRepsCount = electoral_process_reps($cityReps, $cityDivision, $level, $stateProvinceId, NULL, ucwords($city));
    }
  }

  $edRepReturn = "$cityRepsCount representatives created or updated.";
  return $edRepReturn;

}

/*
 * Function to create reps
 */
function electoral_process_reps ($reps, $division, $level, $stateProvinceId, $county = NULL, $city = NULL) {
  $repsCreatedUpdated = 0;

  //Google doesn't include the Bioguide ID, which we need for deduping
  //Building it from the @unitedstates project
  $repBioguideIds = array();
  $congressLegislatorsUrl = "https://theunitedstates.io/congress-legislators/legislators-current.json";
  $congressLegislators = electoral_curl($congressLegislatorsUrl);
  foreach ($congressLegislators as $legislator) {
    $officialName = str_replace(',', '', $legislator['name']['official_full']);
    $repBioguideIds["$officialName"] = $legislator['id']['bioguide'];
  }

  //Process the returned reps
  //Start with offices
  foreach($reps['offices'] as $officeKey => $office) {

    //Check if there's a district
    $officeDistrict = ''; 
    $hasOfficeDistrict = strstr(str_replace($division, '', $office['divisionId']), ":");
    if ($hasOfficeDistrict !== FALSE) {
      $officeDistrictParts = explode(':', str_replace($division, '', $office['divisionId']));
      //Some recursive searching from Google includes lots of divisions we don't care about
      if ($officeDistrictParts[0] == 'precinct' ||
        $officeDistrictParts[0] == 'school_district') {
        continue;
      }
      $officeDistrict = $officeDistrictParts[1];
    }
    //Process the officials for each office
    //Sometimes an office can have more than one official, like the US Senate
    foreach($reps['offices'][$officeKey]['officialIndices'] as $indexKey => $officialIndex) {

      $repContactExists = $chamber = '';

      //Initialize contact params
      $repParams = array('contact_type' => 'Individual', 'do_not_email' => 1);

      //Set official rep name for Bioguide lookup and name parsing
      $repName = $reps['officials'][$officialIndex]['name'];

      //Parse Name
      $repParams = electoral_parse_name($repName, $repParams);

      //Set Bioguide ID, only for country level 
      if ($level == 'country') {
        $bioguideId = $repParams['external_identifier'] = $repBioguideIds[$repName];

        //Check if rep already exists, to avoid duplicate contacts
        $repExistContact = civicrm_api3('Contact', 'get', ['return' => 'id','external_identifier' => "$bioguideId",]);
        if ($repExistContact['count'] == 1) {
          $repParams['id'] = $repExistContact['id'];
        }
      } else {
        $repExistContact = civicrm_api3('Contact', 'get', [
          'return' => 'id',
          'first_name' => $repParams['first_name'],
          'last_name' => $repParams['last_name'],
          'phone' => $reps['officials'][$officialIndex]['phones'][0],]);
        if ($repExistContact['count'] == 1) {
          $repParams['id'] = $repExistContact['id'];
        }
      }

      //Set rep image
      if (isset($reps['officials'][$officialIndex]['photoUrl'])) {
        $repParams['image_URL'] = $reps['officials'][$officialIndex]['photoUrl'];
      }

      //Create or update rep contact
      $repContact = civicrm_api3('Contact', 'create', $repParams);

      $contactId = $repContact['id'];

      //Create Rep Electoral Districts
      if (isset($reps['offices'][$officeKey]['roles'])) {
        if ($reps['offices'][$officeKey]['roles'][0] == 'legislatorUpperBody') {
          $chamber = 'upper';
        }
        if ($reps['offices'][$officeKey]['roles'][0] == 'legislatorLowerBody') {
          $chamber = 'lower';
        }
      }
      electoral_district_create_update($contactId, $level, $stateProvinceId, $county, $city, $chamber, $officeDistrict, 1);

      //Create the Email address
      if (isset($reps['officials'][$officialIndex]['emails'][0])) {
        electoral_create_email($contactId, $reps['officials'][$officialIndex]['emails'][0]);
      }

      //Create the Phone number
      if (isset($reps['officials'][$officialIndex]['phones'][0])) {
        electoral_create_phone($contactId, $reps['officials'][$officialIndex]['phones'][0]);
      }

      //Create the Address address
      if (isset($reps['officials'][$officialIndex]['address'][0]['line1'])) {
        electoral_create_address($contactId, $reps['officials'][$officialIndex]['address'][0]);
      }

      //Create website
      if (isset($reps['officials'][$officialIndex]['urls'][0])) {
        electoral_create_website($contactId, $reps['officials'][$officialIndex]['urls'][0], 2);
      }

      if (isset($reps['officials'][$officialIndex]['channels'])) {
        foreach($reps['officials'][$officialIndex]['channels'] as $channel) {
          if ($channel['type'] == 'Facebook') {
            //Create Facebook
            if ( $channel['id'] != NULL ) {
              $repFacebook = 'https://facebook.com/' . $channel['id'];
              electoral_create_website($contactId, $repFacebook, 3);
            }
          }
          if ($channel['type'] == 'Twitter') {
            //Create Twitter
            if ( $channel['id'] != NULL ) {
              $repTwitter = 'https://twitter.com/' . $channel['id'];
              electoral_create_website($contactId, $repTwitter, 11);
            }
          }
        }
      }
        
      //Tag the legislator with their party
      if ($repExistContact['count'] == 0 && 
          isset($reps['officials'][$officialIndex]['party'])) { 
        electoral_tag_party($contactId, $reps['officials'][$officialIndex]['party']);
      }
    }
    $repsCreatedUpdated++;
  }

  return $repsCreatedUpdated;
}

/* 
 * Helper function to parse Official Names
 */
function electoral_parse_name($name, $params) {
  if ($name == 'Vacant') {
    $params['last_name'] = $name;
  }
  $suffixes = array();
  $individualSuffixes = civicrm_api3('OptionValue', 'get', ['return' => ["label", "value"],'option_group_id' => "individual_suffix",]);
  foreach($individualSuffixes['values'] as $suffixId => $suffix) {
    $suffixes[$suffix['value']] = $suffix['label'];
  }

   //Check for suffixes
   foreach($suffixes as $suffixId => $suffixLabel) {
     $hasSuffix = strstr($name, $suffixLabel);
     if ($hasSuffix !== FALSE) {
       $params['suffix_id'] = $suffixId;
       $name = trim(str_replace($suffixLabel, '', $name));
     }
   }
   //TODO Do we need to do Prefixes too?

  //Check for nick names
  //This assume only a single nick name;
  $hasNickName = strstr($name, '"');
  if ($hasNickName !== FALSE) {
    $nickNameParts = explode('"', $name);
    $params['nick_name'] = trim($nickNameParts[1]);
    unset($nickNameParts[1]);
    $nickNameParts[0] = trim($nickNameParts[0]);
    $nickNameParts[2] = trim($nickNameParts[2]);
    $name = implode(' ', $nickNameParts);
  }

  //Process remaining name
  $nameParts = explode(' ', $name);
  if (count($nameParts) == 2) {
    $params['first_name'] = $nameParts[0];
    $params['last_name'] = $nameParts[1];
  } elseif (count($nameParts) == 3) {
    //First Initial
    $hasFirstInitial = strstr($nameParts[0], '.');
    if ($hasFirstInitial !== FALSE) {
      $params['first_name'] = $nameParts[0];
      $params['middle_name'] = $nameParts[1];
      $params['last_name'] = $nameParts[2];
    } else {
      //Middle Initial
      $hasMiddleInitial = strstr($nameParts[1], '.');
      if ($hasMiddleInitial !== FALSE) {
        $params['first_name'] = $nameParts[0];
        $params['middle_name'] = $nameParts[1];
        $params['last_name'] = $nameParts[2];
      } else {
        $params['first_name'] = $nameParts[0];
        $params['last_name'] = $nameParts[1];
        $params['last_name'] .= ' ' . $nameParts[2];
      }
    }
  } else {
    foreach($nameParts as $partKey => $namePart) {
      if ($partKey == 0) {
        $params['first_name'] = $namePart;
      } elseif ($partKey == 1) {
        $params['last_name'] = $namePart;
      } else {
        $params['last_name'] = ' ' . $namePart;
      }
    }
  }

  return $params;

}

/*
 * Helper function to check if email exists
 * and if not, create it
 */
function electoral_create_email ($contactId, $email) {
  //Check if contact has an email address set, Main location type
  $emailExist = civicrm_api3('Email', 'get', array(
    'return' => "email",
    'contact_id' => $contactId,
    'is_primary' => 1,
    'location_type_id' => 3,
  ));
  //If there is an existing email address, set the id for comparison
  if ($emailExist['count'] > 0) {
    $emailExistId = $emailExist['id'];
  }

  //Add an updated email address or a new one if none exist,
  //and set it to primary
  if (($emailExist['count'] == 1 && $emailExist['values'][$emailExistId]['email'] != strtolower($email)) ||
       $emailExist['count'] == 0 ) {
    $emailParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 3,
      'is_primary' => 1,
      'email' => "$email",
    );
    $createdEmail = civicrm_api3('Email', 'create', $emailParams);
  }
}

/*
 * Helper function to check if phone exists
 * and if not, create it
 */
function electoral_create_phone($contactId, $phone) {
  //Check if contact has a phone set, Main location type
  $phoneExist = civicrm_api3('Phone', 'get', array(
    'return' => "phone",
    'contact_id' => $contactId,
    'is_primary' => 1,
    'location_type_id' => 3,
  ));
  //If there is an existing phone number, set the id for comparison
  if ($phoneExist['count'] > 0) {
    $phoneExistId = $phoneExist['id'];
  }

  //Add an updated phone number or a new one if none exist,
  //and set it to primary
  if (($phoneExist['count'] == 1 && $phoneExist['values'][$phoneExistId]['phone'] != strtolower($phone)) ||
       $phoneExist['count'] == 0 ) {
    $phoneParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 3,
      'phone_type_id' => 1,
      'is_primary' => 1,
      'phone' => "$phone",
    );
    $createdPhone = civicrm_api3('Phone', 'create', $phoneParams);
  }
}

/*
 * Helper function to check if address exists
 * and if not, create it
 */
function electoral_create_address($contactId, $address) {
  $streetAddress = $address['line1'];
  //Check if contact has an address set
  $addressExist = civicrm_api3('Address', 'get', array(
    'return' => "street_address",
    'contact_id' => $contactId,
    'is_primary' => 1,
  ));
  //If there is an existing address address, set the id for comparison
  if ($addressExist['count'] > 0) {
    $addressExistId = $addressExist['id'];
  }

  //Add an updated address address or a new one if none exist,
  //and set it to primary
  if (($addressExist['count'] == 1 && $addressExist['values'][$addressExistId]['street_address'] != $streetAddress) ||
       $addressExist['count'] == 0 ) {
    $usStates = array_flip(CRM_Core_PseudoConstant::stateProvinceForCountry(1228, 'abbreviation'));
    $addressParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 3,
      'is_primary' => 1,
      'street_address' => $streetAddress,
      'supplemental_address_1' => $address['line2'],
      'city' => $address['city'],
      'state_province_id' => $usStates[$address['state']],
      'postal_code' => $address['zip'],
    );
    $createdAddress = civicrm_api3('Address', 'create', $addressParams);
  }
}

/*
 * Helper function to check if website exists
 * and if not, create it
 */
function electoral_create_website($contactId, $website, $websiteType) {
  //Check if contact has a website set, Main location type
  $websiteExist = civicrm_api3('Website', 'get', array(
    'return' => "url",
    'contact_id' => $contactId,
    'website_type_id' => $websiteType
  ));
  //If there is an existing website, set the id for comparison
  if ($websiteExist['count'] > 0) {
    $websiteExistId = $websiteExist['id'];
  }

  //Add an updated website or a new one if none exist,
  //and set it to primary
  if (($websiteExist['count'] == 1 && $websiteExist['values'][$websiteExistId]['url'] != $website) ||
       $websiteExist['count'] == 0 ) {
    $websiteParams = array(
      'contact_id' => $contactId,
      'url' => "$website",
      'website_type_id' => $websiteType
    );
    $website = civicrm_api3('Website', 'create', $websiteParams);
  }
}

/*
 * Helper function to tag contact with political party
 */
function electoral_tag_party($contactId, $party) {
  if ($party == 'Democratic') {
    $partyTag = civicrm_api3('EntityTag', 'create', array('entity_id' => $contactId,'tag_id' => "Democrat",));
  }
  if ($party == 'Independent') {
    $partyTag = civicrm_api3('EntityTag', 'create', array('entity_id' => $contactId,'tag_id' => "Independent",));
  }
  if ($party == 'Republican') {
    $partyTag = civicrm_api3('EntityTag', 'create', array('entity_id' => $contactId,'tag_id' => "Republican",));
  }
}

/*
 * Helper function for curl requests
 */
function electoral_curl($url) {
  //CRM_Core_Error::debug_var('url', $url);

  $verifySSL = civicrm_api('Setting', 'getvalue', ['version' => 3, 'name' => 'verifySSL']);

  //Intitalize curl
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);

  //Get results from API and decode the JSON
  $curl_return = json_decode(curl_exec($ch), TRUE);

  //Close curl
  curl_close($ch);

  return $curl_return;
}

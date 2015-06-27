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
function civicrm_api3_ny_times_districts($params) {

  $limit = '';
  if (isset($params['limit']) && is_numeric($params['limit'])) {
    $limit = $params['limit'];
  } else {
    return civicrm_api3_create_error(array(1), array("NY Times Districts API limit is not an integer."));
  }
  ny_times_districts($limit);
  return civicrm_api3_create_success(array(1), array("NY Times Districts API successful."));

}

function ny_times_districts($limit) {

  $apikey = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'nyTimesAPIKey'));

  //geo_code1 = latitude
  //geo_code2 = longitude
  //FIXME this assumes the Home location type because we're assuming that's where folks are registered
  //this obviously assumes a certain data model.  Should this assume the primary address?  
  //the default address type?  Or should there be an address flag that is checked?
  $contact_addresses = civicrm_api3('Address', 'get', array(
    'return' => "contact_id,geo_code_1,geo_code_2",
    'contact_id' => array('IS NOT NULL' => 1),
    'location_type_id' => 1,
    'state_province_id' => 1031,
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
    //FIXME HTTPS
    $url = "http://api.nytimes.com/svc/politics/v2/districts.json?api-key=$apikey&lat=$latitude&lng=$longitude";
    //CRM_Core_Error::debug_var('url', $url);

    //Get results from API and decode the JSON
    //The query above returns all addresses in NY state, 
    //so we have to ignore those request that fall outside New York City
    if (file_get_contents($url) != '' ) {
      $districts = json_decode(file_get_contents($url));
      //CRM_Core_Error::debug_var('districts', $districts);
    }

    if( $districts->status == 'OK' ) {
      $contact_id = $address['contact_id'];
      foreach ($districts->results as $district) {
        if ($district->level == 'City Council') {
          $city_council_district = $district->district;
          
          //Update the CiviCRM Contact
          //FIXME this doesn't update an existing entry on a multi-value custom data set
          $contact_rep_details_update = civicrm_api3('CustomValue', 'create', array(
            'entity_id' => $contact_id,
            'custom_Representative_Details:Level' => 'City',
            'custom_Representative_Details:District' => "$city_council_district",
          ));
        }
      }
    }
  }
}

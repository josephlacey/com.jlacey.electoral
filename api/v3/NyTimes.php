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
  $addressLocationType = civicrm_api('Setting', 'getvalue', array('version' => 3, 'name' => 'addressLocationType'));

  //geo_code1 = latitude
  //geo_code2 = longitude
  $contact_addresses = civicrm_api3('Address', 'get', array(
    'return' => "contact_id,geo_code_1,geo_code_2",
    'contact_id' => array('IS NOT NULL' => 1),
    'location_type_id' => $addressLocationType,
    'state_province_id' => 1031,
    'country_id' => 1228,
    'geo_code_1' => array('IS NOT NULL' => 1),
    'geo_code_2' => array('IS NOT NULL' => 1),
    'options' => array('limit' => $limit),
  ));

  foreach($contact_addresses['values'] as $address) {

    $latitude = $longitude = $districts = $contact_id = '';
    
    $latitude = $address['geo_code_1'];
    $longitude = $address['geo_code_2'];

    //Assemble the API URL
    //FIXME HTTPS changes in php 5.6 http://php.net/manual/en/migration56.openssl.php 
    $url = "https://api.nytimes.com/svc/politics/v2/districts.json?api-key=$apikey&lat=$latitude&lng=$longitude";

    //Get results from API and decode the JSON
    //The query above returns all addresses in NY state, 
    //so we have to ignore those requests that fall outside New York City
    if (file_get_contents($url) != '' ) {
      $districts = json_decode(file_get_contents($url));
    }

    if( $districts->status == 'OK' ) {
      $contact_id = $address['contact_id'];
      foreach ($districts->results as $district) {
        if ($district->level == 'City Council') {
          $city_council_district = $district->district;
          
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
            "$rep_details_level_field" => "nytimes",
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
              "$rep_details_level_field" => "nytimes",
              "$rep_details_district_field" => "$city_council_district",
            ));
            */
          } else {
            $contact_rep_details_update = civicrm_api3('CustomValue', 'create', array(
              'entity_id' => $contact_id,
              'custom_Representative_Details:Level' => 'nytimes',
              'custom_Representative_Details:District' => "$city_council_district",
            ));
          }
        }
      }
    }
  }
}

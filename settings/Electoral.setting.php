<?php 

/*
 * Settings metadata file
 */
return array(
  'sunlightFoundationAPIKey' => array(
    'group_name' => 'Electoral API settings',
    'group' => 'electoral',
    'name' => 'sunlightFoundationAPIKey',
    'type' => 'Text',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Sunlight Foundation API Key',
    'help_text' => 'Add your registered Sunlight Foundation API Key for Congress and Open States API calls.',
  ),
  'addressLocationType' => array(
    'group_name' => 'Electoral API settings',
    'group' => 'electoral',
    'name' => 'addressLocationType',
    'type' => 'Integer',
    'default' => '1',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Address location for district lookup.',
    'help_text' => 'Select the address location type to use when looking up a contact\'s districts.',
  ),
  'includedOpenStates' => array(
    'group_name' => 'Electoral API settings',
    'group' => 'electoral',
    'name' => 'includedOpenStates',
    'type' => 'Array',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'States included in Open States API calls',
    'help_text' => 'Add states to include in Open States API scheduled jobs',
  ),
  'nyTimesAPIKey' => array(
    'group_name' => 'Electoral API settings',
    'group' => 'electoral',
    'name' => 'nyTimesAPIKey',
    'type' => 'Text',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'NY Times API Key',
    'help_text' => 'Add your registered NY Times API Key for Districts API calls',
  ),
);

?>

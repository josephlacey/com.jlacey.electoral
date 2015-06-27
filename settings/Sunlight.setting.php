<?php 

/*
 * Settings metadata file
 */
return array(
  'sunlightFoundationAPIKey' => array(
    'group_name' => 'Sunlight Foundation API settings',
    'group' => 'sunlight',
    'name' => 'sunlightFoundationAPIKey',
    'type' => 'Text',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Sunlight Foundation API Key',
    'help_text' => 'Add your registered Sunlight Foundation API Key for Congress and Open States API calls.',
  ),
  'includedOpenStates' => array(
    'group_name' => 'Sunlight Foundation API settings',
    'group' => 'sunlight',
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
    'group_name' => 'Sunlight Foundation API settings',
    'group' => 'sunlight',
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

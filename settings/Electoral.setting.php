<?php 

/*
 * Settings metadata file
 */
return array(
  'proPublicaCongressAPIKey' => array(
    'group_name' => 'Electoral API settings',
    'group' => 'electoral',
    'name' => 'proPublicaCongressAPIKey',
    'type' => 'Text',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'ProPublica Congress API Key',
    'help_text' => 'Add your registered ProPublica Congress API Key for Congress API calls.',
  ),
  'openStatesAPIKey' => array(
    'group_name' => 'Electoral API settings',
    'group' => 'electoral',
    'name' => 'openStatesAPIKey',
    'type' => 'Text',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Open States API Key',
    'help_text' => 'Add your registered Open States API Key for Open States API calls.',
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
  'googleCivicInformationAPIKey' => array(
    'group_name' => 'Electoral API settings',
    'group' => 'electoral',
    'name' => 'googleCivicInformationAPIKey',
    'type' => 'Text',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Google Civic API Key',
    'help_text' => 'Add your registered Google Civic Information API Key for Open Civic Data API calls',
  ),
);

?>

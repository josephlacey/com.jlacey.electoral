<?php

//require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Admin_Form_Setting_Electoral extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'proPublicaCongressAPIKey' => 'Electoral API settings',
    'openStatesAPIKey' => 'Electoral API settings',
    'addressLocationType' => 'Electoral API settings',
    'includedOpenStates' => 'Electoral API settings',
    'googleCivicInformationAPIKey' => 'Electoral API settings',
  );

  function buildQuickForm() {

    $this->add('text', 'proPublicaCongressAPIKey', ts('ProPublica Congress API key'), NULL);
    $this->add('text', 'openStatesAPIKey', ts('Open States API key'), NULL);
		$this->_location_types = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
		$this->_location_types = array('Primary') + $this->_location_types;
    $this->add('select', 'addressLocationType', ts('Address location for district lookup.'), 
      $this->_location_types, FALSE, array('class' => 'crm-select2')
    );
    $this->add('select', 'includedOpenStates', ts('States included in Open States API calls'), 
      CRM_Core_PseudoConstant::stateProvince(), FALSE, array('multiple' => 'multiple', 'class' => 'crm-select2')
    );
    $this->add('text', 'googleCivicInformationAPIKey', ts('Google Civic Information API Key'), NULL);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}

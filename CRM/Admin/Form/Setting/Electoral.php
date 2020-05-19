<?php

//require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Admin_Form_Setting_Electoral extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'googleCivicInformationAPIKey' => 'Electoral API settings',
    'proPublicaCongressAPIKey' => 'Electoral API settings',
    'addressLocationType' => 'Electoral API settings',
    'includedStatesProvinces' => 'Electoral API settings',
    'includedCounties' => 'Electoral API settings',
    'includedCities' => 'Electoral API settings',
  );

  public function buildQuickForm() {
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/setting/electoral', 'reset=1'));

    $this->add('text', 'googleCivicInformationAPIKey', ts('Google Civic Information API Key'), NULL);
    $this->add('text', 'proPublicaCongressAPIKey', ts('ProPublica Congress API Key'), NULL);
    $this->_location_types = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $this->_location_types = array('Primary') + $this->_location_types;
    $this->add('select', 'addressLocationType', ts('Address location for district lookup.'),
      $this->_location_types, FALSE, array('class' => 'crm-select2')
    );
    $this->add('select', 'includedStatesProvinces', ts('States included in API calls'),
      CRM_Core_PseudoConstant::stateProvince(FALSE, TRUE), FALSE, array('multiple' => 'multiple', 'class' => 'crm-select2')
    );
    $this->addChainSelect('includedCounties', array('control_field' => 'includedStatesProvinces', 'data-callback' => 'civicrm/ajax/jqCounty', 'label' => "Counties included in the API calls", 'data-empty-prompt' => 'Choose state first', 'data-none-prompt' => '- N/A -', 'multiple' => TRUE, 'required' => FALSE, 'placeholder' => '- none -'));
    $this->add('text', 'includedCities', ts('Cities included in API Calls'), NULL);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  private function getRenderableElementNames() {
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

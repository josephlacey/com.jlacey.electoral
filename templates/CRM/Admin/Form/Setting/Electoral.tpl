<h3>Sunlight Foundation API extension settings</h3>

<div class="crm-block crm-form-block crm-electroal-api-form-block">
  <table class="form-layout-compressed">
       <tr class="crm-electoral-api-form-block-sunlight-foundation-api-key">
           <td>{$form.sunlightFoundationAPIKey.label}</td>
           <td>{$form.sunlightFoundationAPIKey.html|crmAddClass:huge}<br />
           <span class="description">{ts}Enter your Sunlight Foundation API Key.  <a href="http://sunlightfoundation.com/api/accounts/register/" target="_blank">Register at the Sunlight Foundation</a> to obtain a key.{/ts}</span></td>
       </tr>
       <tr class="crm-electoral-api-form-block-open-states-api-key">
           <td>{$form.openStatesAPIKey.label}</td>
           <td>{$form.openStatesAPIKey.html|crmAddClass:huge}<br />
           <span class="description">{ts}Enter your Open States API Key.  <a href="https://openstates.org/api/register/" target="_blank">Register at Open States</a> to obtain a key.{/ts}</span></td>
       </tr>
       <tr class="crm-electoral-api-form-block-address-location-type">
           <td>{$form.addressLocationType.label}</td>
           <td>{$form.addressLocationType.html}<br />
           <span class="description">{ts}Select the address location type to use when looking up a contact's districts.{/ts}</span></td>
       </tr>
       <tr class="crm-electoral-api-form-block-included-open-states">
           <td>{$form.includedOpenStates.label}</td>
           <td>{$form.includedOpenStates.html|crmAddClass:huge}<br />
           <span class="description">{ts}Select states to inclue in Open States API scheduled jobs.{/ts}</span></td>
       </tr>
       <tr class="crm-electroral-api-form-block-google-civic-information-api-key">
           <td>{$form.googleCivicInformationAPIKey.label}</td>
           <td>{$form.googleCivicInformationAPIKey.html|crmAddClass:huge}<br />
           <span class="description">{ts}Enter your Google Civic Information API Key.  <a href="https://developers.google.com/civic-information/docs/using_api#APIKey" target="_blank">Register at the Google Civic Information API</a> to obtain a key.{/ts}</span></td>
       </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

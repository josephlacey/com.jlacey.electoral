<h3>Sunlight Foundation API extension settings</h3>

<div class="crm-block crm-form-block crm-sunlight-form-block">
  <table class="form-layout-compressed">
       <tr class="crm-sunlight-form-block-sunlight-foundation-api-key">
           <td>{$form.sunlightFoundationAPIKey.label}</td>
           <td>{$form.sunlightFoundationAPIKey.html|crmAddClass:huge}<br />
           <span class="description">{ts}Enter your Sunlight Foundation API Key.  <a href="http://sunlightfoundation.com/api/accounts/register/" target="_blank">Register at the Sunlight Foundation</a> to obtain a key.{/ts}</span></td>
       </tr>
       <tr class="crm-sunlight-form-block-address-location-type">
           <td>{$form.addressLocationType.label}</td>
           <td>{$form.addressLocationType.html}<br />
           <span class="description">{ts}Select the address location type to use when looking up a contact's districts.{/ts}</span></td>
       </tr>
       <tr class="crm-sunlight-form-block-included-open-states">
           <td>{$form.includedOpenStates.label}</td>
           <td>{$form.includedOpenStates.html|crmAddClass:huge}<br />
           <span class="description">{ts}Select states to inclue in Open States API scheduled jobs.{/ts}</span></td>
       </tr>
       <tr class="crm-sunlight-form-block-ny-times-api-key">
           <td>{$form.nyTimesAPIKey.label}</td>
           <td>{$form.nyTimesAPIKey.html|crmAddClass:huge}<br />
           <span class="description">{ts}Enter your NY Times API Key.  <a href="http://developer.nytimes.com/apps/register" target="_blank">Register at the NY Times</a> to obtain a key.{/ts}</span></td>
       </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

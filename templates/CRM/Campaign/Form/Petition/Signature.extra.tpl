{literal}
<script type="text/javascript">

jQuery(document).ready(function() {

  //Hide Sign the Petition button
  jQuery('#_qf_Signature_next-bottom').hide();

  //Add Lookup submit button
  jQuery('#_qf_Signature_next-bottom').after('<button class="crm-form-submit" type="button" id="district-lookup">Send the Petition</button>');
  jQuery('#district-lookup').click(function() { requestNominatim(); });

});

//Nominatim Request
function requestNominatim() {

  //Hide look up button and showing Address look up... user message
  jQuery('#district-lookup').hide();
  jQuery('#district-lookup').after('<div id="address-request" class="form-submit">Address look up...</div>');

  //Assemble Address look up parameters based on form data
  var nominatimOptions = { 
    street: jQuery("input[id^='street_address']").val(), 
    city: jQuery("input[id^='city']").val(), 
    state: jQuery("select[id^='state_province'] > option[selected^='selected']").text(),
    postalcode: jQuery("input[id^='postal_code']").val(),
    format: 'json',
    addressdetails: 1
  };

  //Make Nominatim request and process results, includes failures
  var nominatim = jQuery.ajax({
    type: 'get',
    dataType: 'json',
    url: 'http://nominatim.openstreetmap.org/search?', 
    data: nominatimOptions,
    success: function(data) {
      processNominatim(data);
    },
  });
}

//Nominatim Response handling 
function processNominatim(nominatim) { 

  //Hide Address look up... message
  jQuery('#address-request').hide();

  //If the address look up fails, assume the generic city council member
  if (jQuery.isEmptyObject(nominatim)) {
    console.log('nominatim empty'); 
    //FIXME Using a District 0 seems like it's going to be a problem for at-large state districts
    //District 0
    //processMember(0);

  //Look up returns successful
  } else {

    //If more than more address is found, generate a list for user to select from
    if (jQuery(nominatim).length > 1) {
      var addressList = '<ul>';
      jQuery.each(nominatim, function(key, val) {
        //console.log(val);
        addressList += '<li><a href="#" class="nominatim-address" key="' + key + '">';
        if ( val['address']['house_number'] ) { 
          addressList += val['address']['house_number'] + ' '; 
        }
        if ( val['address']['road'] ) { 
          addressList += val['address']['road'] + ', '; 
        }
        if ( val['address']['city'] ) { 
          addressList += val['address']['city'] + ', '; 
        } else if ( val['address']['state_district'] ) { 
          addressList += val['address']['state_district'] + ', '; 
        }
        addressList += 'NY ';
        if ( val['address']['postcode'] ) { 
          addressList += val['address']['postcode']; 
        }
        addressList += '</a></li>';
      });
      addressList += '</ul>';

      //Show multiple returned addresses list
      jQuery('#webform-component-letter').after('<div id="multiple-addresses">Multiple addresses found.  Please select the correct one.</div>' + addressList + '<br />');

      //Proceed to district look up using the key of the selected address.
      jQuery('.nominatim-address').click(function() {
        jQuery('#multiple-addresses').hide();
        processLatLong(nominatim, jQuery(this).attr('key'))
      });

    //If look up returns one matching address,
    } else {
      //Proceed to district look up automaticaly
      processLatLong(nominatim, 0);
    }
  }
}

//Isolate latitude and longitude
function processLatLong(nominatim, key) {
  //Show Distrcit look up... user message
  jQuery('#district-lookup').after('<div id="district-request" class="form-submit">District look up...</div>');

  var latitude = nominatim[key]['lat'];
  var longitude = nominatim[key]['lon'];

  //Make district request
  requestDistricts(latitude, longitude);
}

//API Request
function requestDistricts(latitude, longitude) {
  //console.log('requestDistricts'); 

  //FIXME A custom data field attached to the petition determines this?
  //TODO state level isn't working yet.
  var level = 'federal';

  if ( level == 'federal') {
    var api = 'Congress';
  } else if (level == 'state') {
    var api = 'OpenStates';
  }

  CRM.api3('Setting', 'getvalue', {"name": "sunlightFoundationAPIKey" }).done(function(result) {
    var apiKey = result;
  });

  if ( api == 'Congress' ) {
    var APIUrl = 'https://congress.api.sunlightfoundation.com/districts/locate?';

    //Assemble API lookup parameters
    var APIOptions = { 
      latitude: latitude, 
      longitude: longitude, 
      apikey: apiKey,
    };
  } else if ( api == 'OpenStates' ) {
    var APIUrl = 'http://openstates.org/api/v1/legislators/geo/?';

    //Assemble API lookup parameters
    var APIOptions = { 
      lat: latitude, 
      long: longitude, 
      apikey: apiKey,
    };
  }

  //Make Sunlight Foundation request
  var districts = jQuery.ajax({
    type: 'get',
    dataType: 'jsonp',
    url: APIUrl,
    data: APIOptions,
    success: function(data) {
      processDistricts(data, api);
    },
    error: function() {
      console.log(api + ' API lookup error');
      //FIXME Using a District 0 seems like it's going to be a problem for at-large state districts
      //District 0
      //processMember(0);
    }
  });
}

//District API Response handling
function processDistricts (districts, api) {

  if ( api == 'Congress' ) {
    //Loop through districts to get City Council
    if (districts.count == 1) {
      jQuery.each(districts.results, function(key, val) {
        jQuery.each(val, function(key2, val2) {
          if (key2 == 'district') {
            district = val2;
          }
        });
      });
    } else {
      //If the look up fails, count is 0 or count is more than 1, then FIXME
      //District 0
      //district = 0;
    }
  
    //Set hidden State field for user, based on user completed State field
    //FIXME custom fields need to be abstracted
    //jQuery("select[id^='custom_92']").val(jQuery("select[id^='state_province'] > option[selected^='selected']").val());
    jQuery("#custom_92").val(jQuery("#state_province-Primary").val());
  
    //Set hidden District field for user
    jQuery("input[id^='custom_93']").val(district);
  } else if ( api == 'OpenStates' ) {
    //TODO multiple reps
  } 

  processPetition();

}

function processPetition() {

  jQuery('.crm-petition-activity-profile').after('<table><tr class="petition-recipients"></tr></table>');

  getRecipients();
  
  //Send Letter
  jQuery('#district-request').hide();
  jQuery('#_qf_Signature_next-bottom').show();
  
}

function getRecipients() {
  
  //FIXME custom fields need to be abstracted
  var state = jQuery("select[id^='custom_92']").val();
  var district = jQuery("input[id^='custom_93']").val();

  CRM.api3('Contact', 'get', {
    "sequential": 1,
    "return": "first_name,last_name,external_identifier,image_URL",
    "custom_91":"lower",
    "custom_92": state,
    "custom_93": district
  }).success(function(result) {
    jQuery(result['values']).each(function () {
      jQuery('.petition-recipients').append('<td><img src="' + this['image_URL'] + '" /><div>' + this['first_name'] + ' ' + this['last_name'] + "</div></td>");
    });
  }); 

  CRM.api3('Contact', 'get', {
    "sequential": 1,
    "return": "first_name,last_name,external_identifier,image_URL",
    "custom_91":"upper",
    "custom_92": state
  }).success(function(result) {
    jQuery(result['values']).each(function () {
      jQuery('.petition-recipients').append('<td><img src="' + this['image_URL'] + '" /><div>' + this['first_name'] + ' ' + this['last_name'] + "</div></td>");
    });
  }); 

}

</script>
{/literal}

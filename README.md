# com.jlacey.electoral

After installing the extension, the first configurations to make are the top level items, from the admin tool bar across the top, Administer > System Settings > Electoral API. That's where you'll add your API Keys, presumably just Google. 

If you're using the Google Maps Geocoding API for address geocoding, and the Google Civic Information API is also enabled in a Google Cloud 'Project' for the API key on Google's side, you can use the same API key here. 

Then, you need to manually select/'whitelist' which states, counties and cities to include in the lookups, if you're using those electoral levels. Note that we have only tested this with US electoral districts.

If you are trying to add City Council district number information for US cities like New York that have City Council 'districts', those should be added without issue. If you are trying to add district number information for cities like Chicago, IL or Santa Ana, CA that have City Council 'wards', those will *not* be added in version 2.0. That capability will probably be added in an upcoming version 3.0 that @MegaphoneJon is actively developing.

The second major step is to enable the Scheduled Jobs that will do the regular lookups. Again, from the administration bar, select Administer > System Settings > Scheduled Jobs. All the relevant jobs should be prefaced 'Google Civic Information API'. Enable those relevant to your desired electoral level(s). 

Please note that, as of May 28, 2020, Google's Civic Information API has a limit (or 'quota') of 25,000 API queries/calls per day, at 100 queries per 100 seconds, or, one query per second. See the link below for the quota limits for your API key. You should first change the value to your Google Cloud Project name at `project=` - https://console.developers.google.com/apis/api/civicinfo.googleapis.com/quotas?project=ocdata20200512 . 

So for example, if you are running Scheduled Jobs to populate hundreds, or thousands, of contact records with electoral districts for the first time, it is recommended you set a limit of 25 ( limit=25 ) in the parameters for each Google Civic Information Scheduled Job, if you have the same "quota" limits as per above.  

Setting the limit greater than 25 in the example above will result in error messages in the Schedule Job log (select View Job Log to the right of the Scheduled Job to see that) that there are 'XXX addreses with errors'. This error does not (necessarily) mean that your address is incorrect, but in this case, you are running queries/sending API calls at a higher rate than allowed in your 'quota'. To increase your and get faster results, you'll need to pay for extra daily lookups.

Two caveats: First, this is very much a beta extension, so there are bugs. Second, additional development of this version 2.0 is not planned, however @MegaphoneJon is develping a version 3.0. 



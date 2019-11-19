# coop.palantetech.electoral

After installing the extension, the first configurations to make are the top level items, from the admin tool bar across the top, Administer > System Settings > Electoral API. That's where you'll add your API Keys, presumably just Google. If you're using Google for address geocoding and the Civic Information API is enabled for the key on Google's side, you can use the same API key here. Then you need to whitelist which states, counties and cities to include in the lookups if you're using those electoral levels. Note that I've only tested this with US electoral districts.

The second major step is to enable the Scheduled Jobs that will do the regular lookups. Again from the administration bar, Administer > System Settings > Scheduled Jobs. All the relevant jobs should be prefaced, 'Google Civic Information API'. Enable the ones relevant to your electoral level. Note that Google's Civic Information API has a free limit of 2500/day, about 100/hour. To get faster results, you'll need to pay for extra daily lookups.

That should be everything you need to get it rolling. Two caveats: this is very much a beta extension, so there are bugs. Second my interest in supporting this extension has waned, so additional development isn't planned. Obviously feel free to fork it and submit PR's.

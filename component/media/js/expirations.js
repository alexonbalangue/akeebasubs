/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Setup (required for Joomla! 3)
 */
if(typeof(akeeba) == 'undefined') {
	var akeeba = {};
}
if(typeof(akeeba.jQuery) == 'undefined') {
	akeeba.jQuery = jQuery.noConflict();
}

akeeba.jQuery(document).ready(function(){
	// The url for our json data
	var jsonurl = 'index.php?option=com_akeebasubs&view=reports&task=getexpirations&format=json&layout=expirations';

	akeeba.jQuery.jqplot('akexpirationschart', jsonurl,
		{
			title: "AJAX JSON Data Renderer",
			dataRenderer: function(url, plot, options) {
				var ret = null;
				akeeba.jQuery.ajax({
					// have to use synchronous here, else the function
					// will return before the data is fetched
					async: false,
					url: url,
					dataType:"json",
					success: function(data) {
						ret = data;
					}
				});
				return ret;
			}
		}
	);
});
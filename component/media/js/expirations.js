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
	var expireChart = akeeba.jQuery.jqplot('akexpirationschart', [[[]]] ,{
		title: "Expirations by week"
	});

	var jsonurl = 'index.php?option=com_akeebasubs&view=reports&task=getexpirations&format=json&layout=expirations';

	akeeba.jQuery.ajax(jsonurl, {
		dataType: 'json',
		success : function(json, status, jqXH){

			var ymax = 0;
			var labels = [];

			akeeba.jQuery.each(json[0], function(key, value){
				labels.push(value[0]);
				if(value[1] > ymax){
					ymax = value[1];
				}
			});

			ymax = Math.ceil(ymax * 1.2);

			var options = {
				data : json,
				highlighter: { show: true, showMarker: false },
				series:[
					{
						renderer: akeeba.jQuery.jqplot.BarRenderer,
						rendererOptions: {
							barPadding : 10,
							barMargin : 10,
							barWidth : 40
						}
					}
				],
				axes:{
					yaxis : {max : ymax},
					xaxis:{
						renderer: akeeba.jQuery.jqplot.DateAxisRenderer,
						tickRenderer: akeeba.jQuery.jqplot.CanvasAxisTickRenderer ,
						ticks : labels,
						tickOptions: {
							angle: -90,
							fontSize: '10pt',
							formatString:'%Y-%m-%d',
							labelPosition: 'middle'
						}
					}
				}
			};
			akeeba.jQuery('#akexpirationschart').width('100%');
			akeeba.jQuery('#akexpirationschart').height(300);
			expireChart.replot(options)
		}
	});

});
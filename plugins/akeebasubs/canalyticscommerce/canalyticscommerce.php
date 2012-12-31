<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Daniel Dimitrov / compojoom.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsCanalyticscommerce extends JPlugin
{
	/**
	* Let's add some analytics code to track the subscription on the order success page! 
	* @param row - object
	*/
	public function onOrderMessage($row) {
		$document = JFactory::getDocument();
		
		// let us get the sub level
		$akeebasubsLevel = FOFModel::getTmpInstance('Levels','AkeebasubsModel')->setId($row->akeebasubs_level_id)->getItem();

		/**
		* doc example: https://developers.google.com/analytics/devguides/collection/gajs/gaTrackingEcommerce
		* don't change the _addTrans or _addItems parameters! 
		* Since we can only purchase 1 subscription at a time -> addItem is called only once.
		* If this changes in the future we will have to modify the _addItem part...
		*/
		$script = "var _gaq = _gaq || [];
				  _gaq.push(['_setAccount', '".$this->params->get('tracking_id')."']);
				  _gaq.push(['_trackPageview']);
				  _gaq.push(['_addTrans',
					'". $row->akeebasubs_subscription_id."',
					'". $this->params->get('store_name')."',
					'". $row->gross_amount."',
					'". $row->tax_amount."',
					'0',
					'',
					'',
					''
				  ]);

				  _gaq.push(['_addItem',
					'". $row->akeebasubs_subscription_id."',
					'". $row->akeebasubs_level_id."',
					'". $akeebasubsLevel->title."',
					'',   // category or variation
					'". $akeebasubsLevel->price."',
					'1'
				  ]);
				  _gaq.push(['_trackTrans']);

				  (function() {
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				  })();";
				  
		$document->addScriptDeclaration($script);
	}
}
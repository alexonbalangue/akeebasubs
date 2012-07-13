<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/**
 * Message pre-processing
 */
class AkeebasubsHelperMessage
{
	/**
	 * Pre-processes the message text in $text, replacing merge tags with those
	 * fetched based on subscription $sub
	 * @param string $text The message to process
	 * @param AkeebasubsTableSubscritpion $sub A subscription object
	 */
	public static function processSubscriptionTags($text, $sub)
	{
		// Get the user object for this subscription
		$user = JFactory::getUser($sub->user_id);
		
		// Get the extra user parameters object for the subscription
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($sub->user_id)
			->getFirstItem();
		
		// Merge the user objects
		$userdata = array_merge((array)$user, (array)($kuser->getData()));
		
		// Create and replace merge tags for subscriptions. Format [SUB:KEYNAME]
		foreach((array)($sub->getData()) as $k => $v) {
			if(is_array($v) || is_object($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_subscription_id') $k = 'id';
			$tag = '[SUB:'.strtoupper($k).']';
			$text = str_replace($tag, $v, $text);
		}
		
		// Create and replace merge tags for user data. Format [USER:KEYNAME]
		foreach($userdata as $k => $v) {
			if(is_object($v) || is_array($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_subscription_id') $k = 'id';
			$tag = '[USER:'.strtoupper($k).']';
			$text = str_replace($tag, $v, $text);
		}
		
		// Create and replace merge tags for custom fields data. Format [CUSTOM:KEYNAME]
		if(array_key_exists('params', $userdata)) {
			$custom = json_decode($userdata['params']);
			if(!empty($custom)) foreach($custom as $k => $v) {
				if(substr($k,0,1) == '_') continue;
				$tag = '[CUSTOM:'.strtoupper($k).']';
				$text = str_replace($tag, $v, $text);
			}
		}
		
		return $text;
	}
	
	/**
	 * Processes the language merge tags ([IFLANG langCode], [/IFLANG]) in some
	 * block of text.
	 * 
	 * @param string $text The text to process
	 * @param string $lang Which language to keep. Null means the default language.
	 */
	public static function processLanguage($text, $lang = null)
	{
		// Get the default language
		if(empty($lang)) {
			$enableTranslation = JFactory::getApplication()->getLanguageFilter();
			
			if($enableTranslation) {
				$lang = JFactory::getLanguage()->getTag();
			} else {
				$user = JFactory::getUser();
				if(property_exists($user, 'language')) {
					$lang = $user->language;
				} else {
					$params = $user->params;
					if(!is_object($params)) {
						jimport('joomla.registry.registry');
						$params = new JRegistry($params);
					}
					if(version_compare(JVERSION, '3.0', 'ge')) {
						$lang = $params->get('language','');
					} else {
						$lang = $params->getValue('language','');
					}
				}
				if(empty($lang)) {
					$lang = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
				}
			}
		}
		
		// Find languages
		$translations = array();
		while(strpos($text, '[IFLANG ') !== false)
		{
			$start = strpos($text, '[IFLANG ');
			$end = strpos($text, '[/IFLANG]');
			$langEnd = strpos($text,']',$start);
			$langCode = substr($text,$start+8,$langEnd-$start-8);
			$langText = substr($text, $langEnd+1, $end-$langEnd-1);
			$translations[$langCode] = $langText;
			
			if($start > 0) {
				$temp = substr($text, 0, $start-1);
			} else {
				$temp = 0;
			}
			$temp .= substr($text, $end+9);
			$text = $temp;
		}
		if(!empty($text)) {
			if(!array_key_exists('*', $translations)) {
				$translations['*'] = $text;
			}
		}
		
		$siteLang = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		
		if(array_key_exists($lang, $translations)) {
			return $translations[$lang];
		} elseif(array_key_exists($siteLang, $translations)) {
			return $translations[$siteLang];
		} elseif(array_key_exists('*', $translations)) {
			return $translations['*'];
		} else {
			return $text;
		}
		
	}
}
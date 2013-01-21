<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
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
	 * 
	 * @param   string  $text    The message to process
	 * @param   AkeebasubsTableSubscription  $sub  A subscription object
	 * 
	 * @return  string  The processed string
	 */
	public static function processSubscriptionTags($text, $sub, $extras = array())
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
		$subData = (array)($sub->getData());
		foreach($subData as $k => $v) {
			if(is_array($v) || is_object($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_subscription_id') $k = 'id';
			$tag = '[SUB:'.strtoupper($k).']';
			$text = str_replace($tag, $v, $text);
		}
		
		// Create and replace merge tags for custom per-subscription data. Format [SUBCUSTOM:KEYNAME]
		if(array_key_exists('params', $subData)) {
			if(is_string($subData['params'])) {
				$custom = json_decode($subData['params']);
			} elseif(is_array($subData['params'])) {
				$custom = $subData['params'];
			} elseif(is_object($subData['params'])) {
				$custom = (array)$subData['params'];
			} else {
				$custom = array();
			}
			if(!empty($custom)) foreach($custom as $k => $v) {
				if(substr($k,0,1) == '_') continue;
				$tag = '[SUBCUSTOM:'.strtoupper($k).']';
				if(is_array($v)) continue;
				$text = str_replace($tag, $v, $text);
			}
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
			if(is_string($userdata['params'])) {
				$custom = json_decode($userdata['params']);
			} elseif(is_array($userdata['params'])) {
				$custom = $userdata['params'];
			} elseif(is_object($userdata['params'])) {
				$custom = (array)$userdata['params'];
			} else {
				$custom = array();
			}
			if(!empty($custom)) foreach($custom as $k => $v) {
				if(substr($k,0,1) == '_') continue;
				$tag = '[CUSTOM:'.strtoupper($k).']';
				if(is_array($v)) continue;
				$text = str_replace($tag, $v, $text);
			}
		}
		
		// Extra variables replacement
		// -- Get the site name
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$sitename = $config->get('sitename');
		} else {
			$sitename = $config->getValue('config.sitename');
		}
		
		// -- First/last name
		$fullname = $user->name;
		$nameParts = explode(' ',$fullname, 2);
		$firstname = array_shift($nameParts);
		$lastname = !empty($nameParts) ? array_shift($nameParts) : '';
		
		// -- Get the subscription level
		$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->setId($sub->akeebasubs_level_id)
			->getItem();
		
		// -- Site URL
		list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();
		if($isCli) {
			jimport('joomla.application.component.helper');
			$baseURL = JComponentHelper::getParams('com_akeebasubs')->get('siteurl','http://www.example.com');
			$temp = str_replace('http://', '', $baseURL);
			$temp = str_replace('https://', '', $temp);
			$parts = explode($temp, '/', 2);
			$subpathURL = count($parts) > 1 ? $parts[1] : '';
		} else {
			$baseURL = JURI::base();
			$subpathURL = JURI::base(true);
		}
		$baseURL = str_replace('/administrator', '', $baseURL);
		$subpathURL = str_replace('/administrator', '', $subpathURL);
		
		// -- My Subscriptions URL
		if($isAdmin || $isCli) {
			$url = 'index.php?option=com_akeebasubs&view=subscriptions&layout=default';
		} else {
			$url = str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=subscriptions&layout=default'));
		}
		$url = ltrim($url, '/');
		$subpathURL = ltrim($subpathURL, '/');
		if(substr($url,0,strlen($subpathURL)+1) == "$subpathURL/") $url = substr($url,strlen($subpathURL)+1);
		$mysubsurl = rtrim($baseURL,'/').'/'.ltrim($url,'/');
		
		// -- The actual replacement
		$extras = array_merge(array(
			"\\n"			=> "\n",
			'[SITENAME]'	=> $sitename,
			'[SITEURL]'		=> $baseURL,
			'[FULLNAME]'	=> $fullname,
			'[FIRSTNAME]'	=> $firstname,
			'[LASTNAME]'	=> $lastname,
			'[USERNAME]'	=> $user->username,
			'[USEREMAIL]'	=> $user->email,
			'[LEVEL]'		=> $level->title,
			'[ENABLED]'		=> JText::_('COM_AKEEBASUBS_SUBSCRIPTION_COMMON_'. ($sub->enabled ? 'ENABLED' : 'DISABLED')),
			'[PAYSTATE]'	=> JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$sub->state),
			'[PUBLISH_UP]'	=> $jFrom->format(JText::_('DATE_FORMAT_LC2'), true),
			'[PUBLISH_DOWN]'=> $jTo->format(JText::_('DATE_FORMAT_LC2'), true),
			'[MYSUBSURL]'	=> $mysubsurl,
			'[URL]'			=> $mysubsurl,
		), $extras);
		foreach ($extras as $key => $value)
		{
			$subject = str_replace($key, $value, $text);
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
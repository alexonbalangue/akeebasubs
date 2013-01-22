<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsHelperEmail
{
	/**
	 * Load language overrides for a specific extension. Used to load the
	 * custom languages for each plugin, if necessary.
	 * 
	 * @param type $extension
	 */
	private static function loadLanguageOverrides($extension)
	{
		// Load the language files and their overrides
		$jlang = JFactory::getLanguage();
		// -- English (default fallback)
		$jlang->load($extension, JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load($extension.'.override', JPATH_ADMINISTRATOR, 'en-GB', true);
		// -- Default site language
		$jlang->load($extension, JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load($extension.'.override', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		// -- Current site language
		$jlang->load($extension, JPATH_ADMINISTRATOR, null, true);
		$jlang->load($extension.'.override', JPATH_ADMINISTRATOR, null, true);
		// -- User's preferred language
		jimport('joomla.registry.registry');
		$uparams = is_object($user->params) ? $user->params : new JRegistry($user->params);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$userlang = $uparams->get('language','');
		} else {
			$userlang = $uparams->getValue('language','');
		}
		if(!empty($userlang)) {
			$jlang->load($extension, JPATH_ADMINISTRATOR, $userlang, true);
			$jlang->load($extension.'.override', JPATH_ADMINISTRATOR, $userlang, true);
		}
	}
	
	/**
	 * Loads an email template from the database or, if it doesn't exist, from
	 * the language file.
	 * 
	 * @param   string  $key  The language key, in the form PLG_LOCATION_PLUGINNAME_TYPE
	 * 
	 * @return  array  isHTML: If it's HTML override from the db; text: The unprocessed translation string
	 */
	private static function loadEmailTemplate($key)
	{
		static $loadedLanguagesForExtensions = array();
		
		// Parse the key
		$key = strtolower($key);
		$keyParts = explode('_', $key, 4);
		
		$extension = $keyParts[0] . '_' . $keyParts[1] . '_' . $keyParts[2];
		$dbkey = $keyParts[2] . '_' . $keyParts[3];
		
		// Initialise
		$templateText = '';
		$subject = '';
		$loadLanguage = null;
		$isHTML = false;

		// Look for desired languages
		$jLang = JFactory::getLanguage();
		$userLang = JFactory::getUser()->getParam('language','');
		$languages = array(
			$userLang, $jLang->getTag(), $jLang->getDefault(), 'en-GB', '*'
		);

		// Look for an override in the database
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__akeebasubs_emailtemplates'))
			->where($db->qn('key').'='.$db->q($dbkey))
			->where($db->qn('enabled').'='.$db->q(1))
		;
		$db->setQuery($query);
		$allTemplates = $db->loadObjectList('language');

		if(!empty($allTemplates))
		{
			// Try finding the most relevant language override and load it
			$loadLanguage = null;
			foreach($languages as $lang) {
				if(!array_key_exists($lang, $allTemplates)) continue;

				if($lang != '*') $loadLanguage = $lang;

				$subject = $allTemplates[$lang]->subject;
				$templateText = $allTemplates[$lang]->body;
				
				$isHTML = true;
			}
		}
				
		if(!$isHTML)
		{
			if(!array_key_exists($extension, $loadedLanguagesForExtensions))
			{
				self::loadLanguageOverrides($extension);
			}
			
			$subjectKey = $extension . '_HEAD_' . $keyParts[3];
			$subject = JText::_($subjectKey);
			if($subject == $subjectKey) {
				$subjectKey = $extension . '_SUBJECT_' . $keyParts[3];
				$subject = JText::_($subjectKey);
			}
			
			$templateTextKey = $extension . '_BODY_' . $keyParts[3];
			$templateText = JText::_($templateTextKey);
			
			$loadLanguage = '';
		}
		
		return array($isHTML, $subject, $templateText, $loadLanguage);
	}
	
	/**
	 * Creates a PHPMailer instance
	 * 
	 * @param   boolean  $isHTML
	 * 
	 * @return  PHPMailer  A mailer instance
	 */
	private static function &getMailer($isHTML = true)
	{
		$mailer = JFactory::getMailer();
		
		$mailer->IsHTML($isHTML);
		// Required in order not to get broken characters
		$mailer->CharSet = 'UTF-8';
		
		return $mailer;
	}
	
	/**
	 * Creates a mailer instance, preloads its subject and body with your email
	 * data based on the key and extra substitution parameters and waits for
	 * you to send a recipient and send the email.
	 * 
	 * @param   object  $sub     The subscription record against which the email is sent
	 * @param   string  $key     The email key, in the form PLG_LOCATION_PLUGINNAME_TYPE
	 * @param   array   $extras  Any optional substitution strings you want to introduce
	 * 
	 * @return  boolean|PHPMailer False if something bad happened, the PHPMailer instance in any other case
	 */
	public static function getPreloadedMailer($sub, $key, array $extras = array())
	{
		// Load the template
		list($isHTML, $subject, $templateText, $loadLanguage) = self::loadEmailTemplate($key);
		
		// Substitute variables in $templateText and $subject
		if(!class_exists('AkeebasubsHelperMessage'))
		{
			$included = @include_once JPATH_ROOT . '/components/com_akeebasubs/helpers/message.php';
			if (!$included)
			{
				return false;
			}
		}
		
		$templateText = AkeebasubsHelperMessage::processSubscriptionTags($templateText, $sub, $extras);
		$subject = AkeebasubsHelperMessage::processSubscriptionTags($subject, $sub, $extras);
		
		// Get the mailer
		$mailer = self::getMailer($isHTML);
		$mailer->setSubject($subject);
		
		// Include inline images
		$pattern = '/(src)=\"([^"]*)\"/i';
		$number_of_matches = preg_match_all($pattern, $templateText, $matches, PREG_OFFSET_CAPTURE);
		if($number_of_matches > 0) {
			$substitutions = $matches[2];
			$last_position = 0;
			$temp = '';
	
			// Loop all URLs
			$imgidx = 0;
			$imageSubs = array();
			foreach($substitutions as &$entry)
			{
				// Copy unchanged part, if it exists
				if($entry[1] > 0)
					$temp .= substr($templateText, $last_position, $entry[1]-$last_position);
				// Examine the current URL
				$url = $entry[0];
				if( (substr($url,0,7) == 'http://') || (substr($url,0,8) == 'https://') ) {
					// External link, skip
					$temp .= $url;
				} else {
					$ext = strtolower(JFile::getExt($url));
					if(!JFile::exists($url)) {
						// Relative path, make absolute
						$url = dirname($template).'/'.ltrim($url,'/');
					}
					if( !JFile::exists($url) || !in_array($ext, array('jpg','png','gif')) ) {
						// Not an image or inexistent file
						$temp .= $url;
					} else {
						// Image found, substitute
						if(!array_key_exists($url, $imageSubs)) {
							// First time I see this image, add as embedded image and push to
							// $imageSubs array.
							$imgidx++;
							$mailer->AddEmbeddedImage($url, 'img'.$imgidx, basename($url));
							$imageSubs[$url] = $imgidx;
						}
						// Do the substitution of the image
						$temp .= 'cid:img'.$imageSubs[$url];
					}
				}
				
				// Calculate next starting offset
				$last_position = $entry[1] + strlen($entry[0]);
			}
			// Do we have any remaining part of the string we have to copy?
			if($last_position < strlen($templateText))
				$temp .= substr($templateText, $last_position);
			// Replace content with the processed one
			$templateText = $temp;
		}
		
		$mailer->setBody($templateText);

		return $mailer;
	}
}
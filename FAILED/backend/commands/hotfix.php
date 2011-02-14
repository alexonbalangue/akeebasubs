<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Hotfixes for Nooku bugs not yet fixed in SVN
 * 
 * @author Nicholas K. Dionysopoulos <nicholas-at-akeebabackup-dot-com>
 * @license GNU GPL v3 or later
 */
class ComAkeebasubsCommandHotfix extends KCommand
{
	public function _controllerAfterSave(KCommandContext $context)
	{
		//Prevent trapped redirects
		$row				= $context->caller->getModel()->getItem();
		$url				= clone KRequest::url();
		$url->query['id']	= $row->id;

		$redirect = $context->caller->getRedirect();
		$pluralView = KInflector::pluralize($url->query['view']);
		
		if(empty($redirect['url']->query['id']) && ($redirect['url']->query['view'] == $pluralView)) {
			$context->caller->setRedirect($url, $redirect['message'], $redirect['type']);
		} elseif($redirect['url'] != (string)$url) {
			unset($url->query['id']);
			$url->query['view']	= KInflector::pluralize($url->query['view']);
			$context->caller->setRedirect($url, $redirect['message'], $redirect['type']);
		}
		
		return true;
	}
	
	public function _controllerAfterApply(KCommandContext $context)
	{
		return $this->_controllerAfterSave($context);
	}
	
	public function _controllerAfterCancel(KCommandContext $context)
	{
		$url				= clone KRequest::url();
		
		unset($url->query['id']);
		$url->query['view']	= KInflector::pluralize($url->query['view']);
		$context->caller->setRedirect($url, $redirect['message'], $redirect['type']);
	}
}
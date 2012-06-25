<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

include_once JPATH_LIBRARIES.'/fof/include.php';

function AkeebasubsBuildRoute(&$query)
{
	$segments = array();
	
	// Default view
	$default = 'levels';
	
	// We need to find out if the menu item link has a view param
	if(array_key_exists('Itemid', $query)) {
		$menu = JFactory::getApplication()->getMenu()->getItem($query['Itemid']);
		if(!is_object($menu)) {
			$menuquery = array();
		} else {
			parse_str(str_replace('index.php?',  '',$menu->link), $menuquery); // remove "index.php?" and parse
		}
	} else {
		$menuquery = array();
	}
	
	// Add the view
	$newView = array_key_exists('view', $query) ? $query['view'] :
					(array_key_exists('view', $menuquery) ? $menuquery['view'] : $default);
	if($newView == 'level') {
		$newView = 'new';
	} elseif($newView == 'message') {
		if(!array_key_exists('layout', $query)) $query['layout'] = 'order';
		if($query['layout'] == 'order') {
			$newView = 'thankyou';
		} else {
			$newView = 'cancelled';
		}
		unset($query['layout']);
	} elseif($newView == 'userinfo') {
		if(!array_key_exists('layout', $query)) unset($query['layout']);
	}
	$segments[] = $newView;
	unset($query['view']);
	
	// Add the slug
	if($newView != 'userinfo') {
		if(array_key_exists('slug', $query) && (FOFInflector::isSingular($segments[0]) || ($segments[0] == 'new')) ){
			$segments[1] = $query['slug'];
			unset($query['slug']);
		} elseif(array_key_exists('id', $query) && ($segments[0] == 'subscription')) {
			$segments[1] = $query['id'];
			unset($query['id']);
		}
	}
	
	return $segments;
}

function AkeebasubsParseRoute($segments)
{
	// accepted views:
	$views = array('new','thankyou','cancelled', 'level', 'levels', 'message', 'subscribe', 'subscription', 'subscriptions', 'callback', 'validate', 'userinfo');
	
	// accepted layouts:
	$layoutsAccepted = array(
		'message' => array('order', 'cancel')
	);
	
	// default view
	$default = 'levels';

	$mObject = JFactory::getApplication()->getMenu()->getActive();
	$menu = is_object($mObject) ? $mObject->query : array();

	// circumvent the auto-segment decoding
	$segments = str_replace(':', '-', $segments);

	$vars = array();

	// if there's no view, but the menu item has view info, we use that
	if(count($segments))
	{
		if(!in_array($segments[0], $views))  {
			$vars['view'] = array_key_exists('view', $menu) ? $menu['view'] : $default;
		} else {
			$vars['view'] = array_shift($segments);
		}
		
		switch($vars['view']) {
			case 'new':
				$vars['view'] = 'level';
				break;
			case 'thankyou':
				$vars['view'] = 'message';
				$vars['layout'] = 'order';
				break;
			case 'cancelled':
				$vars['view'] = 'message';
				$vars['layout'] = 'cancel';
				break;
			case 'userinfo':
				$vars['view'] = 'userinfo';
				$vars['layout'] = 'default';
				break;
		}

		array_push($segments, $vars['view']);
		if(array_key_exists('layout', $vars)) array_unshift($segments, $vars['layout']);

		$layouts = array_key_exists($vars['view'], $layoutsAccepted) ? $layoutsAccepted[$vars['view']] : array();
		if(!in_array($segments[0], $layouts))  {
			$vars['layout'] = array_key_exists('layout', $menu) ? $menu['layout'] : 'default';
		} else {
			$vars['layout'] = array_shift($segments);
		}
		
		// if we are in a singular view, the next item is the slug, unless we are in the userinfo view
		if(FOFInflector::isSingular($vars['view']) && ($vars['view'] != 'userinfo')) {
			if($vars['view'] == 'subscription') {
				$vars['id'] = array_shift($segments);
			} else {
				$vars['slug'] = array_shift($segments);
			}
		}
	}

	return $vars;
}
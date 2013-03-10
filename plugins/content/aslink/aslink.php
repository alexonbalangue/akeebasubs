<?php
/**
 * @package        akeebasubs
 * @copyright    Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

// PHP version check
if(defined('PHP_VERSION')) {
	$version = PHP_VERSION;
} elseif(function_exists('phpversion')) {
	$version = phpversion();
} else {
	// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}
// Old PHP version detected. EJECT! EJECT! EJECT!
if(!version_compare($version, '5.3.0', '>=')) return;

// Make sure FOF is loaded, otherwise do not run
if(!defined('FOF_INCLUDED')) {
	include_once JPATH_LIBRARIES.'/fof/include.php';
}
if(!defined('FOF_INCLUDED') || !class_exists('FOFLess', true))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
JLoader::import('joomla.application.component.helper');
if(!JComponentHelper::isEnabled('com_akeebasubs', true)) return;

class plgContentAslink extends JPlugin
{
    /**
     * Gets the level ID out of a level title. If an ID was passed, it simply returns the ID.
     * If a non-existent subscription level is passed, it returns -1.
     *
     * @param $title string|int The subscription level title or ID
     *
     * @return int The subscription level ID
     */
    private static function getId($title, $slug = false)
    {
        static $levels = null;
        static $slugs = null;
        static $upperSlugs = null;

        // Don't process invalid titles
        if (empty($title)) return -1;

        // Fetch a list of subscription levels if we haven't done so already
        if (is_null($levels)) {
            $levels = array();
            $slugs = array();
            $upperSlugs = array();
            $list = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
                ->getList();
            if (count($list)) foreach ($list as $level) {
                $thisTitle = strtoupper($level->title);
                $levels[$thisTitle] = $level->akeebasubs_level_id;
                $slugs[$thisTitle] = $level->slug;
                $upperSlugs[strtoupper($level->slug)] = $level->slug;
            }
        }

        $title = strtoupper($title);
        if (array_key_exists($title, $levels)) {
            // Mapping found
            return $slug ? $slugs[$title] : $levels[$title];
        } elseif (array_key_exists($title, $upperSlugs)) {
            $mySlug = $upperSlugs[$title];
            if ($slug) {
                return $mySlug;
            } else {
                foreach ($slugs as $t => $s) {
                    if ($s = $mySlug) {
                        return $levels[$t];
                    }
                }
                return -1;
            }
        } elseif ((int)$title == $title) {
            $id = (int)$title;
            $title = '';
            // Find the title from the ID
            foreach ($levels as $t => $lid) {
                if ($lid == $id) {
                    $title = $t;
                    break;
                }
            }

            if (empty($title)) {
                return $slug ? '' : -1;
            } else {
                return $slug ? $slugs[$title] : $levels[$title];
            }
        } else {
            // No match!
            return $slug ? '' : -1;
        }
    }

    private static function process($match)
    {
        $ret = '';

        if($match[1] != 'view=levels') {
            $slug = self::getId($match[1], true);
            if (!empty($slug)) {
                $root = self::getRootUrl();

                $itemId = self::_findItem($slug);
                if($itemId) {
                    $itemId = '&Itemid=' . $itemId;
                }

                $ret = rtrim($root, '/') . JRoute::_('index.php?option=com_akeebasubs&view=level&slug=' . $slug . $itemId);
            }
        } else {
            $root = self::getRootUrl();

            $itemId = self::_findItem('levels');
            if($itemId) {
                $itemId = '&Itemid=' . $itemId;
            }

            $ret = rtrim($root, '/') . JRoute::_('index.php?option=com_akeebasubs&view=levels&Itemid=' . $itemId);
        }

        return $ret;
    }

    private function getRootUrl() {
        $root = rtrim(JURI::base(), '/');
        $subpath = JURI::base(true);
        if (!empty($subpath) && ($subpath != '/')) {
            $root = substr($root, 0, -1 * strlen($subpath));
        }

        return $root;
    }

    public function onContentPrepare($context, &$article, &$params, $limitstart = 0)
    {
        // Check whether the plugin should process or not
        if (JString::strpos($article->text, 'aslink') === false) {
            return true;
        }

        // Search for this tag in the content
        $regex = "#{aslink (.*?)}#s";

        $article->text = preg_replace_callback($regex, array('self', 'process'), $article->text);
    }


    /**
     * Finds out the Itemid for the subscription
     * @static
     * @param $slug
     * @return null
     */
    private static function _findItem($slug)
    {

        $component = JComponentHelper::getComponent('com_akeebasubs');
        $menus = JApplication::getMenu('site', array());
		$items = $menus->getItems('component_id', $component->id);
        $itemId = null;

        if (count($items)) {
            foreach ($items as $item) {
				if(is_string($item->params)) {
					$params = new JRegistry();
					if(version_compare(JVERSION, '3.0', 'ge')) {
						$params->loadString($item->params, 'JSON');
					} else {
						$params->loadJSON($item->params);
					}
				} else {
					$params = $item->params;
				}
				
                if (@$item->query['view'] == 'level') {
					if(version_compare(JVERSION, '3.0', 'ge')) {
						if ((@$params->get('slug') == $slug)) {
							$itemId = $item->id;
							break;
						}
					} else {
						if ((@$params->getValue('slug') == $slug)) {
							$itemId = $item->id;
							break;
						}
					}
                }

                if (@$item->query['view'] == 'levels') {
                    if($item->query['view'] == $slug) {
                        $itemId = $item->id;
                        break;
                    }
                }
            }
        }
        return $itemId;
    }
}
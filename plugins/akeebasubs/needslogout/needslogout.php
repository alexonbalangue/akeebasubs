<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2014 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsNeedslogout extends JPlugin
{
	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(& $subject, $config = array())
	{
		if(!is_object($config['params'])) {
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);
	}

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
        if($info['status'] != 'modified')
        {
            return;
        }

        if(!isset($info['modified']->enabled))
        {
            return;
        }

        $user = F0FModel::getTmpInstance('Users', 'AkeebasubsModel')->getTable();
        $user->load(array('user_id' => $row->user_id));

        $bind['needs_logout'] = 1;
        $user->save($bind);
	}
}
<?php
/**
 * @package        akeebasubs
 * @subpackage     plugins.akeebasubs.reseller
 * @copyright      Copyright 2013-2016 Nicholas K. Dionysopoulos
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Subscriptions;

class plgAkeebasubsReseller extends JPlugin
{
    /**
     * Public constructor. Overridden to load the language strings.
     *
     * @param object $subject
     * @param array $config
     */
	public function __construct(& $subject, $config = array())
	{
		if (!is_object($config['params']))
		{
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);
	}

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 *
	 * @param   Subscriptions  $row   The subscriptions row
	 * @param   array          $info  The row modification information
	 *
	 * @return  void
	 */
	public function onAKSubscriptionChange(Subscriptions $row, array $info)
	{
		//
	}

	/**
	 * Notifies the component of the supported email keys by this plugin.
	 *
	 * @return  array
	 *
	 * @since 3.0
	 */
	public function onAKGetEmailKeys()
	{
		$this->loadLanguage();

		return array(
			'section' => $this->_name,
			'title'   => JText::_('PLG_AKEEBASUBS_RESELLER_EMAILSECTION'),
			'keys'    => array(
				'COUPONCODE'    => JText::_('PLG_AKEEBASUBS_RESELLER_EMAIL_COUPONCODE'),
			)
		);
	}
}
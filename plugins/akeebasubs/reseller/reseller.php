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
    private $company_url;
    private $api_key;
    private $api_pwd;

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

        $this->company_url = $this->params->get('company_url', '');
        $this->api_key     = $this->params->get('api_key', '');
        $this->api_pwd     = $this->params->get('api_pwd', '');
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
        // If I already have a reseller coupon, there's no need to continue
        $params = $row->params;

        if(isset($params['reseller_coupon']))
        {
            return;
        }

        // Sanity checks
        if(!$this->company_url || !$this->api_key || !$this->api_pwd)
        {
            // Required info missing, let's stop here
            return;
        }

        $payState = $row->getFieldValue('state', 'N');

        // No payment has been made yet; do not contact the company site
        if ($payState == 'N')
        {
            return;
        }

        // Did the payment status just change to C or P? It's a new subscription
        if (array_key_exists('state', (array)$info['modified']) && in_array($payState, array('P', 'C')))
        {
            if ($row->enabled)
            {
                if (is_object($info['previous']) && $info['previous']->getFieldValue('state') == 'P')
                {
                    // A pending subscription just got paid
                    $this->requestCode($row);
                }
                else
                {
                    // A new subscription just got paid; send new subscription notification
                    $this->requestCode($row);
                }
            }
            elseif ($payState == 'C')
            {
                if ($row->contact_flag <= 2)
                {
                    // A new subscription which is for a renewal (will be active in a future date)
                    $this->requestCode($row);
                }
            }
        }
        elseif ($info['status'] == 'modified')
        {
            // If the subscription got disabled and contact_flag is 3, do not contact the user.
            // The flag is set to 3 only when a user has already renewed his subscription.
            if (array_key_exists('enabled', (array)$info['modified']) && !$row->enabled && ($row->contact_flag == 3))
            {
                return;
            }
            elseif (array_key_exists('enabled', (array)$info['modified']) && $row->enabled)
            {
                // Subscriptions just enabled, suppose date triggered
                if (($payState == 'C'))
                {
                    $this->requestCode($row);
                }
            }
        }
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

    /**
     * Contacts the company site and requests for a coupon code that will be stored inside this subscription
     *
     * @param   Subscriptions   $row
     */
    private function requestCode($row)
    {
        $url  = trim($this->company_url, '/');
        $url .= '/index.php?option=com_akeebasubs&view=APICoupons&task=create';
        $url .= '&key='.$this->api_key.'&pwd='.$this->api_pwd.'&format=json';

        $adapter  = new FOFDownload();
        $raw_data = $adapter->getFromURL($url);

        // Do I get a connection error?
        if(!$raw_data)
        {
            // TODO notifiy the administrator of the site
            return;
        }

        $data = json_decode($raw_data, true);

        // Did I get an invalid response?
        if(!$data)
        {
            // TODO notifiy the administrator of the site
            return;
        }

        // Did I get an error while creating a coupon?
        if (isset($data['error']))
        {
            // TODO notifiy the administrator of the site
            return;
        }

        // Anyway, the coupon code is missing?
        if(!isset($data['coupon']))
        {
            // TODO notifiy the administrator of the site
            return;
        }

        // Ah ok, if we're here we can safely continue
        $params = $row->params;
        $params['reseller_coupon'] = $data['coupon'];

        // I have to pass the "dontNotify" flag, otherwise the event will get into an infinite loop
        $new_data = array(
            'params' => $params,
            '_dontNotify' => true
        );

        try
        {
            $row->save($new_data);
        }
        catch(\Exception $e)
        {
            // Wait something bad happened while saving the subscription?

        }
    }
}
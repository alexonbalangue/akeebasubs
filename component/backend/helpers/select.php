<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsHelperSelect
{
	public static $countries = array(
		'' => '----',
		'AD' =>'Andorra', 'AE' =>'United Arab Emirates', 'AF' =>'Afghanistan',
		'AG' =>'Antigua and Barbuda', 'AI' =>'Anguilla', 'AL' =>'Albania',
		'AM' =>'Armenia', 'AN' =>'Netherlands Antilles', 'AO' =>'Angola',
		'AQ' =>'Antarctica', 'AR' =>'Argentina', 'AS' =>'American Samoa',
		'AT' =>'Austria', 'AU' =>'Australia', 'AW' =>'Aruba',
		'AX' =>'Aland Islands', 'AZ' =>'Azerbaijan', 'BA' =>'Bosnia and Herzegovina',
		'BB' =>'Barbados', 'BD' =>'Bangladesh',	'BE' =>'Belgium',
		'BF' =>'Burkina Faso', 'BG' =>'Bulgaria', 'BH' =>'Bahrain',
		'BI' =>'Burundi', 'BJ' =>'Benin', 'BL' =>'Saint Barthélemy',
		'BM' =>'Bermuda', 'BN' =>'Brunei Darussalam', 'BO' =>'Bolivia, Plurinational State of',
		'BR' =>'Brazil', 'BS' =>'Bahamas', 'BT' =>'Bhutan', 'BV' =>'Bouvet Island',
		'BW' =>'Botswana', 'BY' =>'Belarus', 'BZ' =>'Belize', 'CA' =>'Canada',
		'CC' =>'Cocos (Keeling) Islands', 'CD' =>'Congo, the Democratic Republic of the',
		'CF' =>'Central African Republic', 'CG' =>'Congo', 'CH' =>'Switzerland',
		'CI' =>'Cote d\'Ivoire', 'CK' =>'Cook Islands', 'CL' =>'Chile',
		'CM' =>'Cameroon', 'CN' =>'China', 'CO' =>'Colombia', 'CR' =>'Costa Rica',
		'CU' =>'Cuba', 'CV' =>'Cape Verde', 'CX' =>'Christmas Island', 'CY' =>'Cyprus',
		'CZ' =>'Czech Republic', 'DE' =>'Germany', 'DJ' =>'Djibouti', 'DK' =>'Denmark',
		'DM' =>'Dominica', 'DO' =>'Dominican Republic', 'DZ' =>'Algeria',
		'EC' =>'Ecuador', 'EE' =>'Estonia', 'EG' =>'Egypt', 'EH' =>'Western Sahara',
		'ER' =>'Eritrea', 'ES' =>'Spain', 'ET' =>'Ethiopia', 'FI' =>'Finland',
		'FJ' =>'Fiji', 'FK' =>'Falkland Islands (Malvinas)', 'FM' =>'Micronesia, Federated States of',
		'FO' =>'Faroe Islands', 'FR' =>'France', 'GA' =>'Gabon', 'GB' =>'United Kingdom',
		'GD' =>'Grenada', 'GE' =>'Georgia', 'GF' =>'French Guiana', 'GG' =>'Guernsey',
		'GH' =>'Ghana', 'GI' =>'Gibraltar', 'GL' =>'Greenland', 'GM' =>'Gambia',
		'GN' =>'Guinea', 'GP' =>'Guadeloupe', 'GQ' =>'Equatorial Guinea', 'GR' =>'Greece',
		'GS' =>'South Georgia and the South Sandwich Islands', 'GT' =>'Guatemala',
		'GU' =>'Guam', 'GW' =>'Guinea-Bissau', 'GY' =>'Guyana', 'HK' =>'Hong Kong',
		'HM' =>'Heard Island and McDonald Islands', 'HN' =>'Honduras', 'HR' =>'Croatia',
		'HT' =>'Haiti', 'HU' =>'Hungary', 'ID' =>'Indonesia', 'IE' =>'Ireland',
		'IL' =>'Israel', 'IM' =>'Isle of Man', 'IN' =>'India', 'IO' =>'British Indian Ocean Territory',
		'IQ' =>'Iraq', 'IR' =>'Iran, Islamic Republic of', 'IS' =>'Iceland',
		'IT' =>'Italy', 'JE' =>'Jersey', 'JM' =>'Jamaica', 'JO' =>'Jordan',
		'JP' =>'Japan', 'KE' =>'Kenya', 'KG' =>'Kyrgyzstan', 'KH' =>'Cambodia',
		'KI' =>'Kiribati', 'KM' =>'Comoros', 'KN' =>'Saint Kitts and Nevis',
		'KP' =>'Korea, Democratic People\'s Republic of', 'KR' =>'Korea, Republic of',
		'KW' =>'Kuwait', 'KY' =>'Cayman Islands', 'KZ' =>'Kazakhstan',
		'LA' =>'Lao People\'s Democratic Republic', 'LB' =>'Lebanon',
		'LC' =>'Saint Lucia', 'LI' =>'Liechtenstein', 'LK' =>'Sri Lanka',
		'LR' =>'Liberia', 'LS' =>'Lesotho', 'LT' =>'Lithuania', 'LU' =>'Luxembourg',
		'LV' =>'Latvia', 'LY' =>'Libyan Arab Jamahiriya', 'MA' =>'Morocco',
		'MC' =>'Monaco', 'MD' =>'Moldova, Republic of', 'ME' =>'Montenegro',
		'MF' =>'Saint Martin (French part)', 'MG' =>'Madagascar', 'MH' =>'Marshall Islands',
		'MK' =>'Macedonia, the former Yugoslav Republic of', 'ML' =>'Mali',
		'MM' =>'Myanmar', 'MN' =>'Mongolia', 'MO' =>'Macao', 'MP' =>'Northern Mariana Islands',
		'MQ' =>'Martinique', 'MR' =>'Mauritania', 'MS' =>'Montserrat', 'MT' =>'Malta',
		'MU' =>'Mauritius', 'MV' =>'Maldives', 'MW' =>'Malawi', 'MX' =>'Mexico',
		'MY' =>'Malaysia', 'MZ' =>'Mozambique', 'NA' =>'Namibia', 'NC' =>'New Caledonia',
		'NE' =>'Niger', 'NF' =>'Norfolk Island', 'NG' =>'Nigeria', 'NI' =>'Nicaragua',
		'NL' =>'Netherlands', 'NO' =>'Norway', 'NP' =>'Nepal', 'NR' =>'Nauru', 'NU' =>'Niue',
		'NZ' =>'New Zealand', 'OM' =>'Oman', 'PA' =>'Panama', 'PE' =>'Peru', 'PF' =>'French Polynesia',
		'PG' =>'Papua New Guinea', 'PH' =>'Philippines', 'PK' =>'Pakistan', 'PL' =>'Poland',
		'PM' =>'Saint Pierre and Miquelon', 'PN' =>'Pitcairn', 'PR' =>'Puerto Rico',
		'PS' =>'Palestinian Territory, Occupied', 'PT' =>'Portugal', 'PW' =>'Palau',
		'PY' =>'Paraguay', 'QA' =>'Qatar', 'RE' =>'Reunion', 'RO' =>'Romania',
		'RS' =>'Serbia', 'RU' =>'Russian Federation', 'RW' =>'Rwanda', 'SA' =>'Saudi Arabia',
		'SB' =>'Solomon Islands', 'SC' =>'Seychelles', 'SD' =>'Sudan', 'SE' =>'Sweden',
		'SG' =>'Singapore', 'SH' =>'Saint Helena, Ascension and Tristan da Cunha',
		'SI' =>'Slovenia', 'SJ' =>'Svalbard and Jan Mayen', 'SK' =>'Slovakia',
		'SL' =>'Sierra Leone', 'SM' =>'San Marino', 'SN' =>'Senegal', 'SO' =>'Somalia',
		'SR' =>'Suriname', 'ST' =>'Sao Tome and Principe', 'SV' =>'El Salvador',
		'SY' =>'Syrian Arab Republic', 'SZ' =>'Swaziland', 'TC' =>'Turks and Caicos Islands',
		'TD' =>'Chad', 'TF' =>'French Southern Territories', 'TG' =>'Togo',
		'TH' =>'Thailand', 'TJ' =>'Tajikistan', 'TK' =>'Tokelau', 'TL' =>'Timor-Leste',
		'TM' =>'Turkmenistan', 'TN' =>'Tunisia', 'TO' =>'Tonga', 'TR' =>'Turkey',
		'TT' =>'Trinidad and Tobago', 'TV' =>'Tuvalu', 'TW' =>'Taiwan',
		'TZ' =>'Tanzania, United Republic of', 'UA' =>'Ukraine', 'UG' =>'Uganda',
		'UM' =>'United States Minor Outlying Islands', 'US' =>'United States',
		'UY' =>'Uruguay', 'UZ' =>'Uzbekistan', 'VA' =>'Holy See (Vatican City State)',
		'VC' =>'Saint Vincent and the Grenadines', 'VE' =>'Venezuela, Bolivarian Republic of',
		'VG' =>'Virgin Islands, British', 'VI' =>'Virgin Islands, U.S.', 'VN' =>'Viet Nam',
		'VU' =>'Vanuatu', 'WF' =>'Wallis and Futuna', 'WS' =>'Samoa', 'YE' =>'Yemen',
		'YT' =>'Mayotte', 'ZA' =>'South Africa', 'ZM' =>'Zambia', 'ZW' =>'Zimbabwe'
	);

	public static $states = array();

	public static function decodeCountry($cCode)
	{
		if(array_key_exists($cCode, self::$countries))
		{
			return self::$countries[$cCode];
		}
		else
		{
			return $cCode;
		}
	}

	protected static function genericlist($list, $name, $attribs, $selected, $idTag)
	{
		if(empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';
			foreach($attribs as $key=>$value)
			{
				$temp .= $key.' = "'.$value.'"';
			}
			$attribs = $temp;
		}

		return JHTML::_('select.genericlist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	protected static function genericradiolist($list, $name, $attribs, $selected, $idTag)
	{
		if(empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';
			foreach($attribs as $key=>$value)
			{
				$temp .= $key.' = "'.$value.'"';
			}
			$attribs = $temp;
		}

		return JHTML::_('select.radiolist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	public static function booleanlist( $name, $attribs = null, $selected = null )
	{
		$options = array(
			JHTML::_('select.option','','---'),
			JHTML::_('select.option',  '0', JText::_( 'JNo' ) ),
			JHTML::_('select.option',  '1', JText::_( 'JYes' ) )
		);
		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function countries($selected = null, $id = 'country', $attribs = array())
	{
		// Get the raw list of countries
		$options = array();
		$countries = self::$countries;
		asort($countries);

		// Parse show / hide options
		// -- Initialisation
		$show = array();
		$hide = array();
		// -- parse the show attrib
		if (isset($attribs['show']))
		{
			$show = trim($attribs['show']);
			if (!empty($show))
			{
				$show = explode(',', $show);
			}
			else
			{
				$show = array();
			}
			unset($attribs['show']);
		}
		// -- parse the hide attrib
		if (isset($attribs['hide']))
		{
			$hide = trim($attribs['hide']);
			if (!empty($hide))
			{
				$hide = explode(',', $hide);
			}
			else
			{
				$hide = array();
			}
			unset($attribs['hide']);
		}
		// -- If $show is not empty, filter the countries
		if (count($show))
		{
			$temp = array();
			foreach($show as $key)
			{
				if (array_key_exists($key, $countries))
				{
					$temp[$key] = $countries[$key];
				}
			}
			asort($temp);
			$countries = $temp;
		}
		// -- If $show is empty but $hide is not, filter the countries
		elseif (count($hide))
		{
			$temp = array();
			foreach($countries as $key => $v)
			{
				if (!in_array($key, $hide))
				{
					$temp[$key] = $v;
				}
			}
			asort($temp);
			$countries = $temp;
		}

		foreach($countries as $code => $name)
		{
			$options[] = JHTML::_('select.option', $code, $name );
		}
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function states($selected = null, $id = 'state', $attribs = array())
	{
		$options = array();
		foreach(self::$states as $country => $states) {
			$options[] = JHTML::_('select.option', '<OPTGROUP>', $country);
			foreach($states as $code => $name) {
				$options[] = JHTML::_('select.option', $code, $name );
			}
			$options[] = JHTML::_('select.option', '</OPTGROUP>');
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function usergroups($name = 'usergroups', $selected = '', $attribs = array())
	{
		// Get a database object.
		$db = JFactory::getDBO();

		// Get the user groups from the database.
		$query = $db->getQuery(true);
		$query->select(array(
			$db->qn('a').'.'.$db->qn('id'),
			$db->qn('a').'.'.$db->qn('title'),
			$db->qn('a').'.'.$db->qn('parent_id').' AS '.$db->qn('parent'),
			'COUNT(DISTINCT '.$db->qn('b').'.'.$db->qn('id').') AS '.$db->qn('level')
		))->from($db->qn('#__usergroups').' AS '.$db->qn('a'))
		->join('left', $db->qn('#__usergroups').' AS '.$db->qn('b').' ON '.
			$db->qn('a').'.'.$db->qn('lft').' > '.$db->qn('b').'.'.$db->qn('lft').
			' AND '.$db->qn('a').'.'.$db->qn('rgt').' < '.$db->qn('b').'.'.$db->qn('rgt')
		)->group(array(
			$db->qn('a').'.'.$db->qn('id')
		))->order(array(
			$db->qn('a').'.'.$db->qn('lft').' ASC'
		))
		;
		$db->setQuery($query);
		$groups = $db->loadObjectList();

		$options = array();
		$options[] = JHTML::_('select.option', '', '- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');

		foreach ($groups as $group) {
			$options[] = JHTML::_('select.option', $group->id, JText::_($group->title));
		}

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function published($selected = null, $id = 'enabled', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option',null,'- '.JText::_('COM_AKEEBASUBS_COMMON_SELECTSTATE').' -');
		$options[] = JHTML::_('select.option',0,JText::_('JUNPUBLISHED'));
		$options[] = JHTML::_('select.option',1,JText::_('JPUBLISHED'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function languages($selected = null, $id = 'language', $attribs = array() )
	{
		JLoader::import('joomla.language.helper');
		$languages = JLanguageHelper::getLanguages('lang_code');
		$options = array();
		$options[] = JHTML::_('select.option','*',JText::_('JALL_LANGUAGE'));
		if(!empty($languages)) foreach($languages as $key => $lang)
		{
			$options[] = JHTML::_('select.option',$key,$lang->title);
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function subscriptionlevels($selected = null, $id = 'akeebasubs_level_id', $attribs = array())
	{
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$items = $model->savestate(0)->limit(0)->limitstart(0)->getItemList();

		$options = array();

		if(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->akeebasubs_level_id, $item->title);
		}

	   array_unshift($options, JHTML::_('select.option',0,'- '.JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LEVEL').' -'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function paystates($selected = null, $id = 'state', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE').' -');

		$types = array('N','P','C','X');
		foreach($types as $type) $options[] = JHTML::_('select.option',$type,JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$type));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Drop down list of payment states
	 */
	public static function coupontypes($name = 'type', $selected = 'value', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		$options[] = JHTML::_('select.option','value',JText::_('COM_AKEEBASUBS_COUPON_TYPE_VALUE'));
		$options[] = JHTML::_('select.option','percent',JText::_('COM_AKEEBASUBS_COUPON_TYPE_PERCENT'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Shows a listbox with defined subscription levels
	 */
	public static function levels($name = 'level', $selected = '', $attribs = array())
	{
		$list = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->savestate(0)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->limit(0)
			->offset(0)
			->getList();

		$options   = array();

		$include_none = false;
		$include_all = false;
		$include_clear = false;
		if(array_key_exists('include_none', $attribs)) {
			$include_none = $attribs['include_none'];
			unset($attribs['include_none']);
		}
		if(array_key_exists('include_all', $attribs)) {
			$include_all = $attribs['include_all'];
			unset($attribs['include_all']);
		}
		if(array_key_exists('include_clear', $attribs)) {
			$include_clear = $attribs['include_clear'];
			unset($attribs['include_clear']);
		}

		if($include_none) {
			$options[] = JHTML::_('select.option','-1',JText::_('COM_AKEEBASUBS_COMMON_SELECTLEVEL_NONE'));
		}
		if($include_all) {
			$options[] = JHTML::_('select.option','0',JText::_('COM_AKEEBASUBS_COMMON_SELECTLEVEL_ALL'));
		}
		if($include_clear || (!$include_none && !$include_all)) {
			$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		}

		foreach($list as $item) {
			$options[] = JHTML::_('select.option',$item->akeebasubs_level_id,$item->title);
		}

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function formatCountry($country = '')
	{
 		if(array_key_exists($country, self::$countries)) {
 			$name = self::$countries[$country];
 		} else {
 			$name = '&mdash;';
 		}

 		return $name;
	}

	public static function formatState($state)
	{
		$name = '&mdash;';

		foreach(self::$states as $country => $states) {
			if(array_key_exists($state, $states)) $name = $states[$state];
		}

 		return $name;
	}

	public static function formatLevel($id)
	{
		static $levels;

		if(empty($levels)) {
			$rawlevels = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->filter_order('ordering')
				->filter_order_Dir('ASC')
				->limit(0)
				->offset(0)
				->getList();

			$levels = array();

			if (!empty($rawlevels))
			{
				foreach ($rawlevels as $rawlevel)
				{
					$levels[$rawlevel->akeebasubs_level_id] = $rawlevel->title;
				}
			}
		}

		if(array_key_exists($id, $levels)) {
			return $levels[$id];
		} else {
			return '&mdash;&mdash;&mdash;';
		}
	}

	/**
 	 * Drop down list of payment methods (active payment plugins)
 	 */
 	public static function paymentmethods($name = 'paymentmethod', $selected = '', $attribs = array())
 	{
		// Get the list of payment plugins
 		$plugins = FOFModel::getTmpInstance('Subscribes','AkeebasubsModel')
			->getPaymentPlugins();

		// Load the component parameters helper
		if(!class_exists('AkeebasubsHelperCparams')) {
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		}

		// Initialise parameters
		$level_id = isset($attribs['level_id']) ? $attribs['level_id'] : 0;
		$always_dropdown = isset($attribs['always_dropdown']) ? 1 : 0;
		$default_option = isset($attribs['default_option']) ? 1 : 0;

		// Per-level payment option filtering
		if ($level_id > 0)
		{
			// Load the subscription level
			$level = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->getItem($level_id);
			$payment_plugins = $level->payment_plugins;
			if (!empty($payment_plugins))
			{
				$payment_plugins = explode(',', $payment_plugins);
				$temp = array();
				foreach ($plugins as $plugin)
				{
					if (in_array($plugin->name, $payment_plugins))
					{
						$temp[] = $plugin;
					}
				}
				if (!empty($temp))
				{
					$plugins = $temp;
				}
			}
		}

		$returnRawList = false;
		if (isset($attribs['return_raw_list']))
		{
			$returnRawList = $attribs['return_raw_list'];
			unset($attribs['return_raw_list']);
		}

		if ($returnRawList)
		{
			return $plugins;
		}

		// Determine how to render the payment method (drop-down or radio box)
		if((AkeebasubsHelperCparams::getParam('useppimages', 1) > 0) && !$always_dropdown) {
			// Show images instead of a drop-down
			$options = array();
			foreach($plugins as $plugin) {
				if(!isset($plugin->image)) {
					$plugin->image = '';
				} else {
					$plugin->image = trim($plugin->image);
				}
				if(empty($plugin->image)) {
					$plugin->image = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/credit_card_logos.gif';
				}
				$innerHTML = '<img border="0" src="'.$plugin->image.'" /> ';
				if(AkeebasubsHelperCparams::getParam('useppimages', 1) == 2) {
					$innerHTML .= '<span class="pull-left">'.$plugin->title.'</span>';
				}
				$options[] = array(
					'value'		=> $plugin->name,
					'label'		=> $innerHTML,
				);
				// In case we don't have a default selection, select the first item on the list
				if(empty($selected)) {
					$selected = $plugin->name;
				}
			}
			$html = '<span class="akeebasubs-paymentmethod-images">';
			if(!empty($options)) {
				foreach($options as $o) {
					$html .= '<label class="radio input-xxlarge"><input type="radio" name="'.$name.'" id="'.
							$name.$o['value'].'" value="'.$o['value'].'" ';
					if($o['value'] == $selected) $html.='checked="checked"';
					$html.='/>'.$o['label'].'</label>';
				}
			}
			//$html .= self::genericradiolist($options, $name, $attribs, $selected, $name);
			$html .= '</span>';
			return $html;
		} else {
			// Show drop-down
			$options = array();

			if ($default_option)
			{
				$options[] = JHTML::_('select.option', '', JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PAYMENT_PLUGINS_UNSELECT'));
				$selected = explode(',', $selected);
			}

			foreach($plugins as $plugin)
			{
				$options[] = JHTML::_('select.option', $plugin->name, $plugin->title);
			}

			return self::genericlist($options, $name, $attribs, $selected, $name);
		}
 	}

	public static function affiliates($selected = null, $id = 'akeebasubs_affiliate_id', $attribs = array())
	{
		$model = FOFModel::getTmpInstance('Affiliates','AkeebasubsModel');
		$items = $model->savestate(0)->limit(0)->limitstart(0)->getItemList();

		$options = array();

		if(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->akeebasubs_affiliate_id, $item->username);
		}

		array_unshift($options, JHTML::_('select.option',0,'- '.JText::_('COM_AKEEBASUBS_COMMON_AFFILIATE').' -'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Drop down list of payment states
	 */
	public static function discountmodes($name = 'discountmode', $selected = '', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT').' -');
		$options[] = JHTML::_('select.option','none',JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_NONE'));
		$options[] = JHTML::_('select.option','coupon',JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_COUPON'));
		$options[] = JHTML::_('select.option','upgrade',JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_UPGRADE'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of upgrade types
	 */
	public static function upgradetypes($name = 'type', $selected = 'value', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		$options[] = JHTML::_('select.option','value',JText::_('COM_AKEEBASUBS_UPGRADE_TYPE_VALUE'));
		$options[] = JHTML::_('select.option','percent',JText::_('COM_AKEEBASUBS_UPGRADE_TYPE_PERCENT'));
		$options[] = JHTML::_('select.option','lastpercent',JText::_('COM_AKEEBASUBS_UPGRADE_TYPE_LASTPERCENT'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of level groups
	 */
	public static function levelgroups($selected = null, $id = 'akeebasubs_levelgroup_id', $attribs = array())
	{
		$model = FOFModel::getTmpInstance('Levelgroups','AkeebasubsModel');
		$items = $model->savestate(0)->limit(0)->limitstart(0)->getItemList();

		$options = array();

		if(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->akeebasubs_levelgroup_id, $item->title);
		}

	   array_unshift($options, JHTML::_('select.option',0,JText::_('COM_AKEEBASUBS_SELECT_LEVELGROUP')));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Drop down list of custom field types
	 */
	public static function fieldtypes($name = 'type', $selected = 'text', $attribs = array())
	{
		$fieldTypes = array();

		JLoader::import('joomla.filesystem.folder');
		$basepath = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/customfields';
		$files = JFolder::files($basepath, '.php');
		foreach($files as $file)
		{
			if ($file === 'abstract.php')
			{
				continue;
			}

			require_once $basepath.'/'.$file;
			$type = substr($file, 0, -4);
			$class = 'AkeebasubsCustomField' . ucfirst($type);
			if (class_exists($class))
			{
				$fieldTypes[] = $type;
			}
		}

		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		foreach ($fieldTypes as $type)
		{
			$options[] = JHTML::_('select.option', $type, JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TYPE_'.strtoupper($type)));
		}

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of when to show custom fields
	 */
	public static function fieldshow($name = 'show', $selected = 'all', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		$options[] = JHTML::_('select.option','all',JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_SHOW_ALL'));
		$options[] = JHTML::_('select.option','level',JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_SHOW_LEVEL'));
		$options[] = JHTML::_('select.option','notlevel',JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_SHOW_NOTLEVEL'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	/**
	 * Drop down list of subscription level relation modes
	 */
	public static function relationmode($name = 'mode', $selected = 'rules', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		$options[] = JHTML::_('select.option','rules',JText::_('COM_AKEEBASUBS_RELATIONS_MODE_RULES'));
		$options[] = JHTML::_('select.option','fixed',JText::_('COM_AKEEBASUBS_RELATIONS_MODE_FIXED'));
		$options[] = JHTML::_('select.option','flexi',JText::_('COM_AKEEBASUBS_RELATIONS_MODE_FLEXI'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function flexiperioduoms($name = 'flex_uom', $selected = 'rules', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		$options[] = JHTML::_('select.option','d',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_UOM_D'));
		$options[] = JHTML::_('select.option','w',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_UOM_W'));
		$options[] = JHTML::_('select.option','m',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_UOM_M'));
		$options[] = JHTML::_('select.option','y',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_UOM_Y'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function flexitimecalc($name = 'flex_timecalculation', $selected = 'current', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		$options[] = JHTML::_('select.option','current',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMECALCULATION_CURRENT'));
		$options[] = JHTML::_('select.option','future',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMECALCULATION_FUTURE'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function flexirounding($name = 'flex_rounding', $selected = 'round', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		$options[] = JHTML::_('select.option','floor',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMEROUNDING_FLOOR'));
		$options[] = JHTML::_('select.option','ceil',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMEROUNDING_CEIL'));
		$options[] = JHTML::_('select.option','round',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMEROUNDING_ROUND'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function flexiexpiration($name = 'expiration', $selected = 'replace', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		$options[] = JHTML::_('select.option','replace',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_EXPIRATION_REPLACE'));
		$options[] = JHTML::_('select.option','after',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_EXPIRATION_AFTER'));
		$options[] = JHTML::_('select.option','overlap',JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_EXPIRATION_OVERLAP'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function invoiceextensions($name = 'extension', $selected = '', $attribs = array())
	{
		$options = FOFModel::getTmpInstance('Invoices', 'AkeebasubsModel')
			->getExtensions(1);

		$option = JHtml::_('select.option', '', '- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		array_unshift($options, $option);

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function viesregistered($name = 'viesregistered', $selected = 0, $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option', '0', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER_VIESREGISTERED_NO'));
		$options[] = JHTML::_('select.option', '1', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER_VIESREGISTERED_YES'));
		$options[] = JHTML::_('select.option', '2', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER_VIESREGISTERED_FORCEYES'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function invoicetemplateisbusines($name = 'isbusiness', $selected = -1, $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option', '-1', JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_ISBUSINESS_INDIFFERENT'));
		$options[] = JHTML::_('select.option', '0',  JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_ISBUSINESS_PERSONAL'));
		$options[] = JHTML::_('select.option', '1',  JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_ISBUSINESS_BUSINESS'));

		return self::genericlist($options, $name, $attribs, $selected, $name);
	}
}

// Load the states from the database
function akeebasubsHelperSelect_init()
{
	$model = FOFModel::getTmpInstance('States', 'AkeebasubsModel');
	$rawstates = $model->enabled(1)->getItemList(true);
	$states = array();
	$current_country = '';
	$current_country_name = 'N/A';
	$current_states = array('' => 'N/A');
	foreach($rawstates as $rawstate)
	{
		if($rawstate->country != $current_country)
		{
			if (!empty($current_country_name))
			{
				$states[$current_country_name] = $current_states;
				$current_states = array();
				$current_country = '';
				$current_country_name = '';
			}

			if (empty($rawstate->country) || empty($rawstate->state) || empty($rawstate->label))
			{
				continue;
			}

			$current_country = $rawstate->country;
			$current_country_name = AkeebasubsHelperSelect::$countries[$current_country];
		}

		$current_states[$rawstate->state] = $rawstate->label;
	}

	if (!empty($current_country_name))
	{
		$states[$current_country_name] = $current_states;
	}

	AkeebasubsHelperSelect::$states = $states;
}

akeebasubsHelperSelect_init();
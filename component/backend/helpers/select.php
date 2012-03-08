<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
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
		'TT' =>'Trinidad and Tobago', 'TV' =>'Tuvalu', 'TW' =>'Taiwan, Province of China',
		'TZ' =>'Tanzania, United Republic of', 'UA' =>'Ukraine', 'UG' =>'Uganda',
		'UM' =>'United States Minor Outlying Islands', 'US' =>'United States',
		'UY' =>'Uruguay', 'UZ' =>'Uzbekistan', 'VA' =>'Holy See (Vatican City State)',
		'VC' =>'Saint Vincent and the Grenadines', 'VE' =>'Venezuela, Bolivarian Republic of',
		'VG' =>'Virgin Islands, British', 'VI' =>'Virgin Islands, U.S.', 'VN' =>'Viet Nam',
		'VU' =>'Vanuatu', 'WF' =>'Wallis and Futuna', 'WS' =>'Samoa', 'YE' =>'Yemen',
		'YT' =>'Mayotte', 'ZA' =>'South Africa', 'ZM' =>'Zambia', 'ZW' =>'Zimbabwe'
	);
	
	public static $states = array(
		'N/A' => array('' => 'N/A'),
		'USA' => array(
			'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona',
			'AR' => 'Arkansas',	'CA' => 'California', 'CO' => 'Colorado',
			'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District of Columbia',
			'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',	'ID' => 'Idaho',
			'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
			'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts',
			'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana',
			'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
			'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota',
			'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
			'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
			'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin',
			'WY' => 'Wyoming'
			),
		'Canada' => array(
			'AB' => 'Alberta', 'BC' => 'British Columbia',
			'MB' => 'Manitoba', 'NB' => 'New Brunswick', 'NL' => 'Newfoundland and Labrador',
			'NT' => 'Northwest Territories', 'NS' => 'Nova Scotia', 'NU' => 'Nunavut', 'ON' => 'Ontario',
			'PE' => 'Prince Edward Island', 'QC' => 'Quebec', 'SK' => 'Saskatchewan', 'YT' => 'Yukon'
			),
		'Australia'	=> array(
			'ACT' => 'Australian Capital Territory', 'NSW' => 'New South Wales',
			'AU-NT' => 'Northern Terittory', 'QLD' => 'Queensland', 'AU-SA' => 'South Australia',
			'TAS' => 'Tasmania', 'VIC' => 'Victoria', 'AU-WA' => 'Western Australia'
		)
	);

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

	public static function booleanlist( $name, $attribs = null, $selected = null )
	{
		$options = array(
			JHTML::_('select.option','','---'),
			JHTML::_('select.option',  '0', JText::_( 'No' ) ),
			JHTML::_('select.option',  '1', JText::_( 'Yes' ) )
		);
		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function countries($selected = null, $id = 'country', $attribs = array())
	{
		$options = array();
		$countries = self::$countries;
		asort($countries);
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
		$query = FOFQueryAbstract::getNew($db);
		$query->select(array(
			$db->nameQuote('a').'.'.$db->nameQuote('id'),
			$db->nameQuote('a').'.'.$db->nameQuote('title'),
			$db->nameQuote('a').'.'.$db->nameQuote('parent_id').' AS '.$db->nameQuote('parent'),
			'COUNT(DISTINCT '.$db->nameQuote('b').'.'.$db->nameQuote('id').') AS '.$db->nameQuote('level')
		))->from($db->nameQuote('#__usergroups').' AS '.$db->nameQuote('a'))
		->join('left', $db->nameQuote('#__usergroups').' AS '.$db->nameQuote('b').' ON '.
			$db->nameQuote('a').'.'.$db->nameQuote('lft').' > '.$db->nameQuote('b').'.'.$db->nameQuote('lft').
			' AND '.$db->nameQuote('a').'.'.$db->nameQuote('rgt').' < '.$db->nameQuote('b').'.'.$db->nameQuote('rgt')
		)->group(array(
			$db->nameQuote('a').'.'.$db->nameQuote('id')
		))->order(array(
			$db->nameQuote('a').'.'.$db->nameQuote('lft').' ASC'
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
		$options[] = JHTML::_('select.option',0,JText::_((version_compare(JVERSION, '1.6.0', 'ge')?'J':'').'UNPUBLISHED'));
		$options[] = JHTML::_('select.option',1,JText::_((version_compare(JVERSION, '1.6.0', 'ge')?'J':'').'PUBLISHED'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
	
	public static function languages($selected = null, $id = 'language', $attribs = array() )
	{
		jimport('joomla.language.helper');
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
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -');
		
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
			$levels = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->filter_order('ordering')
				->filter_order_Dir('ASC')
				->limit(0)
				->offset(0)
				->getList();
		}
		
		if(array_key_exists($id, $levels)) {
			return $levels[$id]->title;
		} else {
			return '&mdash;&mdash;&mdash;';
		}
	}
	
	/**
 	 * Drop down list of payment methods (active payment plugins)
 	 */
 	public static function paymentmethods($name = 'paymentmethod', $selected = '', $attribs = array())
 	{	
 		$plugins = FOFModel::getTmpInstance('Subscribes','AkeebasubsModel')
			->getPaymentPlugins();
 		
		$options = array();
		foreach($plugins as $plugin) {
			$options[] = JHTML::_('select.option',$plugin->name,$plugin->title);
 		}
 		
 		return self::genericlist($options, $name, $attribs, $selected, $name);
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
}
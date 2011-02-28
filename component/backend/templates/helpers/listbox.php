<?php
class ComAkeebasubsTemplateHelperListbox extends ComDefaultTemplateHelperListbox
{
	public static $countries = array(
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
		'' => 'N/A', 'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
		'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
		'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
		'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
		'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts',
		'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana',
		'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
		'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota',
		'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
		'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
		'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin',
		'WY' => 'Wyoming', 'AB' => 'Alberta', 'BC' => 'British Columbia',
		'MB' => 'Manitoba', 'NB' => 'New Brunswick', 'NL' => 'Newfoundland and Labrador',
		'NT' => 'Northwest Territories', 'NS' => 'Nova Scotia', 'NU' => 'Nunavut', 'ON' => 'Ontario',
		'PE' => 'Prince Edward Island', 'QC' => 'Quebec', 'SK' => 'Saskatchewan', 'YT' => 'Yukon'
	);
	
    public function images($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'extensions' => 'bmp|gif|jpg|png',
            'directory'  => KRequest::root()->path.'/images/stories/',
            'attribs'    => array('id' => $config->name)
        ));
            
        $base = KRequest::url()->setPath($config->directory)->get(KHttpUri::PART_BASE);
               
		jimport( 'joomla.filesystem.folder' );
		$files  = JFolder::files( str_replace( KRequest::root()->path, '', JPATH_ROOT).DS.$config->directory );
		$options[]	= $this->option(array('text' => '- '.JText::_( 'Select image').' -'));
			
		foreach ($files as $file) 
		{
            if (preg_match("#".$config->extensions."#i", $file)) 
            {
				$options[] 	= $this->option(array('text' => $file, 'value' => $file));
				$preload[]	= $base.$file;
			}
		}

		if (!$config->javascript) 
		{
			$config->javascript = "
			new Asset.images(".json_encode($preload).");
			window.addEvent('domready', function(){
				$('".$config->name."').addEvent('change', function(){
					$('".$config->name."-preview').setProperty('src', '" .$base. "' + this.value);
				});
			});
			";
			
			KFactory::get('lib.joomla.document')->addScriptDeclaration($config->javascript);
		}

		$list = $this->optionlist(array(
			'options'   => $options,
			'name'      => $config->name,
			'selected'  => $config->{$config->name},
			'attribs'   => $config->attribs
		));

		return $list;
    }

	/**
	 * Shows a listbox with defined subscription levels
	 */
	public function levels($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'model'		=> 'levels',
			'name' 		=> 'level',
			'value'		=> 'id',
			'text'		=> 'title',
			'deselect'	=> true
		));
		
		return parent::_listbox($config);
	}
	
	public function paystates($config = array())
 	{
		$config = new KConfig($config);
		$config->append(array(
			'name'		=> 'state',
			'attribs'	=> array(),
			'deselect'	=> false,
			'selected'  => 'N'
		));
		
 		$options  = array();
 		
 		if($config->deselect) {
			$options[] =  $this->option(array('text' => '- '.JText::_( 'Select' ).' -'));
 		}
		
		$options[] = $this->option(array('text' => JText::_( 'COM_AKEEBASUBS_SUBSCRIPTION_STATE_N' ), 'value' => 'N' ));
		$options[] = $this->option(array('text' => JText::_( 'COM_AKEEBASUBS_SUBSCRIPTION_STATE_P' ), 'value' => 'P' ));
		$options[] = $this->option(array('text' => JText::_( 'COM_AKEEBASUBS_SUBSCRIPTION_STATE_C' ), 'value' => 'C' ));
		$options[] = $this->option(array('text' => JText::_( 'COM_AKEEBASUBS_SUBSCRIPTION_STATE_X' ), 'value' => 'X' ));
				
		//Add the options to the config object
		$config->options = $options;
		
		return $this->optionlist($config);
 	}

 	public function countries($config = array())
 	{
		$config = new KConfig($config);
		$config->append(array(
			'name'		=> 'country',
			'attribs'	=> array(),
			'deselect'	=> true,
			'selected'  => ''
		));
		
 		$options  = array();
 		
 		if($config->deselect) {
			$options[] =  $this->option(array('text' => '- '.JText::_( 'Select' ).' -'));
 		}
		
 		asort(self::$countries);
 		foreach(self::$countries as $code => $name) {
 			$options[] = $this->option(array('text' => $name, 'value' => $code ));
 		}
				
		//Add the options to the config object
		$config->options = $options;
		
		return $this->optionlist($config);
 	}
 	
 	public function states($config = array())
 	{
		$config = new KConfig($config);
		$config->append(array(
			'name'		=> 'state',
			'attribs'	=> array(),
			'deselect'	=> true,
			'selected'  => ''
		));
		
 		$options  = array();
 		
 		foreach(self::$states as $code => $name) {
 			$options[] = $this->option(array('text' => $name, 'value' => $code ));
 		}
				
		//Add the options to the config object
		$config->options = $options;
		
		return $this->optionlist($config);
 	}
 	
	public function formatCountry($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'country'  	 => ''
 		));
 		
 		if(array_key_exists($config->country, self::$countries)) {
 			$name = self::$countries[$config->country];
 		} else {
 			$name = '&mdash;';
 		}
 		
 		return $name; 
	}
	
	public function formatState($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'state'  	 => ''
 		));
 		
 		if(array_key_exists($config->state, self::$states)) {
 			$name = self::$states[$config->state];
 		} else {
 			$name = '&mdash;';
 		}
 		
 		return $name; 
	}	
}
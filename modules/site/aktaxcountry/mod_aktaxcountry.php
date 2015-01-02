<?php
/**
 * @package      akeebasubs
 * @copyright    Copyright (c)2010-2015 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license      GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 * @version      $Id$
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// no direct access
defined('_JEXEC') or die;

// Load F0F
if (!defined('F0F_INCLUDED') || !class_exists('F0FForm', true))
{
	include_once JPATH_LIBRARIES . '/f0f/include.php';
}

if (!defined('F0F_INCLUDED') || !class_exists('F0FForm', true))
{
	return;
}

// Load dependencies
if (!class_exists('AkeebasubsHelperEuVATInfo'))
{
	require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/euvatinfo.php';
}

if (!class_exists('AkeebasubsHelperSelect'))
{
	require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/select.php';
}

// Load the language files
$lang = JFactory::getLanguage();
$lang->load('mod_aktaxcountry', JPATH_SITE, 'en-GB', true);
$lang->load('mod_aktaxcountry', JPATH_SITE, null, true);
$lang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
$lang->load('com_akeebasubs', JPATH_SITE, null, true);

// Get a list of options
$countries = $params->get('countries', 'US');
$eucountries = $params->get('eucountries', 1);
$eubusiness = $params->get('eubusiness', 1);
$international = $params->get('international', 1);

$countryCodes = array();

if (!empty($countries))
{
	$countryCodes = explode(',', $countries);
	$countryCodes = array_map(function($x) { return trim($x); }, $countryCodes);
}

if ($eucountries)
{
	$additionalCountries = AkeebasubsHelperEuVATInfo::$EuropeanUnionVATInformation;

	foreach ($additionalCountries as $code => $info)
	{
		if (!in_array($code, $countryCodes))
		{
			$countryCodes[] = $code;
		}
	}
}

if ($eubusiness && !in_array('EU-VIES', $countryCodes))
{
	$countryCodes[] = 'EU-VIES';
}

if ($international && !in_array('XX', $countryCodes))
{
	$countryCodes[] = 'XX';
}

$options = array();
$countryNames = AkeebasubsHelperSelect::getCountries();

foreach ($countryCodes as $code)
{
	switch ($code)
	{
		case 'XX':
			$options[] = JHtml::_('select.option', $code, JText::_('MOD_AKTAXCOUNTRY_LBL_INTERNATIONAL'));
			break;

		case 'EU-VIES':
			$options[] = JHtml::_('select.option', $code, JText::_('MOD_AKTAXCOUNTRY_LBL_EUBUSINES'));
			break;

		default:
			if (array_key_exists($code, $countryNames))
			{
				$options[] = JHtml::_('select.option', $code, $countryNames[$code]);
			}
			else
			{
				$options[] = JHtml::_('select.option', $code, $code);
			}
			break;
	}
}

// Try to guess the default value
$default_option = JFactory::getSession()->get('country', null, 'mod_aktaxcountry');

if (empty($default_option))
{
	$taxHelper = F0FModel::getTmpInstance('Taxhelper', 'AkeebasubsModel');
	$taxparams = $taxHelper->getTaxDefiningParameters;
	$default_option = $taxparams['country'];

	if ($params['vies'] && AkeebasubsHelperEuVATInfo::isEUVATCountry($params['country']))
	{
		$default_option = 'EU-VIES';
	}
}

if (empty($default_option))
{
	$default_option = 'XX';
}

// Load the layout file
require_once JModuleHelper::getLayoutPath($module->module);
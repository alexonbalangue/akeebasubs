<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelTaxconfigs extends FOFModel
{
	public function getStateVars()
	{
		return (object)array(
			'novatcalc'		=> $this->getState('novatcalc', 0, 'int'),
			'akeebasubs_level_id'
							=> $this->getState('akeebasubs_level_id', '0', 'cmd'),
			'country'		=> $this->getState('country', '', 'cmd'),
			'taxrate'		=> $this->getState('taxrate', 0.0, 'float'),
			'viesreg'		=> $this->getState('viesreg', 0, 'int'),
			'showvat'		=> $this->getState('showvat', 0, 'int'),
		);
	}

	/**
	 * Removes all tax rules
	 */
	public function clearTaxRules()
	{
		$state = $this->getStateVars();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->qn('#__akeebasubs_taxrules'))
			->where($db->qn('akeebasubs_level_id') . '=' . $db->q($state->akeebasubs_level_id));
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Creates new tax rules based on the user preferences
	 */
	public function createTaxRules()
	{
		// Get the state variables
		$params = $this->getStateVars();

		// Should I proceed?
		if($params->novatcalc) {
			// User opted out from VAT configuration
			return;
		}

		// Is this an EU country?
		$euCountries = array(
			'BE', 'BG', 'CZ', 'DK', 'DE', 'EE', 'GR', 'ES', 'FR', 'IE',
			'IT', 'CY', 'LV', 'LT', 'LU', 'HU', 'MT', 'NL', 'AT', 'PL',
			'PT', 'RO', 'SI', 'SK', 'FI', 'SE', 'GB'
		);
		$inEU = in_array($params->country, $euCountries);

		// Prototype for tax rules
		$data = array(
			'akeebasubs_level_id'
						=> $params->akeebasubs_level_id,
			'country'	=> '',
			'state'		=> '',
			'city'		=> '',
			'vies'		=> 0,
			'taxrate'	=> 0,
			'enabled'	=> 1,
			'ordering'	=> 0,
		);
		$ordering = 0;

		if(!$inEU && !$params->viesreg) {
			// Non-EU business, without an EU VAT ID
			// A. All countries, with or without VIES registration, taxrate%
			$data['taxrate'] = $params->taxrate;
			$data['ordering'] = ++$ordering;
			FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
			$data['vies'] = 1;
			$data['ordering'] = ++$ordering;
			FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
		} elseif($params->viesreg) {
			// EU VIES-registered business, or non-EU business with an EU VAT ID

			// A. All countries, with or without VIES registration, 0%
			$data['ordering'] = ++$ordering;
			FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
			$data['vies'] = 1;
			$data['ordering'] = ++$ordering;
			FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
			// B. My country, with or without VIES registration, taxrate%
			$data['taxrate'] = $params->taxrate;
			$data['country'] = $params->country;

			$data['vies'] = 0;
			$data['ordering'] = ++$ordering;
			FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);

			$data['vies'] = 1;
			$data['ordering'] = ++$ordering;
			FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);

			// C. All other EU countries, without VIES registration, taxrate%
			$data['vies'] = 0;
			foreach($euCountries as $country) {
				if($country == $params->country) continue;
				$data['country'] = $country;
				$data['ordering'] = ++$ordering;
				FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
			}
		} else {
			// EU non-VIES-registered business
			// A. All countries, with or without VIES registration, 0%
			$data['ordering'] = ++$ordering;
			FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
			$data['vies'] = 1;
			$data['ordering'] = ++$ordering;
			FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
			// B. All EU countries, with or without VIES registration, taxrate%
			$data['taxrate'] = $params->taxrate;
			foreach($euCountries as $country) {
				$data['country'] = $country;
				$data['vies'] = 0;
				$data['ordering'] = ++$ordering;
				FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
				$data['vies'] = 1;
				$data['ordering'] = ++$ordering;
				FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')->setId(0)->save($data);
			}
		}
	}

	public function applyComponentConfiguration()
	{
		// Fetch the component parameters
		$db = JFactory::getDbo();
		$sql = $db->getQuery(true)
			->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q('com_akeebasubs'));
		$db->setQuery($sql);
		$rawparams = $db->loadResult();
		$params = new JRegistry();
		$params->loadString($rawparams, 'JSON');

		// Set the parameter
		$state = $this->getStateVars();
		if($state->showvat) {
			$params->set('vatrate', $state->taxrate);
		} else {
			$params->set('vatrate', 0);
		}

		// Save the component parameters
		$data = $params->toString('JSON');
		$sql = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params').' = '.$db->q($data))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q('com_akeebasubs'));

		$db->setQuery($sql);
		$db->execute();
	}
}
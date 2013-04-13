<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableLevel extends FOFTable
{
	public function check() {
		$result = true;

		// Require a title
		if(empty($this->title)) {
			$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_TITLE'));
			$result = false;
		}

		// Make sure the title is unique
		$existingItems = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->title($this->title)
			->getList(true);
		if(!empty($existingItems)) {
			$count = 0;
			$k = $this->getKeyName();
			foreach($existingItems as $item) {
				if($item->$k != $this->$k) $count++;
			}
			if($count) {
				$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_TITLEUNIQUE'));
				$result = false;
			}
		}

		// Create a new or sanitise an existing slug
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/filter.php';
		if(empty($this->slug)) {
			// Auto-fetch a slug
			$this->slug = AkeebasubsHelperFilter::toSlug($this->title);
		} else {
			// Make sure nobody adds crap characters to the slug
			$this->slug = AkeebasubsHelperFilter::toSlug($this->slug);
		}

		// Look for a similar slug
		$existingItems = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->slug($this->slug)
			->getList(true);

		if(!empty($existingItems)) {
			$count = 0;
			$k = $this->getKeyName();
			foreach($existingItems as $item) {
				if($item->$k != $this->$k) $count++;
			}
			if($count) {
				$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_SLUGUNIQUE'));
				$result = false;
			}
		}

		// Do we have an image?
		if(empty($this->image)) {
			$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_IMAGE'));
			$result = false;
		}

		// Check the fixed expiration date and make sure it's in the future
		$nullDate = JFactory::getDbo()->getNullDate();
		if(!empty($this->fixed_date) && !($this->fixed_date == $nullDate))
		{
			$jNow = JFactory::getDate();
			$jFixed = JFactory::getDate($this->fixed_date);

			if($jNow->toUnix() > $jFixed->toUnix())
			{
				$this->fixed_date = $nullDate;
			}
		}

		// Is the duration less than a day and this is not a forever or a fixed date subscription?
		if($this->forever)
		{
			$this->duration = 0;
		}
		elseif(!empty($this->fixed_date) && !($this->fixed_date == $nullDate))
		{
			// We only want the duration to be a positive number or zero
			if($this->duration < 0)
			{
				$this->duration = 0;
			}
		}
		elseif($this->duration < 1)
		{
			$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_LENGTH'));
			$result = false;
		}

		// Serialise params
		if(is_array($this->params)) {
			if(!empty($this->params)) {
				$this->params = json_encode($this->params);
			}
		}
		if(is_null($this->params) || empty($this->params)) {
			$this->params = '';
		}

		// Normalise plugins
		if (!empty($this->payment_plugins))
		{
			if (is_array($this->payment_plugins))
			{
				$payment_plugins = $this->payment_plugins;
			}
			else
			{
				$payment_plugins = explode(',', $this->payment_plugins);
			}

			if (in_array('', $payment_plugins))
			{
				$this->payment_plugins = '';
			}
			else
			{
				$this->payment_plugins = implode(',', $payment_plugins);
			}
		}
		else
		{
			$this->payment_plugins = '';
		}

		return $result;
	}

	function delete( $oid=null )
	{
		$joins = array(
			array(
				'label'		=> 'subscriptions',			// Used to construct the error text
				'name'		=> '#__akeebasubs_subscriptions', // Foreign table
				'idfield'	=> 'akeebasubs_level_id',	// Field name on this table
				'joinfield'	=> 'akeebasubs_level_id',	// Foreign table field
				'idalias'	=> 'subscription_id',		// Used in the query
			)
		);
		if($this->canDelete($oid, $joins))
		{
			return parent::delete($oid);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Unserialises the parameters when loading the record
	 * @param AkeebasubsTableLevel $result
	 * @return bool
	 */
	public function onAfterLoad(&$result) {
		// Convert params to an array
		if(!is_array($this->params)) {
			if(!empty($this->params)) {
				$this->params = json_decode($this->params, true);
			}
		}
		if(is_null($this->params) || empty($this->params)) {
			$this->params = array();
		}

		return parent::onAfterLoad($result);
	}
}
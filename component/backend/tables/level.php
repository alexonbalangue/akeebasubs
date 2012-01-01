<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
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
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/filter.php';
		
		// Auto-fetch a slug
		if(empty($this->slug)) {
			$this->slug = AkeebasubsHelperFilter::toSlug($this->title);
		}
		
		// Make sure nobody adds crap characters to the slug
		$this->slug = AkeebasubsHelperFilter::toSlug($this->slug);
		
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
		
		// Is the duration less than a day?
		if($this->duration < 1) {
			$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_LENGTH'));
			$result = false;
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
}
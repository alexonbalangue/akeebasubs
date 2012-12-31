<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableUpgrade extends FOFTable
{
	public function check() {
		$result = true;
		
		if(empty($this->title)) {
			$this->setError(JText::_('COM_AKEEBASUBS_UPGRADE_ERR_TITLE'));
			$result = false;
		}
				
		if(empty($this->from_id)) {
			$this->setError(JText::_('COM_AKEEBASUBS_UPGRADE_ERR_FROM_ID'));
			$result = false;
		}

		if(empty($this->to_id)) {
			$this->setError(JText::_('COM_AKEEBASUBS_UPGRADE_ERR_TO_ID'));
			$result = false;
		}
		
		if(empty($this->min_presence)) {
			$data->min_presence = 0;
		}

		if(empty($this->max_presence)) {
			$data->max_presence = 36500;
		}
		
		if(empty($this->type)) {
			$this->setError(JText::_('COM_AKEEBASUBS_UPGRADE_ERR_TYPE'));
			$result = false;
		}
		
		if(empty($this->value)) {
			$this->setError(JText::_('COM_AKEEBASUBS_UPGRADE_ERR_VALUE'));
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
				'idfield'	=> 'akeebasubs_upgrade_id',	// Field name on this table
				'joinfield'	=> 'akeebasubs_upgrade_id',	// Foreign table field
				'idalias'	=> 'upgradeid',				// Used in the query
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
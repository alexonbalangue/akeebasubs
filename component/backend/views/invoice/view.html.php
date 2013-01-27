<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

class AkeebasubsViewInvoice extends FOFViewHtml
{
	public function onRead($tpl = null) {
		$this->setPreRender(false);
		$this->setPostRender(false);
		
		return parent::onRead($tpl);
	}
}
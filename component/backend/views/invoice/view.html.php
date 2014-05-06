<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

class AkeebasubsViewInvoice extends F0FViewHtml
{
	public function onRead($tpl = null) {
		$this->setPreRender(false);
		$this->setPostRender(false);

		return parent::onRead($tpl);
	}
}
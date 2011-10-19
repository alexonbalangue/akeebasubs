<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

?>

<div id="akeebasubs-subscribe-level">
	<h3 class="level-title">
		<span class="level-title-text">
			<?php echo $this->escape($this->item->title) ?>
		</span>
	</h3>
	<div class="level-description level-description-short">
		<div class="level-description-inner">
			<?php if(!empty($this->item->image)):?>
			<img class="level-image" src="<?php echo JURI::base().(version_compare(JVERSION,'1.6.0','ge') ? 'images/' :'images/stories/').$this->escape($this->item->image)?>" />
			<?php endif;?>
			<?php echo JHTML::_('content.prepare', $this->item->description);?>
		</div>
	</div>
	<div class="level-clear"></div>	
</div>
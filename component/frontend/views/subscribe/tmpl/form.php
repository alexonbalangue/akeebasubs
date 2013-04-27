<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/autosubmit.js?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');
?>
<?php if(AkeebasubsHelperCparams::getParam('stepsbar',1)):?>
<?php echo $this->loadAnyTemplate('level/steps',array('step'=>'payment')); ?>
<?php endif; ?>

<?php echo $this->form ?>
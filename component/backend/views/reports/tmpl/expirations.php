<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/jquery.jqplot.min.css?'.AKEEBASUBS_VERSIONHASH);

AkeebaStrapper::addJSfile('media://com_akeebasubs/js/excanvas.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jquery.jqplot.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.json2.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.highlighter.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.dateAxisRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.canvasAxisTickRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.canvasTextRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.categoryAxisRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.barRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/expirations.js?'.AKEEBASUBS_VERSIONHASH);

$exp_start = date('Y-m-d', strtotime('-2 months', strtotime('last monday')));

?>
<div style="padding-bottom: 80px;padding-left: 15px">
	<h3><?php echo JText::_('COM_AKEEBASUBS_REPORTS_EXPIRATIONS_WEEK_CHART') ?></h3>
	<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_FROMDATE') ?>
	<?php echo JHTML::calendar($exp_start, 'exp_start', 'exp_start'); ?>
	<button class="btn btn-mini" id="exp_graph_reload" onclick="return false">
		<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_RELOADGRAPHS') ?>
	</button>
	<em><?php echo JText::_('COM_AKEEBASUBS_EXP_START_HELP') ?></em>

	<div id="akexpirationschart">
		<img src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/throbber.gif')?>" id="akthrobber" />
		<p id="akexpirationschart-nodata" style="display:none">
			<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')?>
		</p>
	</div>
</div>
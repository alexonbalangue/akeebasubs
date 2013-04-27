<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/jquery.jqplot.min.css?'.AKEEBASUBS_VERSIONHASH);

AkeebaStrapper::addJSfile('media://com_akeebasubs/js/excanvas.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jquery.jqplot.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.highlighter.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.dateAxisRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.barRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.pieRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/jqplot.hermite.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/cpanelgraphs.js?'.AKEEBASUBS_VERSIONHASH);

if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}

$graphDayFrom = gmdate('Y-m-d', time() - 30 * 24 * 3600);
?>
<h3><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_SALES') ?></h3>
<p>
	<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_FROMDATE') ?>
	<?php echo JHTML::_('calendar', $graphDayFrom, 'akeebasubs_graph_datepicker', 'akeebasubs_graph_datepicker'); ?>
	&nbsp;
	<button class="btn btn-mini" id="akeebasubs_graph_reload" onclick="return false">
		<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_RELOADGRAPHS') ?>
	</button>
</p>
<div id="aksaleschart">
	<img src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/throbber.gif')?>" id="akthrobber" />
	<p id="aksaleschart-nodata" style="display:none">
		<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')?>
	</p>
</div>

<div style="clear: both;">&nbsp;</div>

<h3><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_LEVELSTATS') ?></h3>
<div id="aklevelschart">
	<img src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/throbber.gif')?>" id="akthrobber2" />
	<p id="aklevelschart-nodata" style="display:none">
		<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')?>
	</p>
</div>

<script type="text/javascript">

akeebasubs_cpanel_graph_from = "<?php echo $graphDayFrom ?>";

(function($) {
	$(document).ready(function(){
		akeebasubs_cpanel_graphs_load();

		$('#akeebasubs_graph_reload').click(function(e){
			akeebasubs_cpanel_graphs_load();
		})
	});
})(akeeba.jQuery);
</script>
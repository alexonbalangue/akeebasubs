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

?>
<div style="padding-bottom: 80px;padding-left: 15px">
	<div id="akexpirationschart">
		<img src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/throbber.gif')?>" id="akthrobber" />
		<p id="akexpirationschart-nodata" style="display:none">
			<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')?>
		</p>
	</div>
</div>
<?php
defined('_JEXEC') or die();

JLoader::import('joomla.utilities.date');
$jRelDate = new JDate(AKEEBASUBS_DATE);

// IMPORTANT!!! DO NOT TRANSLATE THESE MESSAGES!!!
?>
<p style="font-size: small" class="well">
	<strong>
		Akeeba Subscriptions <?php echo AKEEBASUBS_VERSION ?>
	</strong>
	<br/>
	<span style="font-size: x-small">
		Copyright &copy;2010&ndash;<?php echo $jRelDate->format('y') ?>
		Nicholas K. Dionysopoulos / AkeebaBackup.com
	</span>
	<br/>
	
	<strong>
		If you use Akeeba Subscriptions, please post a rating and a review at the
		<a href="http://extensions.joomla.org/extensions/e-commerce/membership-a-subscriptions/19528">Joomla! Extensions Directory</a>.
	</strong>
	<br/>

	<span style="font-size: x-small">
		Akeeba Subscriptions is Free software released under the
		<a href="www.gnu.org/licenses/gpl.html">GNU General Public License,</a>
		version 3 of the license or &ndash;at your option&ndash; any later version
		published by the Free Software Foundation.
	</span>	
</p>
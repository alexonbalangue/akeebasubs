<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$this->getContainer()->platform->importPlugin('akeebasubs');
$jResponse = $this->getContainer()->platform->runPlugins('onSubscriptionFormPrepaymentRender', [
				$this->userparams,
				array_merge($this->cache, ['subscriptionlevel' => $this->item->akeebasubs_level_id])
			]);

if (!is_array($jResponse) || empty($jResponse)) return;
?>

@foreach($jResponse as $customFields)
	@if (is_array($customFields) && !empty($customFields))
		@each('site:com_akeebasubs/Level/tmpl_customfield', $customFields, 'field', 'raw|No users found')
	@endif
@endforeach

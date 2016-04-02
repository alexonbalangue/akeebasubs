<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var  array  $field                   Field definition */

$field['isValid']  = array_key_exists('isValid', $field) ? $field['isValid'] : true;

$customField_class = '';
$classValidLabel   = '';
$classInvalidLabel = '';

if ($this->apply_validation == 'true')
{
	$customField_class = $field['isValid'] ? 'success has-success' : 'error has-error';
	$classValidLabel   = $field['isValid'] ? '' : 'hidden';
	$classInvalidLabel = !$field['isValid'] ? '' : 'hidden';
}

?>
<div class="control-group form-group {{{$customField_class}}}">
	<label for="{{{$field['id']}}}" class="control-label col-sm-2">
		{{$field['label']}}
	</label>

	<div class="controls">
		<span class="col-sm-3">
			{{$field['elementHTML']}}
		</span>

		@if (array_key_exists('validLabel', $field))
		<span id="{{{$field['id']}}}_valid" class="help-inline help-block {{$classValidLabel}}">
			{{$field['validLabel']}}
		</span>
		@endif

		@if (array_key_exists('invalidLabel', $field))
		<span id="{{$field['id']}}_invalid" class="help-inline help-block {{$classInvalidLabel}}">
			{{$field['invalidLabel']}}
		</span>
		@endif
	</div>
</div>

<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

require_once __DIR__.'/abstract.php';
require_once __DIR__.'/dropdown.php';

/**
 * A radio selection list field
 *
 * @author Nicholas K. Dionysopoulos
 * @since 2.6.0
 */
class AkeebasubsCustomFieldRadio extends AkeebasubsCustomFieldDropdown
{
	public function __construct(array $config = array()) {
		parent::__construct($config);

		$this->input_type = 'radio';
	}

	public function getJavascript($item)
	{
		$slug = $item->slug;
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		addToValidationFetchQueue(plg_akeebasubs_customfields_fetch_$slug);
ENDJS;
		if(!$item->allow_empty) $javascript .= <<<ENDJS

		addToValidationQueue(plg_akeebasubs_customfields_validate_$slug);
ENDJS;
		$javascript .= <<<ENDJS
	});
})(akeeba.jQuery);

function plg_akeebasubs_customfields_fetch_$slug()
{
	var result = {};
	(function($) {
		result.$slug = $('input:radio[name=custom\\\\[$slug\\\\]]:checked').val();
	})(akeeba.jQuery);
	return result;
}

ENDJS;

		if(!$item->allow_empty):
			$success_javascript = '';
			$failure_javascript = '';
			if(!empty($item->invalid_label)) {
				$success_javascript .= "$('#{$slug}_invalid').css('display','none');\n";
				$failure_javascript .= "$('#{$slug}_invalid').css('display','inline-block');\n";
			}
			if(!empty($item->valid_label)) {
				$success_javascript .= "$('#{$slug}_valid').css('display','inline-block');\n";
				$failure_javascript .= "$('#{$slug}_valid').css('display','none');\n";
			}
			$javascript .= <<<ENDJS

function plg_akeebasubs_customfields_validate_$slug(response)
{
	var thisIsValid = true;
	(function($) {
		$('#$slug').parent().parent().removeClass('error').removeClass('success');
		$('#{$slug}_invalid').css('display','none');
		$('#{$slug}_valid').css('display','none');
		if (!akeebasubs_apply_validation)
		{
			return true;
		}

		if(response.custom_validation.$slug) {
			$('#$slug').parent().parent().addClass('success');
			$success_javascript
		} else {
			$('#$slug').parent().parent().addClass('error');
			$failure_javascript
			thisIsValid = false;
		}
		return thisIsValid;
	})(akeeba.jQuery);
}

ENDJS;
		endif;

		$document = JFactory::getDocument();
		$document->addScriptDeclaration($javascript);
	}
}
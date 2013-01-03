<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsSlavesubs extends JPlugin
{
	private $maxSlaves = array();
	
	public function __construct(&$subject, $name, $config = array())
	{
		parent::__construct($subject, $name, $config);
		
		$this->loadLanguage();
		
		$this->loadLevelAssignments();
	}
	
	/**
	 * Renders the configuration page in the component's back-end
	 * 
	 * @param   AkeebasubsTableLevel  $level  The subscription level
	 * 
	 * @return  object  Definition object, with two properties: 'title' and 'html'
	 */
	public function onSubscriptionLevelFormRender(AkeebasubsTableLevel $level)
	{
		jimport('joomla.filesystem.file');
		$filename = dirname(__FILE__).'/override/default.php';
		if(!JFile::exists($filename)) {
			$filename = dirname(__FILE__).'/tmpl/default.php';
		}

		if(!property_exists($level->params, 'slavesubs_maxSlaves'))
		{
			$level->params->slavesubs_maxSlaves = 0;
		}
		
		@ob_start();
		include_once $filename;
		$html = @ob_get_clean();
		
		$ret = (object)array(
			'title'	=> JText::_('PLG_AKEEBASUBS_SLAVESUBS_TAB_TITLE'),
			'html'	=> $html
		);
		
		return $ret;
	}
	
	/**
	 * Renders custom fields in the form, allowing the subscriber to enter the
	 * dependent users
	 * 
	 * @param  array  $cache
	 * 
	 * @return  array  The custom fields definitions
	 */
	public function onSubscriptionFormRenderPerSubFields($cache)
	{
		$fields = array();
		
		// Make sure we have a level
		if (!array_key_exists('subscriptionlevel', $cache))
		{
			return $fields;
		}
		// Make sure this leve supports slave subscriptions
		$level = $cache['subscriptionlevel'];
		if (!array_key_exists($level, $this->maxSlaves))
		{
			return $fields;
		}
		$maxSlaves = $this->maxSlaves[$level];
		
		if($maxSlaves <= 0)
		{
			return $fields;
		}
		
		jimport('joomla.user.helper');
		
		$javascript_fetch = '';
		$javascript_validate = '';
		
		for($i = 0; $i < $maxSlaves; $i++)
		{
			if(array_key_exists('slaveusers', $cache['subcustom'])) {
				$allSlaves = $cache['subcustom']['slaveusers'];
			} else {
				$allSlaves = array();
			}
			
			if(array_key_exists($i, $allSlaves)) {
				$current = $allSlaves[$i];
			} else {
				$current = '';
			}
			
			$html = '<input type="text" name="subcustom[slaveusers]['.$i.']" id="slaveuser'.$i.'" value="'.htmlentities($current).'" />';
			
			$userExists = false;
			if(!empty($current))
			{
				$userExists = JUserHelper::getUserId($current) > 0;
			}
			
			// Setup the field
			$field = array(
				'id'			=> 'slaveuser'.$i,
				'label'			=> JText::sprintf('PLG_AKEEBASUBS_SLAVESUBS_ADDONUSER_LBL', $i + 1),
				'elementHTML'	=> $html,
				'invalidLabel'	=> JText::_('PLG_AKEEBASUBS_SLAVESUBS_INVALID_LBL'),
				'isValid'		=> empty($current) || $userExists
			);
			// Add the field to the return output
			$fields[] = $field;
			
			// Add Javascript
			$javascript_fetch .= <<<ENDJS
result.slaveusers[$i] = $('#slaveuser$i').val();

ENDJS;
			$javascript_validate .= <<<ENDJS

$('#slaveuser$i').parent().parent().removeClass('error').removeClass('success');
if(!response.subcustom_validation.slaveuser$i) {
	$('#slaveuser$i').parent().parent().addClass('error');
	$('#slaveuser{$i}_invalid').css('display','inline-block');
	thisIsValid = false;
} else {
	$('#slaveuser$i').parent().parent().removeClass('error');
		$('#slaveuser{$i}_invalid').css('display','none');
}

ENDJS;
		}
		
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		addToSubValidationFetchQueue(plg_akeebasubs_slavesubs_fetch);
		addToSubValidationQueue(plg_akeebasubs_slavesubs_validate);
	});
})(akeeba.jQuery);

function plg_akeebasubs_slavesubs_fetch()
{
	var result = {
		slaveusers: {}
	};

	(function($) {
$javascript_fetch
	})(akeeba.jQuery);
	
	return result;
}

function plg_akeebasubs_slavesubs_validate(response)
{
	var thisIsValid = true;
	(function($) {
$javascript_validate
		
		return thisIsValid;
	})(akeeba.jQuery);
}

ENDJS;
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($javascript);
		
		return $fields;
	}
	
	/**
	 * Performs validation of the custom fields, i.e. check that a valid
	 * username (or no username) is set on each one of them.
	 * 
	 * @param   object  $data
	 * 
	 * @return  array subscription_custom_validation, valid
	 */
	public function onValidatePerSubscription($data)
	{
		// Initialise the validation respone
		$response = array(
			'valid'								=> true,
			'subscription_custom_validation'	=> array()
		);
		
		// Make sure we have a subscription level ID
		if($data->id <= 0)
		{
			return $response;
		}
		
		// Fetch the custom data
		$subcustom = $data->subcustom;
		
		if (!array_key_exists($data->id, $this->maxSlaves))
		{
			return $response;
		}
		$maxSlaves = $this->maxSlaves[$data->id];
		
		if($maxSlaves <= 0)
		{
			return $response;
		}

		if(!array_key_exists('slaveusers', $subcustom))
		{
			return $response;
		}

		jimport('joomla.user.helper');
		
		for($i = 0; $i < $maxSlaves; $i++)
		{
			if(!array_key_exists($i, $subcustom['slaveusers']))
			{
				$response['subscription_custom_validation']['slaveuser'.$i] = true;
				continue;
			}
			$current = $subcustom['slaveusers'][$i];
			if (empty($current))
			{
				$response['subscription_custom_validation']['slaveuser'.$i] = true;
			}
			elseif ($current == JFactory::getUser()->username)
			{
				$response['subscription_custom_validation']['slaveuser'.$i] = false;
			}
			elseif ($current == $data->username)
			{
				$response['subscription_custom_validation']['slaveuser'.$i] = false;
			}
			else
			{
				$response['subscription_custom_validation']['slaveuser'.$i] = JUserHelper::getUserId($current) > 0;;
			}
			
			$response['valid'] = $response['valid'] &&
				$response['subscription_custom_validation']['slaveuser'.$i]; 
		}
		
		return $response;
	}
	
	/**
	 * This is called whenever a new subscription is created or an existing
	 * subscription is modified. We are using it to create slave subscriptions
	 * where necessary and "mirror" the parameters of the master subscription
	 * to the slave subscriptions when slave subscriptions already exist.
	 * 
	 * @param   AkeebasubsTableSubscription  $row   The subscription which we act upon
	 * @param   array                        $info  Update information (not used)
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		
	}
	
	/**
	 * This is called once per user whenever the admin uses the Run the Integrations
	 * button in the back-end. We loop the user's subscriptions and run
	 * onAKSubscriptionChange on them.
	 * 
	 * @param   int  $user_id  The user ID we're acting upon
	 */
	public function onAKUserRefresh($user_id)
	{
		
	}	
	
	/**
	 * Copies the subscription information from row $from to $to. If $to is empty
	 * a new subscription row is created.
	 * 
	 * @param   AkeebasubsTableSubscription $from  Row to copy from
	 * @param   AkeebasubsTableSubscription $to    Row to copy to
	 * 
	 * @return  AkeebasubsTableSubscription The modified/created $to row
	 */
	private function copySubscriptionInformation($from, $to = null)
	{
		
	}
	
	/**
	 * Loads the maximum slave subscriptions assignments for each subscription
	 * level.
	 */
	private function loadLevelAssignments()
	{
		$this->maxSlaves = array();
		
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$slavesKey = 'slavesubs_maxSlaves';
		
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				$save = false;
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					continue;
				}
				if(property_exists($level->params, $slavesKey))
				{
					$this->maxSlaves[$level->akeebasubs_level_id] = $level->params->$slavesKey;
				}
			}
		}
	}
}
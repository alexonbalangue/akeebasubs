<?php

/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerBehaviorValidatable extends KControllerBehaviorAbstract
{
	/**
	 * Adds the validation logic to the "save" action
	 *
	 * @param KCommandContext $context The command context
	 * @return bool False on invalid data
	 */
    protected function _beforeSave(KCommandContext $context)
    {
		$identifier = (string)$context->caller->getIdentifier();
		$sessionKey = md5($identifier);
		$model = $context->caller->getModel();
		
		if(!($model instanceof KModelTable)) return true;

        if(method_exists($model, 'validate'))
        {
            // The model supports validation. Run it.
            $data = $context->data;
            
            if(!property_exists($data, 'id')) {
            	$data->id = KRequest::get('get.id','int',null);
				if($data->id instanceof KConfig) {
					$rawdata = $data->id->toArray();
					if(!empty($rawdata)) {
						$data->id = array_shift($rawdata);
					} else {
						$data->id = 0;
					}
				}
            }
            
            $validationErrors = $model->validate($data);

            if(!empty($validationErrors))
            {
				// Save the post data in the session if the data was invalid
				$tempdata = $data;
				unset($tempdata['_token']);
				KRequest::set('session.'.$sessionKey.'.data', serialize((array)$tempdata->getIterator()) );

                // Construct the new URL - Is this necessary or could I use the referrer?
                $referrer = KRequest::referrer();
                $query = $referrer->getQuery(true);
                $query['id'] = $model->getState()->id;
                $referrer->setQuery($query);

                // Redirect
				if($context->caller->getRequest()->format == 'raw') {
					KRequest::set('session.'.$sessionKey.'.errors', serialize(implode('<br/>',$validationErrors)) );
				}
				$context->caller
					->setRedirect((string)$referrer, implode('<br/>',$validationErrors), 'error' );
                return false;
            } else {
            	// Push back the (changed) data and nullify the data cache
            	$context->data = $data;
				KRequest::set('session.'.$sessionKey.'.errors', null);
				KRequest::set('session.'.$sessionKey.'.data', null);
			}
        }

        return true;
    }

	/**
	 * Adds the validation logic to the "apply" action, by delegating to the
	 * validation handler of the "save" action.
	 *
	 * @param KCommandContext $context The command context
	 * @return bool False on invalid data
	 */
	protected function _beforeApply(KCommandContext $context)
	{
		return $this->_beforeSave($context);
	}

	/**
	 * Adds the validation logic to the "edit" action, by delegating to the
	 * validation handler of the "save" action.
	 *
	 * @param KCommandContext $context The command context
	 * @return bool False on invalid data
	 */
	public function _beforeEdit(KCommandContext $context)
	{
		return $this->_beforeSave($context);
	}

	public function _beforeAdd(KCommandContext $context)
	{
		return $this->_beforeSave($context);
	}

	/**
	 * Restores the memorised data when the editor form appears again on user's
	 * browser.
	 *
	 * @param KCommandContext $context The command context
	 * @return bool Always true (non-blocking command)
	 */
    public function _beforeRead(KCommandContext $context)
    {
		$identifier = md5((string)$context->caller->getIdentifier());
        $tempdata = KRequest::get('session.'.$identifier.'.data','raw');
        if(!empty($tempdata))
        {
			$tempdata = unserialize($tempdata);
			if($tempdata !== false) {
				$model = $context->caller->getModel();
				$model->getItem()->setData($tempdata);
			}
			KRequest::set('session.'.$identifier.'.data',null);
        }

		return true;
    }
}

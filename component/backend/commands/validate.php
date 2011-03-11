<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * A transparent server-side data validation solution with automatic redirection
 * back to the editor page on invalid data, without resetting user's input.
 *
 * @author Nicholas K. Dionysopoulos <nicholas-at-akeebabackup-dot-com>
 * @license GNU GPL v3 or later
 */
class ComAkeebasubsCommandValidate extends KCommand
{
	/**
	 * Adds the validation logic to the "save" action
	 *
	 * @param KCommandContext $context The command context
	 * @return bool False on invalid data
	 */
    public function _controllerBeforeSave(KCommandContext $context)
    {
		$identifier = (string)$context->caller->getIdentifier();
        $model = KFactory::get((string)$context->caller->getModel()->getIdentifier());

        if(method_exists($model, 'validate'))
        {
            // The model supports validation. Run it.
            $data = $context->data;
            $validationErrors = $model->validate($data);

            if(!empty($validationErrors))
            {
				// Save the post data in the session if the data was invalid
				$tempdata = $data;
				unset($tempdata['_token']);
				KRequest::set('session.'.$identifier.'.data', serialize((array)$tempdata->getIterator()) );

                // Construct the new URL - Is this necessary or could I use the referrer?
                $referrer = KRequest::referrer();
                $query = $referrer->getQuery(true);
                $query['id'] = $model->getState()->id;
                $referrer->setQuery($query);

                // Redirect
				if($context->caller->getRequest()->format == 'raw')
				{
					KRequest::set('session.'.$identifier.'.errors', serialize(implode('<br/>',$validationErrors)) );
				}
				$context->caller
					->setRedirect((string)$referrer, implode('<br/>',$validationErrors), 'error' );
                return false;
            } else {
            	// Push back the (changed) data and nullify the data cache
            	$context->data = $data;
				KRequest::set('session.'.$identifier.'.errors', null);
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
	public function _controllerBeforeApply(KCommandContext $context)
	{
		return $this->_controllerBeforeSave($context);
	}

	/**
	 * Adds the validation logic to the "edit" action, by delegating to the
	 * validation handler of the "save" action.
	 *
	 * @param KCommandContext $context The command context
	 * @return bool False on invalid data
	 */
	public function _controllerBeforeEdit(KCommandContext $context)
	{
		return $this->_controllerBeforeSave($context);
	}

	public function _controllerBeforeAdd(KCommandContext $context)
	{
		return $this->_controllerBeforeSave($context);
	}

	/**
	 * Restores the memorised data when the editor form appears again on user's
	 * browser.
	 *
	 * @param KCommandContext $context The command context
	 * @return bool Always true (non-blocking command)
	 */
    public function _controllerBeforeRead(KCommandContext $context)
    {    	
		$identifier = (string)$context->caller->getIdentifier();
        $tempdata = KRequest::get('session.'.$identifier.'.data','raw');
        if(!empty($tempdata))
        {
			$tempdata = unserialize($tempdata);
			if($tempdata !== false) {
				$identifier = (string)$context->caller->getIdentifier();
				$model = KFactory::get((string)$context->caller->getModel()->getIdentifier());
				$model->getItem()->setData($tempdata);
			}
			KRequest::set('session.'.$identifier.'.data',null);
        }

		return true;
    }
}
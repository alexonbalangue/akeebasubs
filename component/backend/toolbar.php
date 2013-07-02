<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsToolbar extends FOFToolbar
{
	protected function renderSubmenu()
	{
		$views = array(
			'cpanel',
			'COM_AKEEBASUBS_MAINMENU_SETUP' => array(
				'levels',
				'customfields',
				'levelgroups',
				'relations',
				'upgrades',
				'taxconfigs',
				'taxrules',
				'states',
				'emailtemplates',
				'blockrules',
			),
			'subscriptions',
			'coupons',
			'COM_AKEEBASUBS_MAINMENU_AFFILIATES' => array(
				'affiliates',
				'affpayments'
			),
			'COM_AKEEBASUBS_MAINMENU_TOOLS' => array(
				'tools',
				'users'
			),
			'COM_AKEEBASUBS_MAINMENU_INVOICES' => array(
				'invoices',
				'invoicetemplates'
			),
		);

		if(!AKEEBASUBS_PRO)
		{
			$key = array_search('relations', $views['COM_AKEEBASUBS_MAINMENU_SETUP']);
			unset($views['COM_AKEEBASUBS_MAINMENU_SETUP'][$key]);

			$key = array_search('emailtemplates', $views['COM_AKEEBASUBS_MAINMENU_SETUP']);
			unset($views['COM_AKEEBASUBS_MAINMENU_SETUP'][$key]);

			$key = array_search('blockrules', $views['COM_AKEEBASUBS_MAINMENU_SETUP']);
			unset($views['COM_AKEEBASUBS_MAINMENU_SETUP'][$key]);

			unset($views['COM_AKEEBASUBS_MAINMENU_INVOICES']);
		}

		foreach($views as $label => $view) {
			if(!is_array($view)) {
				$this->addSubmenuLink($view);
			} else {
				$label = JText::_($label);
				$this->appendLink($label, '', false);
				foreach($view as $v) {
					$this->addSubmenuLink($v, $label);
				}
			}
		}
	}

	private function addSubmenuLink($view, $parent = null)
	{
		static $activeView = null;
		if(empty($activeView)) {
			$activeView = $this->input->getCmd('view','cpanel');
		}

		if ($activeView == 'cpanels')
		{
			$activeView = 'cpanel';
		}

		$key = strtoupper($this->component).'_TITLE_'.strtoupper($view);
		if(strtoupper(JText::_($key)) == $key) {
			$altview = FOFInflector::isPlural($view) ? FOFInflector::singularize($view) : FOFInflector::pluralize($view);
			$key2 = strtoupper($this->component).'_TITLE_'.strtoupper($altview);
			if(strtoupper(JText::_($key2)) == $key2) {
				$name = ucfirst($view);
			} else {
				$name = JText::_($key2);
			}
		} else {
			$name = JText::_($key);
		}

		$link = 'index.php?option='.$this->component.'&view='.$view;

		$active = $view == $activeView;

		$this->appendLink($name, $link, $active, null, $parent);
	}

	protected function getMyViews()
	{
		$views = array('cpanel');

		$allViews = parent::getMyViews();
		foreach($allViews as $view) {
			if(!in_array($view, $views)) {
				$views[] = $view;
			}
		}

		return $views;
	}

	public function onSubscriptionsBrowse()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editList();
		}
		if($this->perms->create) {
			JToolBarHelper::addNew();
		}

		$this->renderSubmenu();

		$bar = JToolBar::getInstance('toolbar');

		// Add "Subscription Refresh"Run Integrations"
		JToolBarHelper::divider();
		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			$bar->appendButton('Link', 'subrefresh', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH'), 'javascript:akeebasubs_refresh_integrations();');
		}
		else
		{
			$bar->appendButton('Link', 'play', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH'), 'javascript:akeebasubs_refresh_integrations();');
		}

		// Add "Export to CSV"
		$link = JURI::getInstance();
		$query = $link->getQuery(true);
		$query['format'] = 'csv';
		$query['option'] = 'com_akeebasubs';
		$query['view'] = 'subscriptions';
		$query['task'] = 'browse';
		$link->setQuery($query);

		JToolBarHelper::divider();
		$icon = version_compare(JVERSION, '3.0', 'lt') ? 'export' : 'download';
		$bar->appendButton('Link', $icon, JText::_('COM_AKEEBASUBS_COMMON_EXPORTCSV'), $link->toString());
	}

	public function onLevelsBrowse()
	{
		$this->onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', false);
	}

	public function onUsersBrowse()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editList();
		}
		if($this->perms->create) {
			JToolBarHelper::addNew();
		}

		$this->renderSubmenu();
	}

	public function onAffpaymentsBrowse()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editList();
		}
		if($this->perms->create) {
			JToolBarHelper::addNew();
		}

		$this->renderSubmenu();
	}

	public function onMakecouponsOverview()
	{
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		$this->renderSubmenu();
	}

	/**
	 * Renders the toolbar for the component's Control Panel page
	 */
	public function onTaxconfigsMain()
	{
		//on frontend, buttons must be added specifically
		list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();

		if($isAdmin || $this->renderFrontendSubmenu) {
			$this->renderSubmenu();
		}

		if(!$isAdmin && !$this->renderFrontendButtons) return;

		// Set toolbar title
		$option = $this->input->getCmd('option','com_foobar');
		$subtitle_key = strtoupper($option.'_TITLE_'.$this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( strtoupper($option)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $option));

		JToolBarHelper::save();
	}

	public function onInvoicesBrowse()
	{
		//on frontend, buttons must be added specifically
		list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();

		if($isAdmin || $this->renderFrontendSubmenu) {
			$this->renderSubmenu();
		}

		if(!$isAdmin && !$this->renderFrontendButtons) return;

		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
	}

	public function onCustomfieldsBrowse()
	{
		$this->onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', false);
	}

	public function onInvoicetemplatesBrowse()
	{
		$this->onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', false);
	}

    public function onEmailtemplatesAdd()
    {
        // Quick hack to mark this record as new
        $this->_isNew = true;

        parent::onAdd();
    }

    public function onEmailtemplatesEdit()
    {
        if(!isset($this->_isNew))
        {
            $options['class']   = 'preview';
            $options['a.task']  = 'testtemplate';
            $options['a.href']  = '#';
            $options['text']    = JText::_('COM_AKEEBASUBS_EMAILTEMPLATES_TESTTEMPLATE');

            $this->addCustomBtn('test-template', $options);
            JToolBarHelper::divider();
        }
        parent::onEdit();
    }

    protected function addCustomBtn($id, $options = array())
    {
        $options = (array) $options;
        $a_class = 'toolbar';
        $href	 = '';
        $task	 = '';
        $text    = '';
        $rel	 = '';
        $target  = '';
        $other   = '';

        if(isset($options['a.class']))	$a_class .= $options['a.class'];
        if(isset($options['a.href']))	$href     = $options['a.href'];
        if(isset($options['a.task']))	$task     = $options['a.task'];
        if(isset($options['a.target']))	$target   = $options['a.target'];
        if(isset($options['a.other']))	$other    = $options['a.other'];
        if(isset($options['text']))		$text	  = $options['text'];
        if(isset($options['class']))
        {
            $class = $options['class'];
        }
        else
        {
            $class = 'default';
        }

        if(isset($options['modal']))
        {
            JHTML::_('behavior.modal');
            $a_class .= ' modal';
            $rel	  = "'handler':'iframe'";
            if(is_array($options['modal']))
            {
                if(isset($options['modal']['size']['x']) && isset($options['modal']['size']['y']))
                {
                    $rel .= ", 'size' : {'x' : ".$options['modal']['size']['x'].", 'y' : ".$options['modal']['size']['y']."}";
                }
            }
        }

        $html = '<a id="'.$id.'" class="'.$a_class.'" alt="'.$text.'"';

        if($rel)	$html .= ' rel="{'.$rel.'}"';
        if($href)	$html .= ' href="'.$href.'"';
        if($task)	$html .= " onclick=\"javascript:submitbutton('".$task."')\"";
        if($target) $html .= ' target="'.$target.'"';
        if($other)  $html .= ' '.$other;
        $html .= ' >';

        $html .= '<span class="icon-32-'.$class.'" title="'.$text.'" > </span>';
        $html .= $text;
        $html .= '</a>';

        $bar = JToolBar::getInstance();
        $bar->appendButton('Custom', $html, $id);
    }
}
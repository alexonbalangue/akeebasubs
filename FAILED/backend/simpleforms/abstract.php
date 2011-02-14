<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * SimpleForm - Abstract implementation
 * 
 * @author		Nicholas K. Dionysopoulos
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */
abstract class ComAkeebasubsSimpleformAbstract
	extends KObjectArray
	implements ComAkeebasubsSimpleformInterface, KObjectIdentifiable
{
	/** @var array Array with all form data values */
	protected $_data;
	
	/** @var object Array with all form element definitions */
	protected $_definitions;
	
	/** @var object The object identifier */
	protected $_identifier = null;
	
	/**
	 * Constructor
	 *
	 * @param	array An optional associative array of configuration settings.
	 */
	public function __construct(KConfig $options)
	{
        $this->_identifier = $options->identifier;
		parent::__construct($options);
	}
	
	/**
	 * Initializes the options for the object
	 *
	 * Called from {@link __construct()} as a first step of object instantiation.
	 *
	 * @param   array   Options
	 * @return  array   Options
	 */
	protected function _initialize(KConfig $options)
	{
		$defaults = array(
			'identifier' => null
       	);
       	
       	$options->append($defaults);
       	
        return $options;
    }
	
    /**
	 * Get the identifier
	 *
	 * @return 	object A KFactoryIdentifier object
	 * @see 	KFactoryIdentifiable
	 */
	public function getIdentifier()
	{
		return $this->_identifier;
	}
    
	/**
	 * Allows you to pass the data KConfig to the form (I know, the implementation sucks)
	 * @param array $data The raw data values
	 */
	public function setData(KConfig $data)
	{
		$this->_data = $data;
		return $this;
	}
	
	/**
	 * Allows you to set the form definition (I know, the implementation sucks)
	 * @param object $definitions The raw definition, as imported from the JSON document
	 */
	public function setDefinitions($definitions)
	{
		$this->_definitions = $definitions;
		return $this;
	}
	
	public function renderHtml()
	{
		$html = '';
		
		if(!empty($this->_definitions)) {
			foreach($this->_definitions as $sectionName => $sectionData) {
				$html .= '<fieldset>';
				if(property_exists($sectionData, 'title')) {
					$legend = JText::_($sectionData->title);
				} else {
					$legend = JText::_('COM_AKEEBASUBS_CONFIG_'.strtoupper($sectionName).'_TITLE');
				}
				$html .= "\t<legend>$legend</legend>";
				
				// Render elements
				foreach($sectionData->options as $key => $opt)
				{
					$html .= KFactory::tmp('admin::com.akeebasubs.simpleform.element.'.$opt->type, array(
						'name'			=> $key,
						'label'			=> $opt->title,
						'value'			=> $this->_data->$key,
						'attributes'	=> property_exists($opt,'attributes') ? $opt->attributes : array()
					))->renderHtml();
				}
				
				$html .= '</fieldset>';
			}
		}
		
		return $html;
	}
}
<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * SimpleForm - Form element interface
 * 
 * @author		Nicholas K. Dionysopoulos
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

abstract class ComAkeebasubsSimpleformElementAbstract
	extends KObject
	implements ComAkeebasubsSimpleformElementInterface, KObjectIdentifiable
{
	protected $_value = '';
	
	protected $_name = '';
	
	protected $_attributes = array();
	
	protected $_label = null;
	
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
	
	protected function _initialize(KConfig $options)
	{
		$defaults = array(
			'identifier'	=> null,
			'name'			=> 'element',
			'attributes'	=> array(),
			'value'			=> '',
			'label'			=> null
       	);
       	
       	$options->append($defaults);
       	
       	$this->_name = $options->name;
       	$this->_value = $options->value;
       	$this->_label = $options->label;
       	if(empty($options->attributes)) {
       		$this->_attributes = array();
       	} elseif(is_array($options->attributes)) {
       		$this->_attributes = $options->attributes;
       	} else {
       		$this->_attributes = json_decode(json_encode($options->attributes), true);
       	}       	
        
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
    
	public function renderHtml() {}
	
	protected function getAttributesAsHTML()
	{
		$html = '';
		
		if(!empty($this->_attributes)) {
			$attr = array();
			foreach($this->_attributes as $key => $value) {
				$attr[] = "$key=\"$value\"";
			}
			$html .= implode(' ', $attr);
		}
		
		return $html;
	}
}
<?php

/**
 * @package     Articles Calendar
 *
 * @copyright   Copyright (C) 2017 Joomcar extensions. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldSelectField extends JFormField {
	var $_name = 'selectfield';
	var	$type = 'selectfield';

	function getInput(){
		return JFormFieldSelectField::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
	}
	
	function fetchElement($name, $value, &$node, $control_name) {
		$options = array();
		$options[] = JHTML::_('select.option', 'created', JText::_('MOD_AGS_FIELD_CREATED'));
		$options[] = JHTML::_('select.option', 'publish_up', JText::_('MOD_AGS_FIELD_PUBLISHED'));

		$custom_fields = JFactory::getDBO()->setQuery("SELECT * FROM #__fields WHERE type = 'calendar' AND state = 1")->loadObjectList();
		
		foreach($custom_fields as $field) {
			$options[] = JHTML::_("select.option", "field{$field->id}", $field->label);
		}
		
		return JHTML::_('select.genericlist', $options, $name, 'class="inputbox"', 'value', 'text', $value, $control_name.$name);
	}
}
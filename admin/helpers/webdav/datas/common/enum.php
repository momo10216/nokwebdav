<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	NoKWebDAV - Common classes
* @copyright	Copyright (c) 2017 Norbert Kümin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// No direct access
defined('_JEXEC') or die('Restricted access');

class WebDAVCommonEnum {
	private $validValues = array();
	private $multiple = false;
	private $values = array();

	function __construct($validValues, $multiple) {
		$this->validValues = $validValues;
		$this->multiple = $multiple;
		$this->_resetValues();
		$this->setValue($validValues[0]);
	}

	private function _resetValues() {
		foreach($this->validValues as $validValue) {
			$this->values[$validValue] = false;
		}
	}

	public function getValidValues() {
		return $this->validValues;
	}

	public function hasMultipleValues() {
		return count($this->getValues()) > 1;
	}

	public function getValues() {
		$retval = array();
		foreach($this->values as $value => $selected) {
			if ($selected === true) { $retval[] = }
		}
	}

	public function setValue($value) {
		if (array_search($value,$this->validValues) === false) {
			throw new Exception('Enum exception: Value "'.$value.'" is not valid ('.implode(',',$this->validValues).')');
		}
		if ($this->multiple === false) { $this->_resetValues(); }
		$this->values[$value] = true;
	}

	public function __toString() {
		if ($this->hasMultipleValues() === true) {
			return '['.implode(',',$this->getValues()).']';
		} else {
			return $this->getValues()[0];
		}
	}
}
?>
<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	NoKWebDAV
* @copyright	Copyright (c) 2017 Norbert Kuemin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
class WebDAVText {
	private static $EOL = "\n";
	private $type = '';
	private $settings = array();
	private $values = array();


	public function __construct($text = '') {
		if (!empty($text)) { $this->import($text); }
	}

	public function import($text) {
		$text = str_replace("\r",$self::EOL,$text);
		$text = str_replace($self::EOL.$self::EOL,$self::EOL,$text);
		$lines = explode($self::EOL,$text);
		$cleanlines = array();
		$currentLine = '';
		foreach($lines as $line) {
			if (strlen($line) > 0) {
				if (substr($line,0,1) == ' ') {
					// append
					$currentLine .= substr($line,1);
				} else {
					if (!empty($currentLine)) { $cleanlines[] = $currentLine; }
					$currentLine = $line;
				}
			}
		}
		foreach($cleanlines as $cleanline) {
			list($key,$value) = explode(':',$cleanline,2);
			$setting = '';
			if (strpos(';',$key)) { list($key,$setting) = explode(':',$key,2) }
			$this->values[$key] = $value;
			if (!empty($setting)) { $this->settings[$key] = $setting; }
		}
	}

	public function getKeys() {
		return array_keys($this->values);
	}

	public function getValue($key) {
		if (isset($this->values[$key])) { return $this->values[$key]; }
		return '';
	}

	public function getSetting($key) {
		if (isset($this->settings[$key])) { return $this->settings[$key]; }
		return '';
	}
}
?>

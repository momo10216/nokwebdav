<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	NoK-WebDAV
* @copyright	Copyright (c) 2017 Norbert Kümin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// No direct access
defined('_JEXEC') or die('Restricted access');
 
class WebDAVPropFindHelper {
	public static function getResponse() {
		$info = self::_parseInfo();
		if ($info === false) { return array('400', array(), ''); }
	}

	private static function _getDepth() {
		global $_SERVER;
		return isset($_SERVER["HTTP_DEPTH"]) ? $_SERVER["HTTP_DEPTH"] : "infinity";
	}

	private static function _parseInfo() {
		$input = file_get_contents('php://input');
		$dom = new DOMDocument();
		if (!$dom->loadXML($input)) { return false; }
		$elementList = $dom->getElementsByTagName('allprop');
		if ($elementList->length > 0) { return 'all'; }
		$elementList = $dom->getElementsByTagName('prop');
		$info = array();
		if ($elementList->length > 0) {
			for($i=0 ; $i<$elementList->length ; $i++) {
				$element = $elementList->item($i);
				$elementChildList = $element->childNodes;
				if ($elementChildList->length > 0) {
					for($j=0 ; $j<$elementChildList->length ; $j++) {
						$elementChild = $elementChildList->item($j);
						$info[] = $elementChild->nodeName;
					}
				}
			}
		}
		if (count($info) < 1) { return 'all'; }
		return $info;
	}
}
?>

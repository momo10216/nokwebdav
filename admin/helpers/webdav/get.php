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
 
class WebDAVGetHelper {
	public static function getResponse() {
		return array(WebDAVHelper::$HTTP_STATUS_OK, array(), '');
	}

	private static function _getPathInfo() {
		global $_SERVER;
		return empty($_SERVER["PATH_INFO"]) ? '/' : $_SERVER["PATH_INFO"];
	}

}
?>

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
		WebDAVHelper::debugAddMessage('GET: '.self::_getLocation());
		switch(self::_getFileType(self::_getLocation())) {
			case 'file':
			case 'directory':
				return array(WebDAVHelper::$HTTP_STATUS_OK, array(), '');
			default:
				return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), '');
		}
		return array(WebDAVHelper::$HTTP_STATUS_OK, array(), '');
	}

	private static function _getLocation() {
		global $_SERVER;
		$language = JFactory::getLanguage();
		$shortlang = explode('-',$language->getTag())[0];
		$location = empty($_SERVER["PATH_INFO"]) ? '/' : $_SERVER["PATH_INFO"];
		$location = str_replace('/'.$shortlang,'',$location);
		return $location;
	}

	private static function _getFileType($file) {
		if (!file_exists($file)) { return 'missing'; }
		if (is_dir($fspath)) { return 'directory'; }
		return 'file';
	}

}
?>

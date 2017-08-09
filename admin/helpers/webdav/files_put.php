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
 
class WebDAVHelperPluginCommand {
	public static function execute($fileLocation) {
		$status = self::_check($fileLocation);
		$header = array();
		$content = '';
		if (!$status) { $status = self::_save($fileLocation); }
		return array($status, $header, $content);
	}

	private static function _check($fileLocation) {
		global $_SERVER;
		if (is_dir($fileLocation)) { return WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT; }
		if (WebDAVHelper::isLocked('files', $fileLocation)) {
			return array(WebDAVHelper::$HTTP_STATUS_ERROR_LOCKED,array(),'');
		}
		return '';
	}

	private static function _save($fileLocation) {
		$fileIsNew = !file_exists($fileLocation);
		$status = file_put_contents($fileLocation, file_get_contents('php://input'));
		if (!$status) {
			WebDAVHelper::debugAddMessage('Cannot write to file: '.$fileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN;
		}
		return $fileIsNew ? WebDAVHelper::$HTTP_STATUS_CREATED : WebDAVHelper::$HTTP_STATUS_NO_CONTENT;
	}
}
?>

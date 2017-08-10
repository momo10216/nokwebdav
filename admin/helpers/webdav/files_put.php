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
	public static function execute($fileLocation, $rootLocation, $maxSize) {
		$content = file_get_contents('php://input');
		$status = self::_check($fileLocation, $rootLocation, $content, $maxSize);
		$header = array();
		$answer = '';
		if (!$status) { $status = self::_save($fileLocation, $content); }
		return array($status, $header, $answer);
	}

	private static function _check($fileLocation, $rootLocation, $content, $maxSize) {
		global $_SERVER;
		if (is_dir($fileLocation)) { return WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT; }
		if (WebDAVHelper::isLocked('files', $fileLocation)) {
			WebDAVHelper::debugAddMessage('File locked: '.$fileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_LOCKED;
		}
		if (!WebDAVHelperPlugin::hasEnoughSpace($rootLocation,strlen($content),$maxSize)) {
			WebDAVHelper::debugAddMessage('Not enough space');
			return WebDAVHelper::$HTTP_STATUS_ERROR_INSUFFICIENT_STORAGE;
		}
		return '';
	}

	private static function _save($fileLocation, $content) {
		$fileIsNew = !file_exists($fileLocation);
		if (!$fileIsNew && !is_writable ($fileLocation)) {
			WebDAVHelper::debugAddMessage('Cannot write to file: '.$fileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN;
		}
		$fh = fopen($fileLocation, 'wb');
		if (!$fileIsNew && !is_writable ($fileLocation)) {
			WebDAVHelper::debugAddMessage('Cannot open file for writing: '.$fileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN;
		}
		if (strlen($content) > 0) {
			if (!fwrite($fh, $content)) {
				fclose($fh);
				WebDAVHelper::debugAddMessage('Cannot write content to file: '.$fileLocation);
				return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN;
			}
		}
		fclose($fh);
		return $fileIsNew ? WebDAVHelper::$HTTP_STATUS_CREATED : WebDAVHelper::$HTTP_STATUS_NO_CONTENT;
	}
}
?>

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
		list ($path, $directory) = WebDAVHelperPlugin::getPathAndFilename($fileLocation);
//		WebDAVHelper::debugAddMessage('directory: '.$directory);
//		WebDAVHelper::debugAddMessage('file: '.$file);
		$status = self::_check($path, $directory);
		$header = array();
		$content = '';
		$outFile = '';
		if (!$status) { $status = self::_createDir($path, $directory); }
		return array($status, $header, $content, $outFile);
	}

	private static function _check($path, $directory) {
		global $_SERVER;
		if (!file_exists($path)) { return WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT; }
		if (!is_dir($path)) { return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN; }
		if (file_exists($path.DIRECTORY_SEPARATOR.$directory) ) { return WebDAVHelper::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED; }
		// Extended MKCOL currently not supported
		if (!empty($_SERVER["CONTENT_LENGTH"])) { return WebDAVHelper::$HTTP_STATUS_ERROR_UNSUPPORTED_MEDIA_TYPE; }
		return '';
	}

	private static function _createDir($path, $directory) {
		$status = mkdir($path.DIRECTORY_SEPARATOR.$directory, 0777);
		if (!$status) { return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN; }
		return WebDAVHelper::$HTTP_STATUS_CREATED;
	}
}
?>

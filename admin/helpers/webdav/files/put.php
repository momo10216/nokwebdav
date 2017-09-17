<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	NoKWebDAV
* @copyright	Copyright (c) 2017 Norbert Kümin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// No direct access
defined('_JEXEC') or die('Restricted access');
 
class WebDAVHelperPluginCommand {
	public static function execute($fileLocation, $rootLocation, $maxSize) {
		$status = self::_check($fileLocation, $rootLocation, $maxSize);
		$header = array();
		$answer = '';
		$outFile = '';
		if (!$status) { $status = self::_save($fileLocation); }
		return array($status, $header, $answer, $outFile);
	}

	private static function _check($fileLocation, $rootLocation, $maxSize) {
		global $_SERVER;
		if (is_dir($fileLocation)) { return WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT; }
		if (WebDAVHelper::isLocked('files', $fileLocation)) {
			WebDAVHelper::debugAddMessage('File locked: '.$fileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_LOCKED;
		}
		if (!WebDAVHelperPlugin::hasEnoughSpace($rootLocation,$_SERVER['CONTENT_LENGTH'],$maxSize)) {
			WebDAVHelper::debugAddMessage('Not enough space');
			return WebDAVHelper::$HTTP_STATUS_ERROR_INSUFFICIENT_STORAGE;
		}
		return '';
	}

	private static function _save($fileLocation) {
		$fileIsNew = !file_exists($fileLocation);
		if (!$fileIsNew && !is_writable ($fileLocation)) {
			WebDAVHelper::debugAddMessage('Cannot write to file: '.$fileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN;
		}
		if (!$fileIsNew && !is_writable ($fileLocation)) {
			WebDAVHelper::debugAddMessage('Cannot open file for writing: '.$fileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN;
		}
		$fhWrite = fopen($fileLocation, 'wb');
		$fhRead = fopen('php://input', 'rb');
		if (stream_copy_to_stream($fhRead, $fhWrite) === false) {
			fclose($fhRead);
			fclose($fhWrite);
			if ($fileIsNew) { unlink($fileLocation); }
			WebDAVHelper::debugAddMessage('Cannot write content to file: '.$fileLocation);
			WebDAVHelper::debugAddArray(error_get_last,'LastError');
			return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN;
		}
		fclose($fhRead);
		fclose($fhWrite);
		return $fileIsNew ? WebDAVHelper::$HTTP_STATUS_CREATED : WebDAVHelper::$HTTP_STATUS_NO_CONTENT;
	}
}
?>

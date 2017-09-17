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
	public static function execute($sourceFileLocation, $targetFileLocation, $rootLocation, $maxSize, $command) {
		if (is_dir($targetFileLocation)) {
			list($srcDir, $srcFile) = WebDAVHelperPlugin::getPathAndFilename($sourceFileLocation);
			$targetFileLocation = WebDAVHelper::joinDirAndFile($targetFileLocation, $srcFile);
		}
		$status = self::_check($sourceFileLocation, $targetFileLocation, $rootLocation, $maxSize, $command);
		$header = array();
		$content = '';
		$outFile = '';
		if (!$status) { $status = self::_copymove($sourceFileLocation, $targetFileLocation, $command); }
		return array($status, $header, $content, $outFile);
	}

	private static function _check($sourceFileLocation, $targetFileLocation, $rootLocation, $maxSize, $command) {
		global $_SERVER;

		list($targetDir, $targetFile) = WebDAVHelperPlugin::getPathAndFilename($targetFileLocation);
		if (!file_exists($sourceFileLocation)) {
			WebDAVHelper::debugAddMessage('Source file missing: '.$sourceFileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND;
		}
		if (!is_dir($targetDir)) {
			WebDAVHelper::debugAddMessage('Target directory missing: '.$targetDir);
			return WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND;
		}
		if (is_dir($targetFileLocation)) {
			WebDAVHelper::debugAddMessage('Target file is directory: '.$targetFileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT;
		}
		if (!empty($_SERVER['CONTENT_LENGTH'])) {
			WebDAVHelper::debugAddMessage('Content length not empty: '.$_SERVER['CONTENT_LENGTH']);
			return WebDAVHelper::$HTTP_STATUS_ERROR_UNSUPPORTED_MEDIA_TYPE;
		}
		if (file_exists($targetFileLocation)) {
			if (isset($_SERVER['HTTP_OVERWRITE'])) {
				if ($_SERVER['HTTP_OVERWRITE'] != 'T') {
					WebDAVHelper::debugAddMessage('Overwrite not allowed: '.$_SERVER['HTTP_OVERWRITE']);
					return WebDAVHelper::$HTTP_STATUS_ERROR_PRECONDITION_FAILED;
				}
			}
		}
		if (WebDAVHelper::isLocked('files', $sourceFileLocation, true)) {
			WebDAVHelper::debugAddMessage('File locked: '.$sourceFileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_LOCKED;
		}
		if (WebDAVHelper::isLocked('files', $targetFileLocation)) {
			WebDAVHelper::debugAddMessage('File locked: '.$targetFileLocation);
			return WebDAVHelper::$HTTP_STATUS_ERROR_LOCKED;
		}
		if ($command == 'COPY') {
			if (!WebDAVHelperPlugin::hasEnoughSpace($rootLocation,filesize($sourceFileLocation),$maxSize)) {
				WebDAVHelper::debugAddMessage('Not enough space: '.filesize($sourceFileLocation).' '.$maxSize);
				return WebDAVHelper::$HTTP_STATUS_ERROR_INSUFFICIENT_STORAGE;
			}
		}
		return '';
	}

	private static function _copymove($sourceFileLocation, $targetFileLocation, $command) {
		$move = ($command == 'MOVE');
		if (is_dir($sourceFileLocation)) {
			$status = self::recurse_copymove($sourceFileLocation, $targetFileLocation, $move);
		} else {
			$status = self::single_copymove($sourceFileLocation, $targetFileLocation, $move);
		}
		if (!$status) { return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN; }
		return WebDAVHelper::$HTTP_STATUS_OK;
	}

	private static function single_copymove($src,$dst,$move=false) {
		if ($move === true) {
			return rename($src, $dst);
		} else {
			return copy($src, $dst);
		}
	}

	private static function recurse_copymove($src,$dst,$move=false) {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . '/' . $file)) {
					if (self::recurse_copymove($src.DIRECTORY_SEPARATOR.$file, $dst.DIRECTORY_SEPARATOR.$file, $move) == false) {
						closedir($dir);
						return false;
					}
				} else {
					if (self::single_copymove($src.DIRECTORY_SEPARATOR.$file, $dst.DIRECTORY_SEPARATOR.$file, $move) == false) {
						closedir($dir);
						return false;
					}
				}
			}
		}
		closedir($dir);
		if ($move === true) { return rmdir($src); }
		return true;
	}
}
?>

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
 
class WebDAVHelperPlugin {
	private static $EOL = "\n";
	private static $_allowedCommands = array('GET', 'OPTIONS', 'PROPFIND');
	private $_access;
	private $_fileLocation;
	private $_contactData;
	private $_eventData;

	public function __construct($access, $fileLocation, $contactData, $eventData) {
		$this->_access = $access;
		$this->_fileLocation = $fileLocation;
		$this->_contactData = $contactData;
		$this->_eventData = $eventData;
	}

	public function handleCommand($command) {
		WebDAVHelper::debugAddMessage('Incoming file command: '.$command);
		switch($command) {
			case 'GET':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_get.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation);
			case 'OPTIONS':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_options.php', true);
				return WebDAVHelperPluginCommand::execute(self::$_allowedCommands);
			case 'PROPFIND':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_propfind.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation);
			default:
				// Unsupported command
				WebDAVHelper::debugAddMessage('Unsupported command: '.$command);
				$code = WebDAVHelper::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED;
                		$headers = array('Allow: '.join(", ", self::$_allowedCommands));
				$content = '';
				return array($code, $headers, $content);
		}
	}

	public static function getFileType($file) {
		if (!file_exists($file)) { return 'unknown'; }
		if (is_dir($file)) { return 'directory'; }
		return 'file';
	}

	public static function getDirectoryList($directory) {
		global $_SERVER;
        	$dirHandle = opendir($directory);
		$dirList = array();
		if (!$dirHandle) { return false; }
		while ($filename = readdir($dirHandle)) {
			if (is_readable($filename)) {
				$filenameWithPath = WebDAVHelper::joinDirAndFile($directory,$filename);
				$dirList[] = self::getObjectInfo($filenameWithPath);
			}
		}
		closedir($dirHandle);
		return $dirList;
	}

	public static function getObjectInfo($filenameWithPath) {
		$directory = dirname($filenameWithPath);
		$filename = str_replace(WebDAVHelper::directoryWithSlash($directory),'',$filenameWithPath);
		WebDAVHelper::debugAddMessage('Filename with path: '.$filenameWithPath);
		WebDAVHelper::debugAddMessage('Path: '.$directory);
		WebDAVHelper::debugAddMessage('Filename: '.$filename);
		$fileinfo = array();
		$fileinfo['name'] = $filename;
		$fileinfo['html_name'] = htmlspecialchars($filename);
		$fileinfo['html_ref'] = WebDAVHelper::joinDirAndFile($_SERVER['PHP_SELF'],$fileinfo['html_name']);
		$fileinfo['type'] = self::getFileType($filenameWithPath);
		$fileinfo['mime_type'] = mime_content_type($filenameWithPath);
		$fileinfo['ctime'] = filectime($filenameWithPath);
		$fileinfo['mtime'] = filemtime($filenameWithPath);
		$fileinfo['size'] = filesize($filenameWithPath);
		$perm = fileperms($filenameWithPath);
		$fileinfo['permission'] = sprintf('%o', $perm);
		$fileinfo['executable'] = substr(sprintf('%12b', $perm),3,1);
		WebDAVHelper::debugAddArray($fileinfo, 'fileinfo');
		return $fileinfo;
	}

	public static function getDepth() {
		global $_SERVER;
		if (isset($_SERVER['HTTP_DEPTH'])) {
			return $_SERVER["HTTP_DEPTH"];
		}
		return "infinity";
	}
}
?>

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
	private static $_allowedCommands = array('GET', 'OPTIONS', 'PROPFIND', 'MKCOL', 'DELETE', 'PUT', 'COPY', 'MOVE', 'LOCK', 'UNLOCK', 'PROPPATCH');
	private static $_illegalFileChars = array('..', '\\', ':', '|', '<', '>');
	private $_access;
	private $_fileLocation;
	private $_targetAccess;
	private $_targetFileLocation;
	private $_uriLocation;

	public function __construct($access, $fileLocation, $targetAccess, $targetFileLocation, $uriLocation) {
		$this->_access = $access;
		$this->_fileLocation = $fileLocation;
		$this->_targetAccess = $targetAccess;
		$this->_targetFileLocation = $targetFileLocation;
		$this->_uriLocation = $uriLocation;
	}

	public function inputsValid() {
		foreach (self::$_illegalFileChars as $illegalFileChar) {
			if (strpos($this->_fileLocation,$illegalFileChar)) { return false; }
			if (strpos($this->_targetFileLocation,$illegalFileChar)) { return false; }
		}
		return true;
	}

	public function hasAccess($command) {
		$hasAccess = '';
		switch(strtoupper($command)) {
			case 'GET':
			case 'OPTIONS':
			case 'PROPFIND':
				if ($this->_access['read']) { $hasAccess =  '1'; }
				break;
			case 'MKCOL':
				if ($this->_access['create']) { $hasAccess =  '1'; }
				break;
			case 'DELETE':
				if ($this->_access['delete']) { $hasAccess =  '1'; }
				break;
			case 'COPY':
				if (file_exists($this->_targetFileLocation) === true) {
					if ($this->_access['read'] && $this->_targetAccess['change']) { $hasAccess =  '1'; }
				} else {
					if ($this->_access['read'] && $this->_targetAccess['create']) { $hasAccess =  '1'; }
				}
				break;
			case 'MOVE':
				if (file_exists($this->_fileLocation) === true) {
					if ($this->_access['read'] && $this->_targetAccess['change'] && $this->_access['delete']) { $hasAccess =  '1'; }
				} else {
					if ($this->_access['read'] && $this->_targetAccess['create'] && $this->_access['delete']) { $hasAccess =  '1'; }
				}
				break;
			case 'PROPPATCH':
			case 'PUT':
				if (file_exists($this->_fileLocation) === true) {
					if ($this->_access['change']) { $hasAccess =  '1'; }
				} else {
					if ($this->_access['create']) { $hasAccess =  '1'; }
				}
				break;
			default:
				break;
		}
		return $hasAccess;
	}

	public function handleCommand($command) {
		switch($command) {
			case 'GET':
			case 'HEAD':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_get.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation, $this->_uriLocation, $command);
			case 'OPTIONS':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_options.php', true);
				return WebDAVHelperPluginCommand::execute(self::$_allowedCommands);
			case 'PROPFIND':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_propfind.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation, $this->_uriLocation);
			case 'MKCOL':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_mkcol.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation);
			case 'DELETE':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_delete.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation);
			case 'PUT':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_put.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation);
			case 'COPY':
			case 'MOVE':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_copymove.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation, $this->_targetFileLocation, $command);
			case 'PROPPATCH':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_proppatch.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation, $this->_uriLocation);
			default:
				// Unsupported command
				WebDAVHelper::debugAddMessage('Unsupported file command: '.$command);
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

	public static function getDirectoryList($directory, $uriLocation, $filterFiles = array(), $recursive = false) {
		global $_SERVER;
//		WebDAVHelper::debugAddMessage('directory: '.$directory);
        	$dirHandle = opendir($directory);
		$dirList = array();
		if (!$dirHandle) { return false; }
		while ($filename = readdir($dirHandle)) {
			if (!array_search($filename, $filterFiles)) {
//				WebDAVHelper::debugAddMessage('Filename: '.$filename);
				$filenameWithPath = WebDAVHelper::joinDirAndFile($directory,$filename);
				$link = WebDAVHelper::joinDirAndFile($uriLocation,$filename);
				$dirList[] = self::getObjectInfo($filenameWithPath, $link);
			}
		}
		closedir($dirHandle);
		return $dirList;
	}

	public static function getObjectInfo($filenameWithPath, $link) {
		$directory = dirname($filenameWithPath);
		$filename = str_replace(WebDAVHelper::directoryWithSlash($directory),'',$filenameWithPath);
//		WebDAVHelper::debugAddMessage('Filename with path: '.$filenameWithPath);
//		WebDAVHelper::debugAddMessage('Path: '.$directory);
//		WebDAVHelper::debugAddMessage('Filename: '.$filename);
		$fileinfo = array();
		$fileinfo['name'] = $filename;
		$fileinfo['html_name'] = htmlspecialchars($filename);
		$fileinfo['html_ref'] = $link;
		$fileinfo['type'] = self::getFileType($filenameWithPath);
		$fileinfo['mime_type'] = mime_content_type($filenameWithPath);
		$fileinfo['etag'] = md5_file($filenameWithPath);
		$fileinfo['ctime'] = filectime($filenameWithPath);
		$fileinfo['mtime'] = filemtime($filenameWithPath);
		$fileinfo['size'] = filesize($filenameWithPath);
		$perm = fileperms($filenameWithPath);
		$fileinfo['permission'] = sprintf('%o', $perm);
		$fileinfo['executable'] = substr(sprintf('%12b', $perm),3,1);
//		WebDAVHelper::debugAddArray($fileinfo, 'fileinfo');
		return $fileinfo;
	}

	public static function getDepth() {
		global $_SERVER;
		if (isset($_SERVER['HTTP_DEPTH'])) { return $_SERVER["HTTP_DEPTH"]; }
		return "infinity";
	}

	public static function getPathAndFilename($filenameWithPath) {
		$dirEntries = explode(DIRECTORY_SEPARATOR, $filenameWithPath);
//		WebDAVHelper::debugAddArray($dirEntries, 'dirEntries');
		$file = array_pop($dirEntries);
		if (!$file) { $file = array_pop($dirEntries); }
		$directory = implode(DIRECTORY_SEPARATOR, $dirEntries);
		return array($directory, $file);
	}
}
?>

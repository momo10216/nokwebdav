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
 
class WebDAVHelperPlugin {
	private static $EOL = "\n";
	private static $_allowedCommands = array('GET', 'OPTIONS', 'PROPFIND', 'MKCOL', 'DELETE', 'PUT', 'COPY', 'MOVE', 'LOCK', 'UNLOCK', 'PROPPATCH');
	private static $_illegalFileChars = array('..'.DIRECTORY_SEPARATOR, '\\', ':', '|', '<', '>', '%');
	private $_uriLocation;
	private $_rootLocation;
	private $_sourceAccess;
	private $_sourceFileLocation;
	private $_targetAccess;
	private $_targetFileLocation;
	private $_quota;

	public function __construct($uriLocation, $sourceAccess, $targetAccess, $fileData) {
		$this->_sourceAccess = $sourceAccess;
		$this->_targetAccess = $targetAccess;
		$this->_uriLocation = $uriLocation;
		$this->_sourceFileLocation = $fileData['sourceLocation'];
		if (strpos($this->_sourceFileLocation,'%')) { $this->_sourceFileLocation = rawurldecode($this->_sourceFileLocation); }
		$this->_targetFileLocation = $fileData['targetLocation'];
		if (strpos($this->_targetFileLocation,'%')) { $this->_targetFileLocation = rawurldecode($this->_targetFileLocation); }
		$this->_rootLocation = $fileData['rootLocation'];
		$this->_quota = $fileData['quota'];
	}

	public function inputsValid() {
		foreach (self::$_illegalFileChars as $illegalFileChar) {
			if (strpos($this->_sourceFileLocation,$illegalFileChar)) {
				WebDAVHelper::debugAddMessage('Illegal character ('.$illegalFileChar.') found in source file: '.$this->_sourceFileLocation);
				return false;
			}
			if (strpos($this->_targetFileLocation,$illegalFileChar)) {
				WebDAVHelper::debugAddMessage('Illegal character ('.$illegalFileChar.') found in target file: '.$this->_targetFileLocation);
				return false;
			}
		}
		return true;
	}

	public function hasAccess($command) {
		$hasAccess = '';
		switch(strtoupper($command)) {
			case 'OPTIONS':
				$hasAccess =  '1';
				break;
			case 'GET':
			case 'HEAD':
			case 'PROPFIND':
				if ($this->_sourceAccess['read']) {
					$hasAccess =  '1';
				} else {
					WebDAVHelper::debugAddMessage('No read access to "'.$this->_sourceFileLocation.'".');
				}
				break;
			case 'MKCOL':
				if ($this->_sourceAccess['create']) { $hasAccess =  '1'; }
				break;
			case 'DELETE':
				if ($this->_sourceAccess['delete']) { $hasAccess =  '1'; }
				break;
			case 'COPY':
				if (file_exists($this->_targetFileLocation) === true) {
					if ($this->_sourceAccess['read'] && $this->_targetAccess['change']) { $hasAccess =  '1'; }
				} else {
					if ($this->_sourceAccess['read'] && $this->_targetAccess['create']) { $hasAccess =  '1'; }
				}
				break;
			case 'MOVE':
				if (file_exists($this->_sourceFileLocation) === true) {
					if ($this->_sourceAccess['read'] && $this->_targetAccess['change'] && $this->_sourceAccess['delete']) { $hasAccess =  '1'; }
				} else {
					if ($this->_sourceAccess['read'] && $this->_targetAccess['create'] && $this->_sourceAccess['delete']) { $hasAccess =  '1'; }
				}
				break;
			case 'PROPPATCH':
			case 'PUT':
				if (file_exists($this->_sourceFileLocation) === true) {
					if ($this->_sourceAccess['change']) {
						$hasAccess =  '1';
					} else {
						WebDAVHelper::debugAddMessage('No change access to "'.$this->_sourceFileLocation.'".');
					}
				} else {
					if ($this->_sourceAccess['create']) {
						$hasAccess =  '1';
					} else {
						WebDAVHelper::debugAddMessage('No create access to "'.$this->_sourceFileLocation.'".');
					}
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
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files/get.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_uriLocation, $command);
			case 'OPTIONS':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files/options.php', true);
				return WebDAVHelperPluginCommand::execute(self::$_allowedCommands);
			case 'PROPFIND':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files/propfind.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_uriLocation, $this->_quota, $this->getSize($this->_rootLocation));
			case 'MKCOL':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files/mkcol.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation);
			case 'DELETE':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files/delete.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation);
			case 'PUT':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files/put.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_rootLocation, $this->_quota);
			case 'COPY':
			case 'MOVE':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files/copymove.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_targetFileLocation, $this->_rootLocation, $this->_quota, $command);
			case 'PROPPATCH':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files/proppatch.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_uriLocation);
			default:
				// Unsupported command
				WebDAVHelper::debugAddMessage('Unsupported file command: '.$command);
				$code = WebDAVHelper::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED;
                		$headers = array('Allow: '.join(", ", self::$_allowedCommands));
				$content = '';
				$outFile = '';
				return array($code, $headers, $content, $outFile);
		}
	}

	public static function getFileType($file) {
		if (!file_exists($file)) { return 'unknown'; }
		if (is_dir($file)) { return 'directory'; }
		return 'file';
	}

	public static function getDirectoryList($filename, $link, $targetDepth, $currentDepth=0, $addCurrentObject=true) {
		global $_SERVER;
//		WebDAVHelper::debugAddMessage('filename: '.$filename);
		$dirList = array();
		if ($addCurrentObject) {
			$dirList[] = self::getObjectInfo($filename, $link);
		}
		if (is_dir($filename) && (($targetDepth == 'infinity') || ($currentDepth < $targetDepth))) {
			$files = scandir($filename);
			foreach($files as $file) {
				$file = trim($file,DIRECTORY_SEPARATOR);
				if (($file != '.') && ($file != '..')) {
					$newFilename = rtrim($filename,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
					$newLink = rtrim($link,'/').'/'.$file;
					$subDirList = self::getDirectoryList($newFilename, $newLink, $targetDepth, ($currentDepth+1), true);
					$dirList = array_merge($dirList, $subDirList);
				}
			}
		}
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
		$fileinfo['ref'] = $link;
		$fileinfo['html_name'] = htmlspecialchars($filename,ENT_XML1,'UTF-8');
		$fileinfo['html_ref'] = htmlspecialchars($link,ENT_XML1,'UTF-8');
		$fileinfo['type'] = self::getFileType($filenameWithPath);
		$fileinfo['mime_type'] = self::_getMimeType($filenameWithPath);
		if ($fileinfo['type'] != 'directory') {
			$fileinfo['etag'] = md5_file($filenameWithPath);
		}
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

	public static function getSize($path) {
		if (is_dir(rtrim($path,DIRECTORY_SEPARATOR))) {
			$total_size = 0;
			$files = scandir($path);
			foreach($files as $file) {
				if (($file != '.') && ($file != '..')) {
					$total_size += self::getSize(rtrim($path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file);
				}
			}
			return $total_size+filesize($path);
		} else {
			if (!file_exists($path)) { return 0; }
			return filesize($path);
		}
	}

	public static function hasEnoughSpace($directory, $size, $maxSize) {
//		WebDAVHelper::debugAddMessage('hasEnoughSpace: directory='.$directory);
//		WebDAVHelper::debugAddMessage('hasEnoughSpace: size='.$size);
//		WebDAVHelper::debugAddMessage('hasEnoughSpace: maxSize='.$maxSize);
		if (empty($maxSize) || $maxSize <= 0) { return true; }
		if ((self::getSize($directory)+$size) <= $maxSize) { return true; }
		return false;
	}

	public static function hrefEncodeFile($filename) {
		return rawurlencode($filename);
	}

	private static function _getMimeType($filename) {
		$defaultType = 'application/octet-stream';
		$mimeType = mime_content_type($filename);
		if ($mimeType == 'directory') { return $defaultType; }
		if (($mimeType != 'inode/x-empty') && ($mimeType != $defaultType)) { return $mimeType; }
		$knownMimeTypes = array(
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',

			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',

			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',

			// ms office
			'doc' => 'application/msword',
			'docx' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			'pptx' => 'application/vnd.ms-powerpoint',

			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		if (isset($knownMimeTypes[$extension])) { return $knownMimeTypes[$extension]; }
		return $defaultType;
	}
}
?>

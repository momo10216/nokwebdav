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
	private static $_allowedCommands = array('GET','OPTIONS');
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
		switch($command) {
			case 'GET':
				return $this->_handleGET();
			case 'OPTIONS':
				return $this->_handleOPTIONS();
			default:
				// Unsupported command
				$code = WebDAVHelper::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED;
                		$headers = array('Allow: '.join(", ", self::$_allowedCommands));
				$content = '';
				return array($code, $headers, $content);
		}
	}

	private function _handleGET() {
		WebDAVHelper::debugAddMessage('GET: '.$this->_fileLocation);
		WebDAVHelper::debugAddMessage('GET: '.$this->_getFileType($this->_fileLocation));
		switch($this->_getFileType($this->_fileLocation)) {
			case 'file':
				return $this->_getFile($this->_fileLocation);
			case 'directory':
				return $this->_getDirectory($this->_fileLocation);
			default:
				return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), '');
		}
		return array(WebDAVHelper::$HTTP_STATUS_OK, array(), '');
	}

	private function _handleOPTIONS() {
		$status = WebDAVHelper::$HTTP_STATUS_OK;
		$header = array(
			'Allow: '.join(", ", $this->_allowedCommands),
			'DAV: '.join(", ", WebDAVHelper::$DAV_SUPPORTED_PROTOCOLS),
			'MS-Author-Via: DAV'
		);
		$content = '';
		return array($status, $header, $content);
	}

	private function _getFileType($file) {
		if (!file_exists($file)) { return 'missing'; }
		if (is_dir($file)) { return 'directory'; }
		return 'file';
	}

	private function _getDirectory($directory) {
		global $_SERVER;
		$params = '';
		if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) { $params = '?'.$_SERVER['QUERY_STRING']; }
        	$dirHandle = opendir($directory);
		if (!$dirHandle) { return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), ''); }
		$saveDir = htmlspecialchars($directory);
		$displayFormat = "%15s  %-19s  %-s".self::$EOL;
		$content = '<html><head><title>Index of '.$saveDir.'</title></head>'.self::$EOL;
		$content .= '<h1>Index of '.$saveDir.'</h1>'.self::$EOL;
		$content .= '<pre>';
		$content .= sprintf($displayFormat, "Size", "Last modified", "Filename");
		$content .= '<hr>';
		while ($filename = readdir($dirHandle)) {
			if ($filename != '.' && $filename != '..') {
				$filenameWithPath = $directory."/".$filename;
				$saveFilename = htmlspecialchars($filename);
				$content .= sprintf($displayFormat,
					number_format(filesize($filenameWithPath)),
					strftime("%Y-%m-%d %H:%M:%S", filemtime($filenameWithPath)),
					'<a href="'.$_SERVER['PHP_SELF'].'/'.$saveFilename.$params.'">'.$saveFilename.'</a>'
				);
			}
		}
		$content .= '</pre>';
		closedir($dirHandle);
		$content .= '</html>'.self::$EOL;
		return array(WebDAVHelper::$HTTP_STATUS_OK, array(), $content);
	}

	private function _getFile($filename) {
		$header = array();
		$header[] = 'Content-type: '.mime_content_type($filename);
		$header[] = 'Content-length: '.filesize($filename);
		$header[] = 'Last-modified: '.gmdate("D, d M Y H:i:s", filemtime($filename))." GMT";
		$content = file_get_contents($filename);
		return array(WebDAVHelper::$HTTP_STATUS_OK, $header, $content);
	}
}
?>

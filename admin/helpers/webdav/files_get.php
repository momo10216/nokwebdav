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
	private static $EOL = "\n";
	private static $_allowedCommands = array('GET','OPTIONS');
	private $_access;
	private $_fileLocation;
	private $_contactData;
	private $_eventData;

	public function execute($fileLocation)) {
		WebDAVHelper::debugAddMessage('GET: '.$fileLocation);
		WebDAVHelper::debugAddMessage('GET: '.$this->_getFileType($fileLocation));
		switch($this->_getFileType($fileLocation)) {
			case 'file':
				return $this->_getFile($fileLocation);
			case 'directory':
				return $this->_getDirectory($fileLocation);
			default:
				return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), '');
		}
		return array(WebDAVHelper::$HTTP_STATUS_OK, array(), '');
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

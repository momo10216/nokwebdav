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
 
class WebDAVGetHelper {
	private static $EOL = "\n";

	public static function getResponse() {
		$location = self::_getLocation();
		WebDAVHelper::debugAddMessage('GET: '.$location);
		WebDAVHelper::debugAddMessage('GET: '.self::_getFileType($location));
		switch(self::_getFileType($location)) {
			case 'file':
				return self::_getFile($location);
			case 'directory':
				return self::_getDirectory($location);
			default:
				return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), '');
		}
		return array(WebDAVHelper::$HTTP_STATUS_OK, array(), '');
	}

	private static function _getLocation() {
		global $_SERVER;
		$language = JFactory::getLanguage();
		$shortlang = explode('-',$language->getTag())[0];
		$location = empty($_SERVER["PATH_INFO"]) ? '/' : $_SERVER["PATH_INFO"];
		$location = str_replace('/'.$shortlang,'',$location);
		return $location;
	}

	private static function _getFileType($file) {
		if (!file_exists($file)) { return 'missing'; }
		if (is_dir($file)) { return 'directory'; }
		return 'file';
	}

	private static function _getDirectory($directory) {
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

	private static function _getFile($filename) {
		$header = array();
		$header[] = 'Content-type: '.mime_content_type($filename);
		$header[] = 'Content-length: '.filesize($filename);
		$header[] = 'Last-modified: '.gmdate("D, d M Y H:i:s", filemtime($filename))." GMT";
		$content = file_get_contents($filename);
		return array(WebDAVHelper::$HTTP_STATUS_OK, $header, $content);
	}
}
?>

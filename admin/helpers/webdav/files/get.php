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

	public static function execute($fileLocation, $uriLocation, $command) {
		switch(WebDAVHelperPlugin::getFileType($fileLocation)) {
			case 'file':
				return self::_getFile($fileLocation,$command);
			case 'directory':
				return self::_getDirectory($fileLocation, $uriLocation, $command);
			default:
				return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), '');
		}
		return array(WebDAVHelper::$HTTP_STATUS_OK, array(), '', '');
	}

	private static function _getDirectory($directory, $uriLocation, $command) {
		if ($command == 'HEAD') { return array(WebDAVHelper::$HTTP_STATUS_OK, array(), ''); }
		$dirEntries = WebDAVHelperPlugin::getDirectoryList($directory, $uriLocation);
		if ($dirEntries === false) { return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), ''); }
		$title = 'Index of '.htmlspecialchars($directory);
		$displayFormat = "%15s  %-19s  %-s".self::$EOL;
		$content = '<html><head><title>'.$title.'</title></head>'.self::$EOL;
		$content .= '<h1>'.$title.'</h1>'.self::$EOL;
		$content .= '<pre>';
		$content .= sprintf($displayFormat, "Size", "Last modified", "Filename");
		$content .= '<hr>';
		foreach($dirEntries as $dirEntry) {
			if (($dirEntry['name'] != '.') && ($dirEntry['name'] != '..')) {
				$content .= sprintf($displayFormat,
					number_format($dirEntry['size']),
					strftime("%Y-%m-%d %H:%M:%S", $dirEntry['mtime']),
					'<a href="'.$dirEntry['html_ref'].'">'.$dirEntry['html_name'].'</a>'
				);
			}
		}
		$content .= '</pre>';
		$content .= '</html>'.self::$EOL;
		return array(WebDAVHelper::$HTTP_STATUS_OK, array(), $content);
	}

	private static function _getFile($filename,$command) {
		$header = array();
		$header[] = 'Content-type: '.mime_content_type($filename);
		$header[] = 'Last-modified: '.gmdate("D, d M Y H:i:s", filemtime($filename))." GMT";
		if ($command == 'HEAD') { return array(WebDAVHelper::$HTTP_STATUS_OK, $header, '', ''); }
		return array(WebDAVHelper::$HTTP_STATUS_OK, $header, '', $filename);
	}
}
?>

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
		$files = scandir($directory);
		$dirEntries = array();
		foreach($files as $file) {
			$file = trim($file,DIRECTORY_SEPARATOR);
			if (($file != '.') && ($file != '..')) {
				$newFilename = rtrim($directory,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
				$newLink = rtrim($uriLocation,'/').'/'.WebDAVHelperPlugin::hrefEncodeFile($file);
				$subDirList = WebDAVHelperPlugin::getDirectoryList($newFilename, $newLink, 0, 0);
				$dirEntries = array_merge($dirEntries, $subDirList);
			}
		}
		if (count($dirEntries) < 1) { return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), ''); }
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
		$rangesCount = self::_getRangesCount($filename);
		if ($rangesCount == 0) { return self::_getFileFull($filename,$command); }
		if ($rangesCount == 1) { return self::_getFileSingleRange($filename,$command); }
		return self::_getFileMultiRange($filename,$command);
	}

	private static function _getFileFull($filename,$command) {
		$header = array();
		$header[] = 'Content-type: '.mime_content_type($filename);
		$header[] = 'Last-modified: '.gmdate("D, d M Y H:i:s", filemtime($filename))." GMT";
		if ($command == 'HEAD') { return array(WebDAVHelper::$HTTP_STATUS_OK, $header, '', ''); }
		return array(WebDAVHelper::$HTTP_STATUS_OK, $header, '', $filename);
	}

	private static function _getFileSingleRange($filename,$command) {
		$header = array();
		$content = '';
		$header[] = 'Content-type: '.mime_content_type($filename);
		$header[] = 'Last-modified: '.gmdate("D, d M Y H:i:s", filemtime($filename))." GMT";
		list($rangeStart, $rangeEnd) = self::_getRanges($filename)[0];
		$fh = fopen($filename, "r");
		if (fseek($fh, 0, SEEK_SET) == 0) {
			fseek($fh, $rangeStart, SEEK_SET);
			if (feof($fh)) {
				fclose($fh);
				return array(WebDAVHelper::$HTTP_STATUS_ERROR_REQUESTED_RANGE_NOT_SATISFIABLE, array(), '', '');
			}
			$content = fread($fh, ($rangeEnd-$rangeStart));
			fclose($fh);
			$header[] = 'Content-range: '.$rangeStart.'-'.$rangeEnd.'/'.filesize($filename);
		} else {
			fclose($fh);
			return array(WebDAVHelper::$HTTP_STATUS_OK, $header, '', $filename);
		}
		return array(WebDAVHelper::$HTTP_STATUS_OK, $header, $content, '');
	}

	private static function _getFileMultiRange($filename,$command) {
		$header = array();
		$content = '';
		$fh = fopen($filename, "r");
		if (fseek($fh, 0, SEEK_SET) == 0) {
			$multipartSeparator = "MULTIPART_SEPARATOR_".md5(microtime());
			$header[] = 'Content-type: multipart/byteranges; boundary='.$multipartSeparator;
			$ranges = self::_getRanges($filename);
			foreach($ranges as $range) {
				list($rangeStart, $rangeEnd) = $range;
				fseek($fh, $rangeStart, SEEK_SET);
				if (feof($fh)) {
					fclose($fh);
					return array(WebDAVHelper::$HTTP_STATUS_ERROR_REQUESTED_RANGE_NOT_SATISFIABLE, array(), '', '');
				}
				$content .= self::_getFileMultiRangeEntry($multipartSeparator,$filename,$rangeStart,$rangeEnd,fread($fh, ($rangeEnd-$rangeStart)));
			}
			$content .= "\n--".$multipartSeparator."--";
		} else {
			fclose($fh);
			$header[] = 'Content-type: '.mime_content_type($filename);
			$header[] = 'Last-modified: '.gmdate("D, d M Y H:i:s", filemtime($filename))." GMT";
			return array(WebDAVHelper::$HTTP_STATUS_OK, $header, '', $filename);
		}
		fclose($fh);
		return array(WebDAVHelper::$HTTP_STATUS_OK, $header, $content, '');
	}

	private static function _getFileMultiRangeEntry($multipartSeparator,$filename,$from,$to,$rangeContent) {
		$content = "\n--".$multipartSeparator."\n";
		$content .= "Content-type: ".mime_content_type($filename)."\n";
		$content .= "Content-range: ".$from."-".$to."/".filesize($filename);
		$content .= "\n\n".$rangeContent;
		return $content;
	}

	private static function _getRangesCount($filename) {
		return count(self::_getRanges($filename));
	}

	private static function _getRanges($filename) {
		global $_SERVER;
		$retval = array();
		if (isset($_SERVER['HTTP_RANGE'])) {
			// Only "bytes" supported
			if (preg_match('/bytes\s*=\s*(.+)/', $_SERVER['HTTP_RANGE'], $matches)) {
				$ranges = explode(",",$matches[1]);
				foreach($ranges as $range) {
					list($start,$end) = explode('-',$range,2);
					if (empty($start)) { $start = '0'; }
					if (empty($end)) { $end = filesize($filename); }
					$retval[] = array($start,$end);
				}
			}
		}
		return $retval;
	}
}
?>

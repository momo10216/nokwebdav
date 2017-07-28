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

	public static function execute($directory) {
		$propertiesRequested = self::_parseInfo();
		if ($propertiesRequested === false) { return array(WebDAVHelper::$HTTP_STATUS_ERROR_BAD_REQUEST, array(), ''); }
		return self::_generateAnswer($directory, $propertiesRequested);
	}

	private static function _parseInfo() {
		$input = file_get_contents('php://input');
		//WebDAVHelper::debugAddMessage('Propfind input: '.$input);
		$dom = new DOMDocument();
		if (!$dom->loadXML($input)) { return false; }
		$elementList = $dom->getElementsByTagName('allprop');
		if ($elementList->length > 0) { return 'all'; }
		$elementList = $dom->getElementsByTagName('prop');
		$info = array();
		if ($elementList->length > 0) {
			for($i=0 ; $i<$elementList->length ; $i++) {
				$element = $elementList->item($i);
				$elementChildList = $element->childNodes;
				if ($elementChildList->length > 0) {
					for($j=0 ; $j<$elementChildList->length ; $j++) {
						$elementChild = $elementChildList->item($j);
						if ($elementChild->nodeName != '#text') {
							if (strpos($elementChild->nodeName, ':')) {
								$info[] = explode(':',$elementChild->nodeName,2)[1];
							} else {
								$info[] = $elementChild->nodeName;
							}
						}
					}
				}
			}
		}
		if (count($info) < 1) { return 'all'; }
		WebDAVHelper::debugAddArray($info,'Requested properties: ');
		return $info;
	}

	private static function _generateAnswer($directory, $propertiesRequested) {
		$status = WebDAVHelper::$HTTP_STATUS_OK_MULTI_STATUS;
		$header = array('Content-Type: text/xml; charset="utf-8');
		$content = '<?xml version="1.0" encoding="utf-8"?>'.self::$EOL;
		WebDAVHelper::debugAddMessage('Depth: '.WebDAVHelperPlugin::getDepth());
		WebDAVHelper::debugAddMessage('Directory: '.$directory);
		$content .= '<d:multistatus xmlns:d="DAV:">'.self::$EOL;
		switch (WebDAVHelperPlugin::getDepth()) {
			case '0': // Single object info
				$content .= self::_getSingleInfo($directory, $propertiesRequested);
				break;
			case '1': // Directory info
				$content .= self::_getDirectoryInfo($directory, $propertiesRequested, false);
				break;
			case 'infinity': // Recursive directory info
			default:
				$content .= self::_getDirectoryInfo($directory, $propertiesRequested, true);
				break;
		}
		$content .= '</d:multistatus>'.self::$EOL;
		WebDAVHelper::debugAddMessage('Propfind output: '.$content);
		return array($status, $header, $content);
	}

	private static function _getSingleInfo($directory, $propertiesRequested) {
		return self::_getResponse(WebDAVHelperPlugin::getObjectInfo($directory), $propertiesRequested);
	}

	private static function _getDirectoryInfo($directory, $propertiesRequested, $recursive = false) {
		$content = '';
		$dirEntries = WebDAVHelperPlugin::getDirectoryList($directory);
		if ($dirEntries === false) { return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), ''); }
		foreach ($dirEntries as $dirEntry) {
			if (($dirEntry['name'] != '.') && ($dirEntry['name'] != '..')) {
				$content .= self::_getResponse($dirEntry, $propertiesRequested);
			}
		}
		return $content;
	}
	private static function _getResponse($dirEntry, $propertiesRequested) {
		$content = '';
		$content .= '	<d:response>'.self::$EOL;
		$content .= '		<d:href>'.$dirEntry['html_ref'].'</d:href>'.self::$EOL;
		$content .= self::_getProperties($dirEntry, $propertiesRequested, "\t\t");
		$content .= '	</d:response>'.self::$EOL;
		return $content;
	}

	private static function _getProperties($dirEntry, $propertiesRequested, $prefix) {
		$datens =  'xmlns:b="urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882" b:dt="dateTime.rfc1123"';
		$content = $prefix.'<d:propstat>'.self::$EOL;
		$content .= $prefix.'	<d:prop>'.self::$EOL;
		$unknowns = array();
		foreach ($propertiesRequested as $propertyRequested) {
			switch($propertyRequested) {
				case 'displayname';
					$content .= $prefix.'		<d:displayname>'.$dirEntry['name'].'</d:displayname>'.self::$EOL;
					break;
				case 'getcontentlength';
					$content .= $prefix.'		<d:getcontentlength>'.$dirEntry['size'].'</d:getcontentlength>'.self::$EOL;
					break;
				case 'getlastmodified';
					$content .= $prefix.'		<d:getlastmodified '.$datens.'>'.gmdate('D, d M Y H:i:s', $dirEntry['mtime']).' GMT</d:getlastmodified>'.self::$EOL;
					break;
				case 'resourcetype';
					$resourcetype = '';
					if ($dirEntry['mime_type'] == 'directory') { $resourcetype = '<d:collection />'; }
					$content .= $prefix.'		<d:resourcetype>'.self::$EOL;
					$content .= $prefix.'			'.$resourcetype.self::$EOL;
					$content .= $prefix.'		</d:resourcetype>'.self::$EOL;
					break;
				case 'executable';
					if ($dirEntry['executable'] == '1') {
						$content .= $prefix.'		<d:executable />'.self::$EOL;
					}
					break;
				case 'creationdate';
					$content .= $prefix.'		<d:creationdate '.$datens.'>'.gmdate('D, d M Y H:i:s', $dirEntry['ctime']).' GMT</d:creationdate>'.self::$EOL;
					break;
				default:
					// Unsupported property
					$unknowns[] = $propertyRequested;
					break;
			}
		}
		$content .= $prefix.'	</d:prop>'.self::$EOL;
		$content .= $prefix.'	<d:status>HTTP/1.1 200 OK</d:status>'.self::$EOL;
		$content .= $prefix.'</d:propstat>'.self::$EOL;
		if (count($unknowns) > 0) {
			$content .= $prefix.'<d:propstat>'.self::$EOL;
			$content .= $prefix.'	<d:prop>'.self::$EOL;
			$content .= $prefix.'		<d:executable xmlns="http://apache.org/dav/props/" />'.self::$EOL;
			foreach ($unknowns as $unknown) {
				$content .= $prefix.'		<d:'.$unknown.' />'.self::$EOL;
			}
			$content .= $prefix.'	</d:prop>'.self::$EOL;
			$content .= $prefix.'	<d:status>HTTP/1.1 404 Not Found</d:status>'.self::$EOL;
			$content .= $prefix.'</d:propstat>'.self::$EOL;
		}
		return $content;
	}
}
?>

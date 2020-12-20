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
	public static function execute($directory, $uriLocation, $quota, $usedSize) {
		$directory = rtrim($directory,"/");
		$propertiesRequested = self::_parseInfo();
		if ($propertiesRequested === false) { return array(WebDAVHelper::$HTTP_STATUS_ERROR_BAD_REQUEST, array(), ''); }
		if ($propertiesRequested == 'all') { $propertiesRequested = self::_getAllProperties($directory); }
		return self::_generateAnswer($directory, $uriLocation, $propertiesRequested, $quota, $usedSize);
	}

	private static function _getAllProperties($directory) {
		$allProperties = array('displayname', 'getcontentlength', 'getcontenttype', 'resourcetype', 'executable',
			'creationdate', 'getlastmodified', 'getetag', 'supportedlock', 'quota-used-bytes', 'quota-available-bytes');
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('name')))
			->from('#__nokWebDAV_properties')
			->where($db->quoteName('resourcetype').'='.$db->quote('files').' AND '.$db->quoteName('resourcelocation').'='.$db->quote($directory));
		$db->setQuery($query);
		$properties = $db->loadObjectList();
		if ($properties) {
			foreach ($properties as $property) {
				$allProperties[] = $property->name;
			}
		}
		return $allProperties;
	}

	private static function _parseInfo() {
		$input = file_get_contents('php://input');
		WebDAVHelper::debugAddMessage('Propfind input: '.$input);
		if (empty($input)) { return 'all'; }
		$dom = new DOMDocument();
		if (!$dom->loadXML($input, LIBXML_NOWARNING)) { return false; }
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
							$info[] = $elementChild->localName;
						}
					}
				}
			}
		}
		if (count($info) < 1) { return 'all'; }
		WebDAVHelper::debugAddArray($info,'Requested properties: ');
		return $info;
	}

	private static function _generateAnswer($directory, $uriLocation, $propertiesRequested, $quota, $usedSize) {
		$status = WebDAVHelper::$HTTP_STATUS_OK_MULTI_STATUS;
		$header = array('Content-Type: text/xml; charset="utf-8"');
		$content = WebDAVHelper::xmlPreamble();
		$depth = WebDAVHelperPlugin::getDepth();
		WebDAVHelper::debugAddMessage('Location: '.$directory);
//		WebDAVHelper::debugAddMessage('Quota: '.$quota);
		$content .= WebDAVHelper::xmlFormat('<d:multistatus xmlns:d="DAV:">');
		$filetype = WebDAVHelperPlugin::getFileType($directory);
		if ($filetype == 'file') { $depth = '0'; }
		WebDAVHelper::debugAddMessage('Depth: '.$depth);
		if ($filetype == 'unknown') { return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), '', ''); }
		$dirEntries = WebDAVHelperPlugin::getDirectoryList($directory, $uriLocation, $depth, 0);
		$content .= self::_getDirectoryInfo($directory, $uriLocation, $propertiesRequested, $dirEntries, $quota, $usedSize);
		$content .= WebDAVHelper::xmlFormat('</d:multistatus>');
		return array($status, $header, $content, '');
	}

	private static function _getDirectoryInfo($directory, $uriLocation, $propertiesRequested, $dirEntries, $quota, $usedSize) {
		$content = '';
		if (count($dirEntries) > 1) {
			foreach ($dirEntries as $dirEntry) {
				$content .= self::_getResponse(WebDAVHelper::joinDirAndFile($directory, $dirEntry['name']), $dirEntry, $propertiesRequested, WebDAVHelper::$HTTP_STATUS_OK, $quota, $usedSize);
			}
		} else {
			if (count($dirEntries) > 0) {
				$content .= self::_getResponse($directory, $dirEntries[0], $propertiesRequested, WebDAVHelper::$HTTP_STATUS_OK, $quota, $usedSize);
			}
		}
		if (empty($content)) { $content .= WebDAVHelper::xmlFormat('<d:response />',1); }
		return $content;
	}

	private static function _getResponse($filename, $dirEntry, $propertiesRequested, $status, $quota, $usedSize) {
		$href = $dirEntry['html_ref'];
		if (strpos($href,'%')) { $href = rawurldecode($href); }
		$content = '';
		$content .= WebDAVHelper::xmlFormat('<d:response>',1);
		$content .= WebDAVHelper::xmlFormat('<d:href>'.$href.'</d:href>',2);
		$content .= self::_getProperties($filename, $dirEntry, $propertiesRequested, $status, $quota, $usedSize);
		$content .= WebDAVHelper::xmlFormat('</d:response>',1);
		return $content;
	}

	private static function _getProperties($filename, $dirEntry, $propertiesRequested, $status, $quota, $usedSize) {
		$datens =  'xmlns:b="urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882" b:dt="dateTime.rfc1123"';
		$content = WebDAVHelper::xmlFormat('<d:propstat>', 2);
		$content .= WebDAVHelper::xmlFormat('<d:prop>', 3);
		$unknowns = array();
		foreach ($propertiesRequested as $propertyRequested) {
			switch($propertyRequested) {
				case 'displayname':
					$content .= WebDAVHelper::xmlFormat('<d:displayname>'.$dirEntry['html_name'].'</d:displayname>', 4);
					break;
				case 'getcontentlength':
					$content .= WebDAVHelper::xmlFormat('<d:getcontentlength>'.$dirEntry['size'].'</d:getcontentlength>', 4);
					break;
				case 'getcontenttype':
					$content .= WebDAVHelper::xmlFormat('<d:getcontenttype>'.$dirEntry['mime_type'].'</d:getcontenttype>', 4);
					break;
				case 'resourcetype':
					if ($dirEntry['type'] == 'directory') {
						$content .= WebDAVHelper::xmlFormat('<d:resourcetype><d:collection /></d:resourcetype>', 4);
					} else {
						$content .= WebDAVHelper::xmlFormat('<d:resourcetype />', 4);
					}
					break;
				case 'executable':
					if ($dirEntry['executable'] == '1') {
						$content .= WebDAVHelper::xmlFormat('<d:executable />', 4);
					}
					break;
				case 'creationdate':
					$content .= WebDAVHelper::xmlFormat('<d:creationdate '.$datens.'>'.gmdate('D, d M Y H:i:s', $dirEntry['ctime']).' GMT</d:creationdate>', 4);
					break;
				case 'getetag':
					if (isset($dirEntry['etag'])) {
						$content .= WebDAVHelper::xmlFormat('<d:getetag>'.$dirEntry['etag'].'</d:getetag>', 4);
					} else {
						$content .= WebDAVHelper::xmlFormat('<d:getetag />', 4);
					}
					break;
				case 'supportedlock':
//					if ($dirEntry['type'] == 'directory') {
//						$content .= WebDAVHelper::xmlFormat('<d:supportedlock />', 4);
//					} else {
						$content .= WebDAVHelper::xmlFormat('<d:supportedlock>', 4);
						$content .= WebDAVHelper::xmlFormat('<d:lockentry>', 5);
						$content .= WebDAVHelper::xmlFormat('<d:lockscope><d:exclusive /></d:lockscope>', 6);
						$content .= WebDAVHelper::xmlFormat('<d:locktype><d:write /></d:locktype>', 6);
						$content .= WebDAVHelper::xmlFormat('</d:lockentry>', 5);
						$content .= WebDAVHelper::xmlFormat('<d:lockentry>', 5);
						$content .= WebDAVHelper::xmlFormat('<d:lockscope><d:shared /></d:lockscope>', 6);
						$content .= WebDAVHelper::xmlFormat('<d:locktype><d:write /></d:locktype>', 6);
						$content .= WebDAVHelper::xmlFormat('</d:lockentry>', 5);
						$content .= WebDAVHelper::xmlFormat('</d:supportedlock>', 4);
//					}
					break;
				case 'quota-used-bytes':
					$content .= WebDAVHelper::xmlFormat('<d:quota-used-bytes>'.$usedSize.'</d:quota-used-bytes>', 4);
					break;
				case 'quota-available-bytes':
					if (!empty($quota) && ($quota > 0)) {
						$content .= WebDAVHelper::xmlFormat('<d:quota-available-bytes>'.$quota.'</d:quota-available-bytes>', 4);
					} else {
						$unknowns[] = 'quota-available-bytes';
					}
					break;
				default:
					// Search in DB
					list($found, $value, $ns) = self::_getPropertyFromDatabase($filename, $propertyRequested);
					if ($found === false) {
						// Unsupported property
						$unknowns[] = $propertyRequested;
					} else {
						if (!empty($ns && $ns != 'DAV:')) {
							$content .= WebDAVHelper::xmlFormat('<b:'.$propertyRequested.' xmlns:b="'.$ns.'">'.$value.'</b:'.$propertyRequested.'>', 4);
						} else {
							$content .= WebDAVHelper::xmlFormat('<d:'.$propertyRequested.'>'.$value.'</d:'.$propertyRequested.'>', 4);
						}
					}
					break;
			}
		}
		$content .= WebDAVHelper::xmlFormat('</d:prop>', 3);
		$content .= WebDAVHelper::xmlFormat('<d:status>HTTP/1.1 '.WebDAVHelper::getStatus($status).'</d:status>', 3);
		$content .= WebDAVHelper::xmlFormat('</d:propstat>', 2);
		if (count($unknowns) > 0) {
			$content .= WebDAVHelper::xmlFormat('<d:propstat>', 2);
			$content .= WebDAVHelper::xmlFormat('<d:prop>', 3);
			$content .= WebDAVHelper::xmlFormat('<executable xmlns="http://apache.org/dav/props/" />', 4);
			foreach ($unknowns as $unknown) {
				$content .= WebDAVHelper::xmlFormat('<d:'.$unknown.' />', 4);
			}
			$content .= WebDAVHelper::xmlFormat('</d:prop>', 3);
			$content .= WebDAVHelper::xmlFormat('<d:status>HTTP/1.1 404 Not Found</d:status>', 3);
			$content .= WebDAVHelper::xmlFormat('</d:propstat>', 2);
		}
		return $content;
	}

	private static function _getPropertyFromDatabase($filename, $propName) {
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('namespace','value')))
			->from('#__nokWebDAV_properties')
			->where($db->quoteName('resourcetype').'='.$db->quote('files').' AND '.$db->quoteName('resourcelocation').'='.$db->quote($filename).' AND '.$db->quoteName('name').'='.$db->quote($propName));
		$db->setQuery($query);
//		WebDAVHelper::debugAddQuery($query);
		$property = $db->loadObject();
		if (!$property) { return array(false,'',''); }
		return array(true, $property->value, $property->namespace);
	}
}
?>

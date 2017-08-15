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

	public static function execute($directory, $uriLocation, $quota) {
		$directory = rtrim($directory,"/");
		$propertiesRequested = self::_parseInfo();
		if ($propertiesRequested === false) { return array(WebDAVHelper::$HTTP_STATUS_ERROR_BAD_REQUEST, array(), ''); }
		if ($propertiesRequested == 'all') { $propertiesRequested = self::_getAllProperties($directory); }
		return self::_generateAnswer($directory, $uriLocation, $propertiesRequested, $quota);
	}

	private static function _getAllProperties($directory) {
		$allProperties = array('displayname', 'getcontentlength', 'getcontenttype', 'resourcetype', 'executable',
			'creationdate', 'getlastmodified', 'getetag', 'supportedlock');
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

	private static function _generateAnswer($directory, $uriLocation, $propertiesRequested, $quota) {
		$status = WebDAVHelper::$HTTP_STATUS_OK_MULTI_STATUS;
		$header = array('Content-Type: text/xml; charset="utf-8"');
		$content = '<?xml version="1.0" encoding="utf-8"?>'.self::$EOL;
		$depth = WebDAVHelperPlugin::getDepth();
		WebDAVHelper::debugAddMessage('Location: '.$directory);
//		WebDAVHelper::debugAddMessage('Quota: '.$quota);
		$content .= '<d:multistatus xmlns:d="DAV:">'.self::$EOL;
		$filetype = WebDAVHelperPlugin::getFileType($directory);
		if ($filetype == 'file') { $depth = '0'; }
		WebDAVHelper::debugAddMessage('Depth: '.$depth);
		if ($filetype == 'unknown') { return array(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND, array(), ''); }
		$dirEntries = WebDAVHelperPlugin::getDirectoryList($directory, $uriLocation, $depth, 0);
		$content .= self::_getDirectoryInfo($directory, $uriLocation, $propertiesRequested, $dirEntries, $quota);
		$content .= '</d:multistatus>'.self::$EOL;
		return array($status, $header, $content, '');
	}

	private static function _getDirectoryInfo($directory, $uriLocation, $propertiesRequested, $dirEntries, $quota) {
		$content = '';
		foreach ($dirEntries as $dirEntry) {
			$content .= self::_getResponse(WebDAVHelper::joinDirAndFile($directory, $dirEntry['name']), $dirEntry, $propertiesRequested, WebDAVHelper::$HTTP_STATUS_OK, $quota);
		}
		if (empty($content)) { $content .= '	<d:response />'.self::$EOL; }
		return $content;
	}

	private static function _getResponse($filename, $dirEntry, $propertiesRequested, $status, $quota) {
		$content = '';
		$content .= '	<d:response>'.self::$EOL;
		$content .= '		<d:href>'.$dirEntry['html_ref'].'</d:href>'.self::$EOL;
		$content .= self::_getProperties($filename, $dirEntry, $propertiesRequested, "\t\t", $status, $quota);
		$content .= '	</d:response>'.self::$EOL;
		return $content;
	}

	private static function _getProperties($filename, $dirEntry, $propertiesRequested, $prefix, $status, $quota) {
		$datens =  'xmlns:b="urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882" b:dt="dateTime.rfc1123"';
		$content = $prefix.'<d:propstat>'.self::$EOL;
		$content .= $prefix.'	<d:prop>'.self::$EOL;
		$unknowns = array();
		foreach ($propertiesRequested as $propertyRequested) {
			switch($propertyRequested) {
				case 'displayname':
					$content .= $prefix.'		<d:displayname>'.$dirEntry['html_name'].'</d:displayname>'.self::$EOL;
					break;
				case 'getcontentlength':
					$content .= $prefix.'		<d:getcontentlength>'.$dirEntry['size'].'</d:getcontentlength>'.self::$EOL;
					break;
				case 'getcontenttype':
					$content .= $prefix.'		<d:getcontenttype>'.$dirEntry['mime_type'].'</d:getcontenttype>'.self::$EOL;
					break;
				case 'resourcetype':
					if ($dirEntry['type'] == 'directory') {
						$content .= $prefix.'		<d:resourcetype><d:collection /></d:resourcetype>'.self::$EOL;
					} else {
						$content .= $prefix.'		<d:resourcetype />'.self::$EOL;
					}
					break;
				case 'executable':
					if ($dirEntry['executable'] == '1') {
						$content .= $prefix.'		<d:executable />'.self::$EOL;
					}
					break;
				case 'creationdate':
					$content .= $prefix.'		<d:creationdate '.$datens.'>'.gmdate('D, d M Y H:i:s', $dirEntry['ctime']).' GMT</d:creationdate>'.self::$EOL;
					break;
				case 'getlastmodified':
					$content .= $prefix.'		<d:getlastmodified '.$datens.'>'.gmdate('D, d M Y H:i:s', $dirEntry['mtime']).' GMT</d:getlastmodified>'.self::$EOL;
					break;
				case 'getetag':
					$content .= $prefix.'		<d:getetag>'.$dirEntry['etag'].'</d:getetag>'.self::$EOL;
					break;
				case 'supportedlock':
					if ($dirEntry['type'] == 'directory') {
						$content .= $prefix.'		<d:supportedlock />'.self::$EOL;
					} else {
						$content .= $prefix.'		<d:supportedlock>'.self::$EOL;
						$content .= $prefix.'			<d:lockentry>'.self::$EOL;
						$content .= $prefix.'				<d:lockscope><d:exclusive /></d:lockscope>'.self::$EOL;
						$content .= $prefix.'				<d:locktype><d:write /></d:locktype>'.self::$EOL;
						$content .= $prefix.'			</d:lockentry>'.self::$EOL;
						$content .= $prefix.'			<d:lockentry>'.self::$EOL;
						$content .= $prefix.'				<d:lockscope><d:shared /></d:lockscope>'.self::$EOL;
						$content .= $prefix.'				<d:locktype><d:write /></d:locktype>'.self::$EOL;
						$content .= $prefix.'			</d:lockentry>'.self::$EOL;
						$content .= $prefix.'		</d:supportedlock>'.self::$EOL;
					}
					break;
				case 'quota-used-bytes':
					$content .= $prefix.'		<d:quota-used-bytes>'.WebDAVHelperPlugin::getSize($filename).'</d:quota-used-bytes>'.self::$EOL;
					break;
				case 'quota-available-bytes':
					if (!empty($quota) && ($quota > 0)) {
						$content .= $prefix.'		<d:quota-available-bytes>'.$quota.'</d:quota-available-bytes>'.self::$EOL;
					} else {
						$unknowns[] = 'quota-available-bytes';
					}
					break;
				default:
					// Search in DB
					list($found, $value, $ns) = self::_getPropertyFromDatabase($filename, $propertyRequested);
					if (!$found) {
						// Unsupported property
						$unknowns[] = $propertyRequested;
					} else {
						if (!empty($ns && $ns != 'DAV:')) {
							$content .= $prefix.'		<b:'.$propertyRequested.' xmlns:b="'.$ns.'">'.$value.'</b:'.$propertyRequested.'>'.self::$EOL;
						} else {
							$content .= $prefix.'		<d:'.$propertyRequested.'>'.$value.'</d:'.$propertyRequested.'>'.self::$EOL;
						}
					}
					break;
			}
		}
		$content .= $prefix.'	</d:prop>'.self::$EOL;
		$content .= $prefix.'	<d:status>HTTP/1.1 '.WebDAVHelper::getStatus($status).'</d:status>'.self::$EOL;
		$content .= $prefix.'</d:propstat>'.self::$EOL;
		if (count($unknowns) > 0) {
			$content .= $prefix.'<d:propstat>'.self::$EOL;
			$content .= $prefix.'	<d:prop>'.self::$EOL;
			$content .= $prefix.'		<executable xmlns="http://apache.org/dav/props/" />'.self::$EOL;
			foreach ($unknowns as $unknown) {
				$content .= $prefix.'		<d:'.$unknown.' />'.self::$EOL;
			}
			$content .= $prefix.'	</d:prop>'.self::$EOL;
			$content .= $prefix.'	<d:status>HTTP/1.1 404 Not Found</d:status>'.self::$EOL;
			$content .= $prefix.'</d:propstat>'.self::$EOL;
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

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

	public static function execute($resourceLocation, $uriLocation) {
		$properties = self::_parseInfo();
		if ($properties === false) { return array(WebDAVHelper::$HTTP_STATUS_ERROR_BAD_REQUEST, array(), ''); }
		$status = WebDAVHelper::getStatus(WebDAVHelper::$HTTP_STATUS_OK);
		if (!self::_saveProperties($resourceLocation, $properties)) {
			$status = WebDAVHelper::getStatus(WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN);
		}
		return self::_generateAnswer($uriLocation, $properties, $status);
	}

	private static function _parseInfo() {
		$input = file_get_contents('php://input');
		WebDAVHelper::debugAddMessage('Proppatch input: '.$input);
		$dom = new DOMDocument();
		if (!$dom->loadXML($input, LIBXML_NOWARNING)) { return false; }
		$info = array();
		$elementList = $dom->getElementsByTagName('prop');
		if ($elementList->length > 0) {
			for($i=0 ; $i<$elementList->length ; $i++) {
				$element = $elementList->item($i);
				$elementChildList = $element->childNodes;
				if ($elementChildList->length > 0) {
					for($j=0 ; $j<$elementChildList->length ; $j++) {
						$elementChild = $elementChildList->item($j);
						if ($elementChild->nodeName != '#text') {
							$name = $elementChild->localName;
							if (strpos($name,':')) { $name = explode(':',$name,2)[1]; }
							$info[$name] = array(
								'ns' => $elementChild->namespaceURI,
								'value' => $elementChild->textContent
							);
							WebDAVHelper::debugAddArray($info[$name],'Proppatch properties: '.$name.' ');
						}
					}
				}
			}
		}
		if (count($info) < 1) { return false; }
		return $info;
	}

	private static function _saveProperties($resourceLocation, $properties) {
		foreach($properties as $propName => $propData) {
			if (!self::_changeProperty($resourceLocation, $propName, $propData['value'], $propData['ns'])) {
				return false;
			}
		}
		return true;
	}

	private static function _changeProperty($resourceLocation, $name, $value, $ns) {
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		if (!empty($value)) {
			$date = JFactory::getDate();
			$user = JFactory::getUser();
			$dbfields = array(
				$db->quoteName('namespace').'='.$db->quote($ns),
				$db->quoteName('value').'='.$db->quote($value),
				$db->quoteName('modifiedby').'='.$db->quote($user->get('name')),
				$db->quoteName('modifieddate').'='.$db->quote($date->toSql())
			);
			WebDAVHelper::debugAddMessage('Proppatch input: try update "'.$name.'"');
			$query->update($db->quoteName('#__nokWebDAV_properties'))
				->set($dbfields)
				->where($db->quoteName('resourcetype').'='.$db->quote('files').' AND '.$db->quoteName('resourcelocation').'='.$db->quote($resourceLocation).' AND '.$db->quoteName('name').'='.$db->quote($name));
			$db->setQuery($query);
			if (!$db->execute() || ($db->getAffectedRows() < 1)) {
				WebDAVHelper::debugAddMessage('Proppatch input: update failed try insert "'.$name.'"');
				$query = $db->getQuery(true);
				$fields = array(
					'resourcetype' => 'files',
					'resourcelocation' => $resourceLocation,
					'name' => $name,
					'namespace' => $ns,
					'value' => $value,
					'createdby' => $user->get('name'),
					'createddate' => $date->toSql(),
					'modifiedby' => $user->get('name'),
					'modifieddate' => $date->toSql()
				);
				$query->insert($db->quoteName('#__nokWebDAV_properties'))
					->columns($db->quoteName(array_keys($fields)))
					->values(implode(',',$db->quote(array_values($fields))));
				$db->setQuery($query);
				if (!$db->execute()) {
					return false;
				}
			}
		} else {
			WebDAVHelper::debugAddMessage('Proppatch input: try delete "'.$name.'"');
			$query->delete($db->quoteName('#__nokWebDAV_properties'))
				->where($db->quoteName('resourcetype').'='.$db->quote('files').' AND '.$db->quoteName('resourcelocation').'='.$db->quote($resourceLocation).' AND '.$db->quoteName('name').'='.$db->quote($name));
			$db->setQuery($query);
			if (!$db->execute()) {
				return false;
			}
		}
		return true;
	}

	private static function _generateAnswer($uriLocation, $properties, $propstatus) {
		$status = WebDAVHelper::$HTTP_STATUS_OK_MULTI_STATUS;
		$header = array('Content-Type: text/xml; charset="utf-8"');
		$content = '<?xml version="1.0" encoding="utf-8"?>'.self::$EOL;
		$content .= '<d:multistatus xmlns:d="DAV:">'.self::$EOL;
		$content .= self::_getResponse($uriLocation, $properties, $propstatus);
		$content .= '</d:multistatus>'.self::$EOL;
		return array($status, $header, $content);
	}

	private static function _getResponse($uriLocation, $properties, $propstatus) {
		$content = '';
		$content .= '	<d:response>'.self::$EOL;
		$content .= '		<d:href>'.$uriLocation.'</d:href>'.self::$EOL;
		$content .= self::_getProperties($properties, $propstatus, "\t\t");
		$content .= '	</d:response>'.self::$EOL;
		return $content;
	}

	private static function _getProperties($properties, $propstatus, $prefix) {
		$content = $prefix.'<d:propstat>'.self::$EOL;
		$content .= $prefix.'	<d:prop>'.self::$EOL;
		$unknowns = array();
		foreach ($properties as $propName => $propData) {
			if (isset($propData['ns']) && !empty($propData['ns'] && $propData['ns'] != 'DAV:')) {
				$content .= $prefix.'		<b:'.$propName.' xmlns:b="'.$propData['ns'].'" />'.self::$EOL;
			} else {
				$content .= $prefix.'		<d:'.$propName.' />'.self::$EOL;
			}
		}
		$content .= $prefix.'	</d:prop>'.self::$EOL;
		$content .= $prefix.'	<d:status>HTTP/1.1 '.$propstatus.'</d:status>'.self::$EOL;
		$content .= $prefix.'</d:propstat>'.self::$EOL;
		return $content;
	}
}
?>

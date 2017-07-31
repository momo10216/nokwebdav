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
	private static $_allowedCommands = array('LOCK','UNLOCK');
	private static $_timeout = 300;
	private $_type;
	private $_access;
	private $_fileLocation;
	private $_targetAccess;
	private $_targetFileLocation;
	private $_uriLocation;

	public function __construct($type, $access, $fileLocation, $targetAccess, $targetFileLocation, $uriLocation) {
		$this->_type = $type;
		$this->_access = $access;
		$this->_fileLocation = $fileLocation;
		$this->_targetAccess = $targetAccess;
		$this->_targetFileLocation = $targetFileLocation;
		$this->_uriLocation = $uriLocation;
	}

	public function hasAccess($command) {
		$hasAccess = '';
		switch(strtoupper($command)) {
			case 'LOCK':
			case 'UNLOCK':
				if ($this->_access['change']) { $hasAccess =  '1'; }
				break;
			default:
				break;
		}
		//self::debugAddMessage('Access command:'.$command.' result:'.$hasAccess);
		return $hasAccess;
	}

	public function handleCommand($command) {
		WebDAVHelper::debugAddMessage('Incoming file command: '.$command);
		switch($command) {
			case 'LOCK':
				return $this->_lock();
			case 'UNLOCK':
				return $this->_unlock();
			default:
				// Unsupported command
				WebDAVHelper::debugAddMessage('Unsupported locking command: '.$command);
				$code = WebDAVHelper::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED;
                		$headers = array('Allow: '.join(", ", self::$_allowedCommands));
				$content = '';
				return array($code, $headers, $content);
		}
	}

	private function _lock() {
		global $_SERVER;

		if (empty($_SERVER['CONTENT_LENGTH']) && !empty($_SERVER['HTTP_IF'])) {
			return $this->_updateLock(substr($_SERVER['HTTP_IF'], 2, -2));
		} else {
			return $this->_newLock();
		}
	}

	private function _newLock() {
		WebDAVHelper::debugAddMessage('New lock');
		if (WebDAVHelper::isLocked($this->_type, $this->_fileLocation)) {
			return array(WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT,array(),'');
		}
		$depth = WebDAVHelper::getDepth();
		if (!empty($depth) && ($depth != '0')) {
			//no directory or reverse locking
			return array(WebDAVHelper::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED,array(),'');
		}
		$info = $this->_parseInfo();
		$date = JFactory::getDate();
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$fields = array(
			'token' => 'opaquelocktoken:'.WebDAVHelper::uuid(),
			'resourcetype' => $this->_type,
			'resourcelocation' => $this->_fileLocation,
			'expires' => time()+self::$_timeout,
			'recursive' => '0',
			'scope' => $info['scope'],
			'type' => $info['type'],
			'createtime' => time(),
			'modifytime' => time()
		);
		if (isset($info['owner'])) { $fields['owner'] = $info['owner']; }

		$query->insert($db->quoteName('#__nokWebDAV_locks'))
			->columns($db->quoteName(array_keys($fields)))
			->values(implode(',',$db->quote(array_values($fields))));
		$db->setQuery($query);
//		WebDAVHelper::debugAddQuery($query);
		if ($db->execute()) {
			$content = self::_generateLockResponse($fields);
//			WebDAVHelper::debugAddMessage('Response: '.$content);
			return array(WebDAVHelper::$HTTP_STATUS_OK, array('Content-Type: text/xml; charset="utf-8"', 'Lock-Token: <'.$fields['token'].'>'), $content);
		}
//		WebDAVHelper::debugAddMessage('SQL Error: '.$db->getErrorMsg());
		return array(WebDAVHelper::$HTTP_STATUS_ERROR_INTERNAL_SERVER_ERROR, array(), '');
	}

	private function _updateLock($token) {
		WebDAVHelper::debugAddMessage('Lock update');
		$date = JFactory::getDate();
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$fields = array(
			$db->quoteName('expires').'='.$db->quote(time()+self::$_timeout),
			$db->quoteName('modifytime').'='.time()
		);
		$query->update($db->quoteName('#__nokWebDAV_locks','l'))
			->set($fields)
			->where($db->quoteName('token').'='.$db->quote($token));
		$db->setQuery($query);
		if ($db->execute()) {
			WebDAVHelper::debugAddMessage('Lock updated');
			$content = self::_generateLockResponse($fields);
			return array(WebDAVHelper::$HTTP_STATUS_OK, array('Content-Type: text/xml; charset="utf-8"', 'Lock-Token: <'.$token.'>'), $content);
		}
		return array('451', array(), '');
		//return array(WebDAVHelper::$HTTP_STATUS_ERROR_INTERNAL_SERVER_ERROR, array(), '');
	}

	private function _unlock() {
		global $_SERVER;
		if (!isset($_SERVER['HTTP_LOCK_TOKEN']) || empty($_SERVER['HTTP_LOCK_TOKEN'])) {
			return array(WebDAVHelper::$HTTP_STATUS_ERROR_PRECONDITION_FAILED,array(),'');
		}
		$token = substr(trim($_SERVER['HTTP_LOCK_TOKEN']), 1, -1);
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__nokWebDAV_locks'))
			->where($db->quoteName('token').'='.$db->quote($token));
		$db->setQuery($query);
//		WebDAVHelper::debugAddQuery($query);
		if ($db->execute()) {
			return array(WebDAVHelper::$HTTP_STATUS_NO_CONTENT, array(), '');
		}
		return array(WebDAVHelper::$HTTP_STATUS_ERROR_INTERNAL_SERVER_ERROR, array(), '');
	}

	private static function _parseInfo() {
		$input = file_get_contents('php://input');
		//WebDAVHelper::debugAddMessage('Lock input: '.$input);
		$dom = new DOMDocument();
		if (!$dom->loadXML($input)) { return false; }
		$elementList = $dom->getElementsByTagName('lockinfo')->item(0)->childNodes;
		$info = array();
		if ($elementList->length > 0) {
			for($i=0 ; $i<$elementList->length ; $i++) {
				$element = $elementList->item($i);
				switch($element->nodeName) {
					case 'lockscope':
						$info['scope'] = $element->childNodes->item(0)->nodeName;
						break;
					case 'locktype':
						$info['type'] = $element->childNodes->item(0)->nodeName;
						break;
					case 'owner':
						$info['owner'] = $element->childNodes->item(0)->textContent;
						break;
					default:
						WebDAVHelper::debugAddMessage('Lock unknown input: '.$element->nodeName);
						break;
				}
			}
		}
		//WebDAVHelper::debugAddArray($info,'Lock requested properties: ');
		return $info;
	}

	private static function _generateLockResponse($fields) {
		$content = '<?xml version="1.0" encoding="UTF-8"?>'.self::$EOL;
		$content .= '<d:prop xmlns:d="DAV:">'.self::$EOL;
		$content .= '	<d:lockdiscovery>'.self::$EOL;
		$content .= '		<d:activelock>'.self::$EOL;
		$content .= '			<d:lockscope><d:'.$fields['scope'].' /></d:lockscope>'.self::$EOL;
		$content .= '			<d:locktype><d:'.$fields['type'].' /></d:locktype>'.self::$EOL;
		$content .= '			<d:depth>'.WebDAVHelper::getDepth().'</d:depth>'.self::$EOL;
		if (isset($fields['owner'])) {
			$content .= '			<d:owner><d:href>'.$fields['owner'].' </d:href></d:owner>'.self::$EOL;
		}
		$content .= '			<d:timeout>Second-'.self::$_timeout.'</d:timeout>'.self::$EOL;
		$content .= '			<d:locktoken><d:href>'.$fields['token'].'</d:href></d:locktoken>'.self::$EOL;
		$content .= '		</d:activelock>'.self::$EOL;
		$content .= '	</d:lockdiscovery>'.self::$EOL;
		$content .= '</d:prop>'.self::$EOL;
		return $content;
	}
}
?>

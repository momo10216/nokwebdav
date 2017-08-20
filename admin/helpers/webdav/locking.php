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
	private $_access;
	private $_key;
	private $_type;

	public function __construct($type, $access, $fileData, $contactData, $eventData) {
		$this->_type = $type;
		$this->_access = $access;
		switch(strtolower($type)) {
			case 'files':
				$this->_key = $fileData['sourceLocation'];
				break;
			default:
				break;
		}
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
		return $hasAccess;
	}

	public function inputsValid() {
		global $_SERVER;
		if (isset($_SERVER['HTTP_DEPTH']) && ($_SERVER['HTTP_DEPTH'] > 0)) { return false; }
		return true;
	}

	public function handleCommand($command) {
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
				$outFile = '';
				return array($code, $headers, $content, $outFile);
		}
	}

	private function _lock() {
		global $_SERVER;
		if (WebDAVHelper::isLocked($this->_type, $this->_key)) {
			return array(WebDAVHelper::$HTTP_STATUS_ERROR_LOCKED, array(), '', '');
		}
		if (!empty($_SERVER['HTTP_IF'])) {
			return $this->_updateLock(WebDAVHelper::getToken());
		} else {
			return $this->_newLock();
		}
	}

	private function _newLock() {
		$info = $this->_parseInfo();
		$date = JFactory::getDate();
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$fields = array(
			'token' => 'opaquelocktoken:'.WebDAVHelper::uuid(),
			'resourcetype' => $this->_type,
			'resourcelocation' => $this->_key,
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
		if ($db->execute()) {
			$content = self::_generateLockResponse($fields);
			return array(WebDAVHelper::$HTTP_STATUS_OK, array('Content-Type: text/xml; charset="utf-8"', 'Lock-Token: <'.$fields['token'].'>'), $content, '');
		}
		return array(WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT, array(), '', '');
	}

	private function _updateLock($token) {
		$date = JFactory::getDate();
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$lockInfo = WebDAVHelper::getLockInfoByToken($token);
		$fields = array(
			'token' => $token,
			'resourcetype' => $lockInfo->resourcetype,
			'resourcelocation' => $lockInfo->resourcelocation,
			'expires' => time()+self::$_timeout,
			'recursive' => '0',
			'scope' => $lockInfo->scope,
			'type' => $lockInfo->type,
			'createtime' => $lockInfo->createtime,
			'modifytime' => time()
		);
		$dbfields = array(
			$db->quoteName('expires').'='.$db->quote($fields['expires']),
			$db->quoteName('modifytime').'='.$fields['modifytime']
		);
		$query->update($db->quoteName('#__nokWebDAV_locks'))
			->set($dbfields)
			->where($db->quoteName('token').'='.$db->quote($token));
		$db->setQuery($query);
		if ($db->execute()) {
			$content = self::_generateLockResponse($fields);
			return array(WebDAVHelper::$HTTP_STATUS_OK, array('Content-Type: text/xml; charset="utf-8"', 'Lock-Token: <'.$token.'>'), $content, '');
		}
		return array(WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT, array(), '', '');
	}

	private function _unlock() {
		global $_SERVER;
		if (!isset($_SERVER['HTTP_LOCK_TOKEN']) || empty($_SERVER['HTTP_LOCK_TOKEN'])) {
			return array(WebDAVHelper::$HTTP_STATUS_ERROR_PRECONDITION_FAILED,array(),'','');
		}
		$token = substr(trim($_SERVER['HTTP_LOCK_TOKEN']), 1, -1);
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__nokWebDAV_locks'))
			->where($db->quoteName('token').'='.$db->quote($token));
		$db->setQuery($query);
		if ($db->execute()) {
			return array(WebDAVHelper::$HTTP_STATUS_NO_CONTENT, array(), '','');
		}
		return array(WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT,array(),'','');
	}

	private static function _parseInfo() {
		$input = file_get_contents('php://input');
		WebDAVHelper::debugAddMessage('Locking input: '.$input);
		$dom = new DOMDocument();
		if (!$dom->loadXML($input, LIBXML_NOWARNING)) { return false; }
		$elementList = $dom->getElementsByTagName('lockinfo')->item(0)->childNodes;
		$info = array();
		if ($elementList->length > 0) {
			for($i=0 ; $i<$elementList->length ; $i++) {
				$element = $elementList->item($i);
				switch($element->localName) {
					case 'lockscope':
						$info['scope'] = $element->childNodes->item(0)->localName;
						break;
					case 'locktype':
						$info['type'] = $element->childNodes->item(0)->localName;
						break;
					case 'owner':
						$info['owner'] = $element->childNodes->item(0)->textContent;
						break;
					default:
						WebDAVHelper::debugAddMessage('Lock unknown input: '.$element->localName);
						break;
				}
			}
		}
		return $info;
	}

	private static function _generateLockResponse($fields) {
		$content = '<?xml version="1.0" encoding="UTF-8"?>'.self::$EOL;
		$content .= '<d:prop xmlns:d="DAV:">'.self::$EOL;
		$content .= '	<d:lockdiscovery>'.self::$EOL;
		$content .= '		<d:activelock>'.self::$EOL;
		$content .= '			<d:lockscope><d:'.$fields['scope'].' /></d:lockscope>'.self::$EOL;
		$content .= '			<d:locktype><d:'.$fields['type'].' /></d:locktype>'.self::$EOL;
		$content .= '			<d:depth>0</d:depth>'.self::$EOL;
		if (isset($fields['owner'])) {
			$content .= '			<d:owner><d:href>'.$fields['owner'].'</d:href></d:owner>'.self::$EOL;
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

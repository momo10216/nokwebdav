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

class WebDAVHelper {
	public static $HTTP_STATUS_OK = '200';
	public static $HTTP_STATUS_CREATED = '201';
	public static $HTTP_STATUS_NO_CONTENT = '204';
	public static $HTTP_STATUS_OK_PARTIAL = '206';
	public static $HTTP_STATUS_OK_MULTI_STATUS = '207';
	public static $HTTP_STATUS_ERROR_BAD_REQUEST = '400';
	public static $HTTP_STATUS_ERROR_UNAUTHORIZED = '401';
	public static $HTTP_STATUS_ERROR_PAYMENT_REQUIRED = '402';
	public static $HTTP_STATUS_ERROR_FORBIDDEN = '403';
	public static $HTTP_STATUS_ERROR_NOT_FOUND = '404';
	public static $HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED = '405';
	public static $HTTP_STATUS_ERROR_CONFLICT = '409';
	public static $HTTP_STATUS_ERROR_PRECONDITION_FAILED = '412';
	public static $HTTP_STATUS_ERROR_UNSUPPORTED_MEDIA_TYPE = '415';
	public static $HTTP_STATUS_ERROR_REQUESTED_RANGE_NOT_SATISFIABLE = '416';
	public static $HTTP_STATUS_ERROR_LOCKED = '423';
	public static $HTTP_STATUS_ERROR_INTERNAL_SERVER_ERROR = '500';
	public static $HTTP_STATUS_ERROR_NOT_IMPLEMENTED = '501';
	public static $HTTP_STATUS_ERROR_INSUFFICIENT_STORAGE = '507';
	public static $DAV_SUPPORTED_PROTOCOLS = array('1','2');
	private static $_http_status_text = array('200' => 'OK',
		'201' => 'Created',
		'202' => 'Accepted',
		'203' => 'Non-Authoritative Information',
		'204' => 'No Content',
		'205' => 'Reset Content',
		'206' => 'Partial Content',
		'207' => 'Multi-Status',
		'208' => 'Already Reported',
		'226' => 'IM Used',
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'402' => 'Payment Required',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'406' => 'Not Acceptable',
		'407' => 'Proxy Authentication Required',
		'408' => 'Request Time-out',
		'409' => 'Conflict',
		'410' => 'Gone',
		'411' => 'Length Required',
		'412' => 'Precondition Failed',
		'413' => 'Request Entity Too Large',
		'414' => 'Request-URL Too Long',
		'415' => 'Unsupported Media Type',
		'416' => 'Requested range not satisfiable',
		'417' => 'Expectation Failed',
		'420' => 'Policy Not Fulfilled',
		'421' => 'Misdirected Request',
		'422' => 'Unprocessable Entity',
		'423' => 'Locked',
		'424' => 'Failed Dependency',
		'425' => 'Unordered Collection',
		'426' => 'Upgrade Required',
		'428' => 'Precondition Required',
		'429' => 'Too Many Requests',
		'431' => 'Request Header Fields Too Large',
		'444' => 'No Response',
		'449' => 'The request should be retried after doing the appropriate action',
		'451' => 'Unavailable For Legal Reasons',
		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'507' => 'Insufficient Storage'
	);
	private $_type;
	private $_plugin;

	public function __construct($type, $uriLocation, $sourceAccess, $targetAccess, $fileData, $contactData, $eventData) {
		$this->_type = $type;
		$this->_initializePlugin($type, $uriLocation, $sourceAccess, $targetAccess, $fileData, $contactData, $eventData);
	}

	public static function getFilesInstance($sourceAccess, $rootLocation, $sourceLocation, $targetAccess=array(), $targetLocation='/', $uriLocation='/', $quota=0) {
		$fileData = array(
			'rootLocation' => $rootLocation,
			'sourceLocation' => $sourceLocation,
			'targetLocation' => $targetLocation,
			'quota' => $quota
		);
		return new self('files', $uriLocation, $sourceAccess, $targetAccess, $fileData, array(), array());
	}

	public function run() {
		$this->handleCommand($this->getCommand());
	}

	public static function getCommand() {
		global $_SERVER;
		return $_SERVER['REQUEST_METHOD'];
	}

	public static function getDepth() {
		global $_SERVER;
		if (isset($_SERVER['HTTP_DEPTH'])) { return $_SERVER['HTTP_DEPTH']; }
		return 'infinity';
	}

	public static function getToken() {
		global $_SERVER;
		$resourceuri = self::_getUriLoaction();
		if (isset($_SERVER['HTTP_IF'])) {
			if (preg_match('/\<'.str_replace('/','\\/',$resourceuri).'\> \(\<([^\>]*)\>/', $_SERVER['HTTP_IF'], $matches) > 0) {
				return $matches[1];
			} else {
				if (preg_match('/\(\<([^\>]*)\>/', $_SERVER['HTTP_IF'], $matches) > 0) {
					return $matches[1];
				}
			}
		}
		return '';
	}

	public function handleCommand($command) {
		global $_SERVER;
		self::debugAddMessage('handleCommand: start command "'.$command.'"');
		if ($this->_plugin->hasAccess($command)) {
			self::debugAddMessage('handleCommand: Access OK');
			if ($this->_plugin->inputsValid()) {
				self::debugAddMessage('handleCommand: Hand over to plugin');
				list($code, $headers, $content, $outFile) = $this->_plugin->handleCommand($command);
			} else{
				self::debugAddMessage('handleCommand: Inputs invalid');
				$code = self::$HTTP_STATUS_ERROR_BAD_REQUEST;
				$headers = array();
				$content = '';
				$outFile = '';
			}
		} else {
			self::debugAddMessage('handleCommand: No access');
			$code = self::$HTTP_STATUS_ERROR_UNAUTHORIZED;
			$headers = array('WWW-Authenticate: Basic realm="Joomla (NoK-WebDAV) on '.$_SERVER["SERVER_NAME"].'"');
			$content = '';
			$outFile = '';
		}
		if (!empty($outFile)) {
			$headers[] = 'Content-length: '.filesize($outFile);
			self::sendHttpStatusAndHeaders($code, $headers);
			self::debugAddMessage('handleCommand: Content File='.$outFile);
			$fhRead = fopen($outFile, 'rb');
			$fhWrite = fopen('php://output', 'wb');
			stream_copy_to_stream($fhRead, $fhWrite);
			fclose($fhRead);
			fclose($fhWrite);
		} else {
			if (is_string($content)) { $headers[] = 'Content-length: '.mb_strlen($content, '8bit'); }
			self::sendHttpStatusAndHeaders($code, $headers);
			if (!empty($content)) {
				self::debugAddMessage('handleCommand: Content='.$content);
				echo $content;
			}
		}
		self::debugAddMessage('handleCommand: end command "'.$command.'"');
	}

	public static function getStatus($code) {
		if (isset(self::$_http_status_text[$code])) {
			return $code.' '.self::$_http_status_text[$code];
		}
		return $code;
	}

	public static function debugAddMessage($message) {
		JLog::add($message, JLog::DEBUG);
	}

	public static function debugAddQuery($query) {
		self::debugAddMessage('SQL: '.$query->__toString());
	}

	public static function debugAddArray($list, $name) {
		$entries = array();
		foreach($list as $key => $value) {
			$entries[] = "$key => $value";
		}
		self::debugAddMessage($name.' {'.join(', ',$entries).'}');
	}

	public static function directoryWithSlash($directory) {
		if (substr($directory,-1) == '/') { return $directory; }
		return $directory.'/';
	}

	public static function joinDirAndFile($directory, $filename) {
		if (substr($directory,-1) == '/') {
			if (substr($filename,0,1) == '/') {
				return $directory.substr($filename,1);
			} else {
				return $directory.$filename;
			}
		} else {
			if (substr($filename,0,1) == '/') {
				return $directory.$filename;
			} else {
				return $directory.'/'.$filename;
			}
		}
	}

	public static function sendHttpStatusAndHeaders($code, $additionalheaders = array()) {
		$text = '';
		$status = $code;
		if (isset(self::$_http_status_text[$code])) {
			$status = $code.' '.self::$_http_status_text[$code];
		}
		$statusheaders = array("HTTP/1.1 $status", "X-WebDAV-Status: $status", 'X-Dav-Powered-By: NoK-WebDAV');
		$headers = array_merge($statusheaders, $additionalheaders);
		self::debugAddArray($headers, 'handleCommand: headers');
		foreach($headers as $header) {
			header($header,true);
		}
	}

	public static function isLocked($type, $location, $exclusiveOnly= false) {
		global $_SERVER;

		self::_cleanupExpiredLocks();
		$lockInfo = self::getLockInfoByObject($type, $location);
		if ($lockInfo) {
			if (self::getToken() != $lockInfo->token) {
				if (!$exclusiveOnly || ($lockInfo->scope != 'shared')) { return true; }
			}
		}
		return false;
	}

	public static function getLockInfoByToken($token) {
		if ($token) {
			$db = JFactory::getDBO();
			return self::_getLockInfo(array($db->quoteName('token').' = '.$db->quote($token)));
		}
		return false;
	}

	public static function getLockInfoByObject($type, $location) {
		if (!empty($type) && !empty($location)) {
			$db = JFactory::getDBO();
			return self::_getLockInfo(array($db->quoteName('resourcetype').' = '.$db->quote($type),
				$db->quoteName('resourcelocation').' = '.$db->quote($location)));
		}
		return false;
	}

	public static function uuid() {
		// use uuid extension from PECL if available
		if (function_exists("uuid_create")) { return uuid_create(); }

		// fallback
		global $_SERVER;
		$uuid = md5(microtime().$_SERVER['SERVER_NAME'].getmypid());
		return substr($uuid, 0, 8)."-".substr($uuid,  8, 4)."-".substr($uuid, 12, 4)."-".substr($uuid, 16, 4)."-".substr($uuid, 20);
	}

	private static function _useCommandPlugin($command) {
		switch ($command) {
			case 'LOCK':
			case 'UNLOCK':
				return true;
			default:
				return false;
		}
	}

	private function _initializePlugin($type, $uriLocation, $sourceAccess, $targetAccess, $fileData, $contactData, $eventData) {
		$command = self::getCommand();
		if (self::_useCommandPlugin($command)) {
			return $this->_initializeCommandPlugin($command, $type, $uriLocation, $sourceAccess, $targetAccess, $fileData, $contactData, $eventData);
		} else {
			return $this->_initializeTypePlugin($type, $uriLocation, $sourceAccess, $targetAccess, $fileData, $contactData, $eventData);
		}
	}

	private function _initializeCommandPlugin($command, $type, $uriLocation, $sourceAccess, $targetAccess, $fileData, $contactData, $eventData) {
		switch($command) {
			case 'LOCK':
			case 'UNLOCK':
				JLoader::register('WebDAVHelperPlugin', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/locking.php', true);
				$this->_plugin = new WebDAVHelperPlugin($type, $sourceAccess, $fileData, $contactData, $eventData);
				break;
			default:
				JLog::add('Unknown command: "'.$command.'"', JLog::ERROR);
				$this->_plugin = null;
				break;
		}
	}

	private function _initializeTypePlugin($type, $uriLocation, $sourceAccess, $targetAccess, $fileData, $contactData, $eventData) {
		switch(strtolower($type)) {
			case 'files':
				JLoader::register('WebDAVHelperPlugin', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files.php', true);
				$this->_plugin = new WebDAVHelperPlugin($uriLocation, $sourceAccess, $targetAccess, $fileData);
				break;
			default:
				JLog::add('Unknown type: '.$type, JLog::ERROR);
				$this->_plugin = null;
				break;
		}
	}

	private static function _getLockInfo($whereList) {
		$where = implode(" AND ",$whereList);
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__nokWebDAV_locks')
			->where($where);
		$db->setQuery($query);
		return $db->loadObject();
	}

	private static function _cleanupExpiredLocks() {
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__nokWebDAV_locks'))
			->where($db->quoteName('expires').'<'.time());
		$db->setQuery($query);
		if (!$db->execute()) {
			JLog::add('Error while cleanup expired locks', JLog::ERROR);
		}
	}

	private static function _getUriLoaction() {
		global $_SERVER;
		if (isset($_SERVER["HTTPS"]) && !empty($_SERVER["HTTPS"])) {
			$uri = 'https';
		} else {
			$uri = 'http';
		}
		$uri .= '://'.$_SERVER["SERVER_NAME"];
		if ($_SERVER["SERVER_PORT"] != 80 ) { $uri .= ':'.$_SERVER["SERVER_PORT"]; }
		$uri .= $_SERVER["REQUEST_URI"];
		return $uri;
	}

}
?>

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

class WebDAVHelper {
	public static $HTTP_STATUS_OK = '200';
	public static $HTTP_STATUS_OK_PARTIAL = '206';
	public static $HTTP_STATUS_OK_MULTI_STATUS = '207';
	public static $HTTP_STATUS_ERROR_BAD_REQUEST = '400';
	public static $HTTP_STATUS_ERROR_UNAUTHORIZED = '401';
	public static $HTTP_STATUS_ERROR_NOT_FOUND = '404';
	public static $HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED = '405';
	public static $HTTP_STATUS_ERROR_PRECONDITION_FAILED = '412';
	public static $HTTP_STATUS_ERROR_REQUESTED_RANGE_NOT_SATISFIABLE = '416';
	public static $HTTP_STATUS_ERROR_LOCKED = '423';
	public static $HTTP_STATUS_ERROR_NOT_IMPLEMENTED = '501';
	public static $DAV_ALLOWED_COMMANDS = array('GET');
	public static $DAV_SUPPORTED_PROTOCOLS = array('1');
	private static $_http_status_text = array('200' => 'OK',
		'206' => 'Partial Content',
		'207' => 'Multi-Status',
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'412' => 'Precondition Failed',
		'416' => 'Requested range not satisfiable',
		'423' => 'Locked',
		'501' => 'Not Implemented'
	);
	private $_type;
	private $_access;
	private $_fileLocation;
	private $_contactData;
	private $_eventData;

	public function __construct($type='files', $access, $fileLocation='/', $contactData=array(), $eventData=array()) {
		$this->_type = $type;
		$this->_access = $access;
		$this->_fileLocation = $fileLocation;
		$this->_contactData = $contactData;
		$this->_eventData = $eventData;
		$this->_initializePlugin($type, $access, $fileLocation, $contactData, $eventData);
	}

	public static function getInstance($type='files', $access, $fileLocation='/', $contactData=array(), $eventData=array()) {
		return new self($type, $access, $fileLocation, $contactData, $eventData);
	}

	public function run() {
		$this->handleCommand($this->getCommand());
	}

	public function getCommand() {
		global $_SERVER;
		return $_SERVER["REQUEST_METHOD"];
	}

	public function handleCommand($command) {
		list($code, $headers, $content) = $this->_plugin->handleCommand($command);
		if (is_string($content)) { $headers[] = 'Content-length: '.strlen($content); }
		$this->_sendHttpStatusAndHeaders($code, $headers);
		if (!empty($content)) {
			echo $content;
		}
	}

	public static function debugAddMessage($message) {
		JLog::add($message, JLog::DEBUG);
	}

	public static function debugAddArray($list, $name) {
		$entries = array();
		foreach($list as $key => $value) {
			$entries[] = "$key => $value";
		}
		self::debugAddMessage($name.' {'.join(', ',$entries).'}');
	}

	public static function debugServerEnv() {
		global $_SERVER;
		self::debugAddArray($_SERVER,'_SERVER');
	}

	public static function directoryWithSlash($directory) {
		if (substr($directory,-1) == '/') { return $directory; }
		return $directory.'/';
	}

	private function _sendHttpStatusAndHeaders($code, $additionalheaders) {
		$text = '';
		$status = $code;
		if (isset($this->_http_status_text[$code])) {
			$status = $code.' '.$this->_http_status_text[$code];
		}
		$statusheaders = array("HTTP/1.1 $status", "X-WebDAV-Status: $status", 'X-Dav-Powered-By: NoK-WebDAV');
		$headers = array_merge($statusheaders, $additionalheaders);
//		$this->debugAddArray($headers, 'headers');
		foreach($headers as $header) {
			header($header,true);
		}
	}

	private function _initializePlugin($type, $access, $fileLocation, $contactData, $eventData) {
		switch(strtolower($type)) {
			case 'files':
				JLoader::register('WebDAVHelperPlugin', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files.php', true);
				$this->_plugin = new WebDAVHelperPlugin($access, $fileLocation, $contactData, $eventData);
				break;
			default:
				JLog::add('Unknown type: '.$type, JLog::ERROR);
				$this->_plugin = null;
				break;
		}
	}
}
?>

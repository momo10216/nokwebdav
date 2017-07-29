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
	public static $HTTP_STATUS_ERROR_NOT_IMPLEMENTED = '501';
	public static $DAV_ALLOWED_COMMANDS = array('GET');
	public static $DAV_SUPPORTED_PROTOCOLS = array('1');
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
		'501' => 'Not Implemented'
	);
	private $_type;
	private $_access;
	private $_fileLocation;
	private $_uriLocation;
	private $_contactData;
	private $_eventData;

	public function __construct($type='files', $access, $fileLocation='/', $uriLocation='/', $contactData=array(), $eventData=array()) {
		$this->_type = $type;
		$this->_access = $access;
		$this->_fileLocation = $fileLocation;
		$this->_uriLocation = $uriLocation;
		$this->_contactData = $contactData;
		$this->_eventData = $eventData;
		$this->_initializePlugin($type, $access, $fileLocation, $uriLocation, $contactData, $eventData);
	}

	public static function getFilesInstance($access, $fileLocation='/', $uriLocation='/') {
		return new self('files', $access, $fileLocation, $uriLocation, array(), array());
	}

	public function run() {
		$this->handleCommand($this->getCommand());
	}

	public function getCommand() {
		global $_SERVER;
		return $_SERVER["REQUEST_METHOD"];
	}

	public function handleCommand($command) {
		if (self::hasAccess($command)) {
			list($code, $headers, $content) = $this->_plugin->handleCommand($command);
		} else {
			$code = self::$HTTP_STATUS_ERROR_UNAUTHORIZED;
			$headers = array('WWW-Authenticate: Basic realm="Joomla (NoK-WebDAV)"');
		}
		if (is_string($content)) { $headers[] = 'Content-length: '.strlen($content); }
		self::sendHttpStatusAndHeaders($code, $headers);
		if (!empty($content)) {
			echo $content;
		}
	}

	public function hasAccess($command) {
		$hasAccess = '';
		switch(strtoupper($command)) {
			case 'GET':
			case 'OPTIONS':
			case 'PROPFIND':
				if ($this->_access['read']) { $hasAccess =  '1'; }
				break;
			case 'COPY':
			case 'MKCOL':
				if ($this->_access['create']) { $hasAccess =  '1'; }
				break;
			case 'DELETE':
				if ($this->_access['delete']) { $hasAccess =  '1'; }
				break;
			case 'MOVE':
				if ($this->_access['create'] && $this->_access['delete']) { $hasAccess =  '1'; }
				break;
			case 'PUT':
				if (file_exists($this->_fileLocation) === true) {
					if ($this->_access['change']) { $hasAccess =  '1'; }
				} else {
					if ($this->_access['create']) { $hasAccess =  '1'; }
				}
				break;
			default:
				break;
		}
		//self::debugAddMessage('Access command:'.$command.' result:'.$hasAccess);
		return $hasAccess;
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
//		$this->debugAddArray($headers, 'headers');
		foreach($headers as $header) {
			header($header,true);
		}
	}

	private function _initializePlugin($type, $access, $fileLocation, $uriLocation, $contactData, $eventData) {
		switch(strtolower($type)) {
			case 'files':
				JLoader::register('WebDAVHelperPlugin', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files.php', true);
				$this->_plugin = new WebDAVHelperPlugin($access, $fileLocation, $uriLocation, $contactData, $eventData);
				break;
			default:
				JLog::add('Unknown type: '.$type, JLog::ERROR);
				$this->_plugin = null;
				break;
		}
	}
}
?>

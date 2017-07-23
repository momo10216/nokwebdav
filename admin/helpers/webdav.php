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
	private static $_allow = array();
	private static $_debug = true;
	private static $_debugFile = '/tmp/nokwebdav-{timestamp}.log';
	private $_debugText = '';
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

	public function run() {
		$this->handleCommand($this->getCommand());
	}

	public function getCommand() {
		global $_SERVER;
		return $_SERVER["REQUEST_METHOD"];
	}

	public function handleCommand($command) {
		$this->debugServerEnv();
		switch($command) {
/*
			case 'PROPFIND':
				JLoader::register('WebDAVPropFinderHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/propfind.php', true);
				list($code, $headers, $content) = WebDAVPropFinderHelper::getResponse();
				break;
*/
			default:
				// Unsupported command
				$code = self::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED;
                		$headers = array('Allow: '.join(", ", self::$_allow));
				$content = '';
				break;
		}
		$this->_sendHttpStatusAndHeaders($code, $headers);
/*
		if (!empty($content)) {
			echo $content;
		}
*/
		$this->_debugSave();
	}

	public function debugAddMessage($message) {
		if (self::$_debug === false) { return; }
		$this->_debugText .= date('Y-m-d H:i:s');
		$this->_debugText .= "\t$message\n";
	}

	public function debugAddArray($list, $name) {
		if (self::$_debug === false) { return; }
		$message = $name." = {\n";
		foreach($list as $key => $value) {
			$message .= "\t$key => $value\n";
		}
		$message .= '}';
		$this->debugAddMessage($message);
	}

	public function debugServerEnv() {
		global $_SERVER;
		if (self::$_debug === false) { return; }
		$this->debugAddArray($_SERVER,'_SERVER');
	}

	private function _sendHttpStatusAndHeaders($code, $additionalheaders) {
		$text = '';
		$status = $code;
		if (isset($this->_http_status_text[$code])) {
			$status = $code.' '.$this->_http_status_text[$code];
		}
		$statusheaders = array("HTTP/1.1 $status", "X-WebDAV-Status: $status");
		$headers = array_merge($statusheaders, $additionalheaders);
		$this->debugAddArray($headers, 'headers');
		foreach($headers as $header) {
			header($header,true);
		}
	}

	private function _debugSave() {
		global $_SERVER;
		if (self::$_debug === false) { return; }
		$filename = str_replace('{timestamp}',date('Ymd').'T'.date('His'),self::$_debugFile);
foreach($_SERVER as $key => $value) {
	header('x-comment-'.$key.': '.$value,true);
}
		$result = file_put_contents($filename, $this->_debugText);
	}

	private function _arrayToText($list, $name) {
	}
}
?>

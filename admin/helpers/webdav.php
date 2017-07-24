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

	public function run() {
		$this->handleCommand($this->getCommand());
	}

	public function getCommand() {
		global $_SERVER;
		return $_SERVER["REQUEST_METHOD"];
	}

	public function handleCommand($command) {
//		$this->debugServerEnv();
		$this->debugAddMessage('Received command "'.$command.'"');
		switch($command) {
/*
			case 'PROPFIND':
				JLoader::register('WebDAVPropFindHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/propfind.php', true);
				list($code, $headers, $content) = WebDAVPropFindHelper::getResponse();
				break;
*/
			case 'GET':
				JLoader::register('WebDAVGetHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/get.php', true);
				list($code, $headers, $content) = WebDAVGetHelper::getResponse();
				break;
			case 'OPTIONS':
				JLoader::register('WebDAVOptionsHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/options.php', true);
				list($code, $headers, $content) = WebDAVOptionsHelper::getResponse();
				break;
			default:
				// Unsupported command
				$code = self::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED;
                		$headers = array('Allow: '.join(", ", self::$DAV_ALLOWED_COMMANDS));
				$content = '';
				break;
		}
		$this->_sendHttpStatusAndHeaders($code, $headers);
/*
		if (!empty($content)) {
			echo $content;
		}
*/
	}

	public function debugAddMessage($message) {
		JLog::add($message, JLog::DEBUG);
	}

	public function debugAddArray($list, $name) {
		$entries = array();
		foreach($list as $key => $value) {
			$entries[] = "$key => $value";
		}
		$this->debugAddMessage($name.' = {'.join(', ',$entries).'}');
	}

	public function debugServerEnv() {
		global $_SERVER;
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
//		$this->debugAddArray($headers, 'headers');
		foreach($headers as $header) {
			header($header,true);
		}
	}
}
?>

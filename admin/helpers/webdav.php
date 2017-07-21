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

	public static function run() {
		self::handleCommand(self::getCommand());
	}

	public static function getCommand() {
		global $_SERVER;
		return $_SERVER["REQUEST_METHOD"];
	}

	public static function handleCommand($command) {
		switch($command) {
			case 'PROPFIND':
				self::_executePropfind();
				break;
			default:
				// Unsupported command
 				$this->_sendHttpStatus(self::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED);
                		header('Allow: '.join(", ", self::$_allow()));
				break;
		}
	}

	private static function _sendHttpStatus($code) {
		$text = '';
		$status = $code;
		if (isset(self::$_http_status_text[$code])) {
			$status = $code.' '.self::$_http_status_text[$code];
		}
		header("HTTP/1.1 $status");
		header("X-WebDAV-Status: $status", true);
	}

	private static function _getPathInfo() {
		global $_SERVER;
		return empty($_SERVER["PATH_INFO"]) ? '/' : $_SERVER["PATH_INFO"];
	}

	private static function _executePropfind() {
	}
}
?>

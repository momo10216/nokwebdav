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
	public static function execute($fileLocation) {
		$status = self::_check($fileLocation);
		$header = array();
		$content = '';
		if (!$status) { $status = self::_delete($fileLocation); }
		return array($status, $header, $content);
	}

	private static function _check($fileLocation) {
		global $_SERVER;
		if (!file_exists($fileLocation)) { return WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT; }
		if (WebDAVHelper::isLocked('files', $fileLocation)) {
			return array(WebDAVHelper::$HTTP_STATUS_ERROR_LOCKED,array(),'');
		}
		return '';
	}

	private static function _delete($fileLocation) {
		if (is_dir($fileLocation)) {
			$output = array();
			$return_var = 0;
			exec(sprintf("rm -rf %s", escapeshellarg($fileLocation)), $output, $return_var);
			$status = ($return_var == 0);
		} else {
			$status = unlink($fileLocation);
		}
		if (!$status) { return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN; }
		return WebDAVHelper::$HTTP_STATUS_OK;
	}
}
?>

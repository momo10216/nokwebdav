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
 
class WebDAVOptionsHelper {
	public static function getResponse() {
		$status = WebDAVHelper::$HTTP_STATUS_OK;
		$header = array(
			'Allow: '.join(", ", WebDAVHelper::$DAV_ALLOWED_COMMANDS),
			'DAV: '.join(", ", WebDAVHelper::$DAV_SUPPORTED_PROTOCOLS,
			'MS-Author-Via: DAV',
			'Content-length: 0'
		);
		$content = '';
		return array($status, $header, $content);
	}
}
?>

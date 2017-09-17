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
 
class WebDAVHelperPluginCommand {
	public static function execute($allowedCommands) {
		$status = WebDAVHelper::$HTTP_STATUS_OK;
		$header = array(
			'Allow: '.join(", ", $allowedCommands),
			'Accept-Ranges: none',
			'DAV: '.join(", ", WebDAVHelper::$DAV_SUPPORTED_PROTOCOLS),
			'MS-Author-Via: DAV'
		);
		$content = '';
		$outFile = '';
		return array($status, $header, $content, $outFile);
	}
}
?>

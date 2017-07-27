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
	private static $_allowedCommands = array('GET','OPTIONS');
	private $_access;
	private $_fileLocation;
	private $_contactData;
	private $_eventData;

	public function __construct($access, $fileLocation, $contactData, $eventData) {
		$this->_access = $access;
		$this->_fileLocation = $fileLocation;
		$this->_contactData = $contactData;
		$this->_eventData = $eventData;
	}

	public function handleCommand($command) {
		switch($command) {
			case 'GET':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_get.php', true);
				return WebDAVHelperPluginCommand::execute($this->_fileLocation);
			case 'OPTIONS':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/files_options.php', true);
				return WebDAVHelperPluginCommand::execute(self::$_allowedCommands);
			default:
				// Unsupported command
				WebDAVHelper::debugAddMessage('Unsupported command: '.$command);
				$code = WebDAVHelper::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED;
                		$headers = array('Allow: '.join(", ", self::$_allowedCommands));
				$content = '';
				return array($code, $headers, $content);
		}
	}
}
?>

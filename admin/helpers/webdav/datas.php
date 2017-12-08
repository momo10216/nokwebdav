<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	NoKWebDAV
* @copyright	Copyright (c) 2017 Norbert Kuemin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
class WebDAVHelperPlugin {
	private static $EOL = "\n";
	private static $_allowedCommands = array('OPTIONS');
//	private static $_allowedCommands = array('GET', 'HEAD', 'OPTIONS', 'POST', 'PROPFIND', 'DELETE', 'PUT', 'COPY', 'MOVE', 'LOCK', 'UNLOCK', 'PROPPATCH', 'REPORT', 'ACL');
	private $_uriLocation;
	private $_containerId;
	private $_sourceAccess;
	private $_targetAccess;

	public function __construct($uriLocation, $sourceAccess, $targetAccess, $textData) {
		$this->_uriLocation = $uriLocation;
		$this->_containerId = $textData['containerId'];
		$this->_sourceAccess = $sourceAccess;
		$this->_targetAccess = $targetAccess;
	}

	public function inputsValid() {
		return true;
	}

	public function hasAccess($command) {
		$hasAccess = '';
		switch(strtoupper($command)) {
			case 'OPTIONS':
				$hasAccess =  '1';
				break;
			case 'GET':
			case 'HEAD':
			case 'PROPFIND':
			case 'REPORT':
				if ($this->_sourceAccess['read']) { $hasAccess =  '1'; }
				break;
			case 'DELETE':
				if ($this->_sourceAccess['delete']) { $hasAccess =  '1'; }
				break;
			case 'COPY':
				if (file_exists($this->_targetFileLocation) === true) {
					if ($this->_sourceAccess['read'] && $this->_targetAccess['change']) { $hasAccess =  '1'; }
				} else {
					if ($this->_sourceAccess['read'] && $this->_targetAccess['create']) { $hasAccess =  '1'; }
				}
				break;
			case 'MOVE':
				if (file_exists($this->_sourceFileLocation) === true) {
					if ($this->_sourceAccess['read'] && $this->_targetAccess['change'] && $this->_sourceAccess['delete']) { $hasAccess =  '1'; }
				} else {
					if ($this->_sourceAccess['read'] && $this->_targetAccess['create'] && $this->_sourceAccess['delete']) { $hasAccess =  '1'; }
				}
				break;
			case 'PROPPATCH':
			case 'PUT':
				if (file_exists($this->_sourceFileLocation) === true) {
					if ($this->_sourceAccess['change']) { $hasAccess =  '1'; }
				} else {
					if ($this->_sourceAccess['create']) { $hasAccess =  '1'; }
				}
				break;
			default:
				break;
		}
		if (!$hasAccess) {
			WebDAVHelper::debugAddMessage('No access for command "'.$command.'".');
		}
		return $hasAccess;
	}

	public function handleCommand($command) {
		switch($command) {
			case 'GET':
			case 'HEAD':
//				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/get.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_uriLocation, $command);
			case 'OPTIONS':
				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/options.php', true);
				return WebDAVHelperPluginCommand::execute(self::$_allowedCommands);
			case 'PROPFIND':
//				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/propfind.php', true);
				return WebDAVHelperPluginCommand::execute($this->_uriLocation, $this->_quota, $this->getSize($this->_rootLocation));
			case 'DELETE':
//				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/delete.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation);
			case 'PUT':
//				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/put.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_rootLocation, $this->_quota);
			case 'COPY':
			case 'MOVE':
//				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/copymove.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_targetFileLocation, $this->_rootLocation, $this->_quota, $command);
			case 'PROPPATCH':
//				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/proppatch.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_uriLocation);
			case 'REPORT':
//				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/report.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_uriLocation);
			case 'ACL':
//				JLoader::register('WebDAVHelperPluginCommand', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav/datas/acl.php', true);
				return WebDAVHelperPluginCommand::execute($this->_sourceFileLocation, $this->_uriLocation);
			default:
				// Unsupported command
				WebDAVHelper::debugAddMessage('Unsupported file command: '.$command);
				$code = WebDAVHelper::$HTTP_STATUS_ERROR_METHOD_NOT_ALLOWED;
                		$headers = array('Allow: '.join(", ", self::$_allowedCommands));
				$content = '';
				$outFile = '';
				return array($code, $headers, $content, $outFile);
		}
	}
}
?>

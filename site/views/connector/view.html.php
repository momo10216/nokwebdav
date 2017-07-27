<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	ClubManagement-Member
* @copyright	Copyright (c) 2012 Norbert Kümin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;
class NoKWebDAVViewConnector extends JViewLegacy {
	function display($tpl = null) {
		// WebDAVHelper
		JLoader::register('WebDAVHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav.php', true);
		$type = 'files';
		$access = array('read' => true, 'create' => true, 'change' => true, 'delete' => true);
		$fileLocation = '/';
		$contactData = array();
		$eventData = array();
		$webdavHelper = WebDAVHelper::getInstance($type, $access, $fileLocation, $contactData, $eventData);
		$webdavHelper->run();
		// Exit
		$app = JFactory::getApplication();
		$app->close();
	}

	function _getLocation() {
		global $_SERVER;
		$language = JFactory::getLanguage();
		$shortlang = explode('-',$language->getTag())[0];
		$location = empty($_SERVER["PATH_INFO"]) ? '/' : $_SERVER["PATH_INFO"];
		$location = str_replace('/'.$shortlang,'',$location);
		return $location;
	}

}
?>

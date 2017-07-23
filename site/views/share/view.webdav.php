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
class NoKWebDAVViewShare extends JViewLegacy {
	function display($tpl = null) {
		// Init variables
		//$this->user = JFactory::getUser();
		//$this->state = $this->get('State');
		//$this->paramsComponent = $this->state->get('params');
		// WebDAVHelper
		JLoader::register('WebDAVHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav.php', true);
		$webdavHelper = new WebDAVHelper();
		$webdavHelper->run();
		// Exit
		$app = JFactory::getApplication();
		$app->close();
	}
}
?>

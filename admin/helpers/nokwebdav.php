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

class NoKWebDAVHelper extends JHelperContent {
	public static function addSubmenu($vName) {
		JHtmlSidebar::addEntry(
			JText::_('COM_NOKWEBDAV_MENU_CONTAINERS'),
			'index.php?option=com_nokwebdav&view=containers',
			$vName == 'containers'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_NOKWEBDAV_MENU_DATAS'),
			'index.php?option=com_nokwebdav&view=datas',
			$vName == 'contacts'
		);
	}
}
?>

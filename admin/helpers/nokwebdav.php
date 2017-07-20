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

defined('_JEXEC') or die;

class NoKWebDAVHelper extends JHelperContent {
	public static function addSidebar($vName) {
		JHtmlSidebar::addEntry(
			JText::_('COM_NOKWEBDAV_MENU_SHARES'),
			'index.php?option=com_nokwebdav&view=shares',
			$vName == 'shares'
		);
/*
		JHtmlSidebar::addEntry(
			JText::_('COM_NOKWEBDAV_MENU_CONTACTLISTS'),
			'index.php?option=com_nokwebdav&view=contactlists',
			$vName == 'contacts'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_NOKWEBDAV_MENU_CALENDARS'),
			'index.php?option=com_nokwebdav&view=calendars',
			$vName == 'events'
		);
*/
	}
}
?>

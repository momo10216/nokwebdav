<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	NoKWebDAV
* @copyright	Copyright (c) 2020 Norbert Kümin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.core');

function displayContainerInfo($view) {
	echo JText::_('COM_NOKWEBDAV_CONTAINER_PATH_LABEL').' ';
	echo $view->getListLink($view->getItem()->name,'/').':';
	if ($view->getPath() == '/') {
		echo '/';
        } else {
		$pathEntries = explode('/', $view->getPath());
		$path = '';
		foreach($pathEntries as $pathEntry) {
			if ($pathEntry != '') {
				$path .= '/'.$pathEntry;
				echo '/';
				echo $view->getListLink($pathEntry, $path);
			}
		}
	}
}

displayContainerInfo($this);
$task = JRequest::getVar('task');
switch ($task) {
	case 'save':
		break;
	case 'list':
	default:
		echo $this->loadTemplate('list');
		break;
}
?>

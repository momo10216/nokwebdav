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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;

$EOL = "\n";

function convertFiletypeToIcon($view, $type) {
	$file = '';
	switch($type) {
		case 'folder':
			$file = 'folder.svg';
			break;
		default:
			$file = 'file.svg';
			break;
	}
	if ($file != '') {
		return '<img src="'.$view->getIconPath().'/'.$file.'" class="icon">';
	}
	return '';
}

function convertSizeToReadable($size) {
	$exp = 0;
	$base = $size;
	while ($base > 1023) {
		$exp++;
		$base = $base/1024;
	}
	$ext = '';
	switch($exp) {
		case 1:
			$ext = 'K';
			break;
		case 2:
			$ext = 'M';
			break;
		case 3:
			$ext = 'G';
			break;
		case 4:
			$ext = 'T';
			break;
		case 5:
			$ext = 'P';
			break;
		default:
			$ext = '';
			$base = $size;
			break;
	}
	return intval($base).$ext;
}

function convertUnixToDate($view, $unixTimestamp) {
	$timezone = $view->getUser()->getTimezone();
	$date = new Date(date(Text::_('DATE_FORMAT_LC6'),$unixTimestamp));
	$date->setTimezone($timezone);
	return str_replace(' ','&nbsp;',$date->format(Text::_('DATE_FORMAT_LC6')));
}

function getDeleteLink($view, $file) {
	$uriList = new JURI(JURI::Root().'index.php');
	$uriList->setVar('id', $view->getItem()->id);
	$uriList->setVar('view', 'filebrowser');
	$uriList->setVar('option', 'com_nokwebdav');
	$uriList->setVar('davpath', $view->getPath());
	$uriList->setVar('davfile', $file);
	$uriList->setVar('task', 'delete');
	$uriList->setVar('orderfield', $view->getSortField());
	$uriList->setVar('orderdir', $view->getSortDir());
	return '<a href="'.$uriList->toString().'"><img src="'.$view->getIconPath().'/trash.svg" class="icon"></a>';
}

function getDownloadLink($view, $file) {
	$uriList = new JURI(JURI::Root().'index.php');
	$uriList->setVar('id', $view->getItem()->id);
	$uriList->setVar('view', 'filebrowser');
	$uriList->setVar('option', 'com_nokwebdav');
	$uriList->setVar('davpath', $view->getPath());
	$uriList->setVar('davfile', $file);
	$uriList->setVar('task', 'download');
	$uriList->setVar('orderfield', $view->getSortField());
	$uriList->setVar('orderdir', $view->getSortDir());
	return '<a href="'.$uriList->toString().'"><img src="'.$view->getIconPath().'/download.svg" class="icon"></a>';
}

function getUploadLink($view) {
	$uriList = new JURI(JURI::Root().'index.php');
	$uriList->setVar('id', $view->getItem()->id);
	$uriList->setVar('view', 'filebrowser');
	$uriList->setVar('option', 'com_nokwebdav');
	$uriList->setVar('davpath', $view->getPath());
	$uriList->setVar('task', 'upload');
	$uriList->setVar('orderfield', $view->getSortField());
	$uriList->setVar('orderdir', $view->getSortDir());
	return '<a href="'.$uriList->toString().'"><img src="'.$view->getIconPath().'/upload.svg" class="icon"></a>';
}

function getCreateFolderLink($view) {
	$uriList = new JURI(JURI::Root().'index.php');
	$uriList->setVar('id', $view->getItem()->id);
	$uriList->setVar('view', 'filebrowser');
	$uriList->setVar('option', 'com_nokwebdav');
	$uriList->setVar('davpath', $view->getPath());
	$uriList->setVar('task', 'create_folder');
	$uriList->setVar('orderfield', $view->getSortField());
	$uriList->setVar('orderdir', $view->getSortDir());
	return '<a href="'.$uriList->toString().'"><img src="'.$view->getIconPath().'/create_folder.svg" class="icon"></a>';
}

function getSubmitLink($view, $task, $icon) {
	return '<a href="" onclick="return submitForm(\''.$task.'\', \''.Text::_('COM_NOKWEBDAV_FILE_BROWSER_EMPTY_SELECTION_ERROR').'\');"><img src="'.$view->getIconPath().'/'.$icon.'" class="icon"></a>';
}

function displayDirectoryHeaderField($view, $class, $label, $field) {
	$listDir = $view->getSortDir();
	$listOrder = $view->getSortField();
	if ($listOrder == '') {
		$listOrder = 'name';
	}
	if ($listDir == '') {
		$listDir = 'asc';
	}
	$dir = 'asc';
	if (($listOrder == $field) && ($listDir == 'asc')) {
		$dir = 'desc';
	}
	echo '<th class="'.$class.'">';
	echo '<a href="#" onclick="sorting(\''.$field.'\',\''.$dir.'\'); return false;">';
	echo JText::_($label);
	if ($listOrder == $field) {
		if ($listDir == 'asc') {
			echo '<span class="icon-arrow-down-3"></span>';
		} else {
			echo '<span class="icon-arrow-up-3"></span>';
		}
	}
	echo '</a>';
	echo '</th>';
}

function displayDirectoryHeader($view) {
	global $EOL;

	echo '<form action="'.JRoute::_('index.php?option=com_nokwebdav&view=filebrowser&id='.$view->getItem()->id.'&davpath='.$view->getPath()).'" method="post" name="siteForm" id="siteForm">'.$EOL;
	echo '<table class="filelist">'.$EOL;
	echo '<thead>';
	echo '<tr>';
	if ($view->getMultiFileOption()) {
		echo '<th class="col-checkbox"><input type="checkbox" name="checkall-toggle" value="" onclick="checkAll(this);"></th>';
	}
	echo '<th class="col-icon"></th>';
	displayDirectoryHeaderField($view, 'col-name', 'COM_NOKWEBDAV_FILE_BROWSER_HEAD_NAME', 'name');
	displayDirectoryHeaderField($view, 'col-mtime', 'COM_NOKWEBDAV_FILE_BROWSER_HEAD_CHANGE_DATE', 'moddate');
	displayDirectoryHeaderField($view, 'col-size', 'COM_NOKWEBDAV_FILE_BROWSER_HEAD_SIZE', 'size');
	echo '<th class="col-action">';
	if ($view->isFolderCreationAllowed()) {
		echo getCreateFolderLink($view);
	}
	echo getUploadLink($view);
	if ($view->getMultiFileOption()) {
		echo getSubmitLink($view, 'download', 'download.svg');
		echo getSubmitLink($view, 'delete', 'trash.svg');
	}
	echo '</th>';
	echo '</tr>';
	echo '</thead>'.$EOL;
}

function displayDirectoryListEntry($view, $number, $key, $entry) {
	global $EOL;
	echo '<tr>';
	if ($view->getMultiFileOption()) {
		echo '<td class="col-checkbox"><input type="checkbox" id="cb'.$number.'" name="davfiles[]" value="'.$key.'"></td>';
	}
	echo '<td class="col-icon">'.convertFiletypeToIcon($view, $entry['type']).'</td>';
	echo '<td class="col-name">';
	if ($entry['type'] == 'folder') {
		$path = $view->getPath();
		if ($path == '/') { $path = ''; }
		echo $view->getListLink($key, $path.'/'.$key);
	} else {
		echo $key;
	}
	echo '</td>';
	echo '<td class="col-mtime">'.convertUnixToDate($view, $entry['mtime']).'</td>';
	echo '<td class="col-size">'.convertSizeToReadable($entry['size']).'</td>';
	echo '<td class="col-action">'.getDownloadLink($view, $key).getDeleteLink($view, $key).'</td>';
	echo '</tr>';
}

function displayDirectoryListEntries($view) {
	$i = 0;
	foreach($view->getDirEntries() as $key => $entry) {
		displayDirectoryListEntry($view, $i, $key, $entry);
		$i++;
	}
}

function displayDirectoryList($view) {
	global $EOL;
	echo '<tbody>'.$EOL;
	displayDirectoryListEntries($view);
	echo '</tbody>'.$EOL;
}

function displayDirectoryFooter($view) {
	global $EOL;
	$listDirn = $view->getSortDir();
	$listOrder = $view->getSortField();
	echo '</table>'.$EOL;
	echo '<input type="hidden" id="task" name="task" value="" />'.$EOL;
	echo '<input type="hidden" id="orderfield" name="orderfield" value="'.$listOrder.'" />'.$EOL;
	echo '<input type="hidden" id="orderdir" name="orderdir" value="'.$listDirn.'" />'.$EOL;
	echo JHtml::_('form.token');
	echo '</form>'.$EOL;
}

function displayDirectory($view) {
	displayDirectoryHeader($view);
	displayDirectoryList($view);
	displayDirectoryFooter($view);
}

JFactory::getDocument()->addStyleSheet(JURI::base().'components/com_nokwebdav/views/filebrowser/main.css');
if ($this->getMultiFileOption()) {
	$script = <<<EOD
function checkAll(checktoggle) {
	checkboxes = document.getElementsByName('davfiles[]');
	for (var i=0; i<checkboxes.length; i++)  {
		checkboxes[i].checked = checktoggle.checked;
	}
}
EOD;
	JFactory::getDocument()->addScriptDeclaration($script);
}
$script = <<<EOD
function submitForm(task, errorMsg) {
	checkboxes = document.getElementsByName('davfiles[]');
	let checked = false;
	for (var i=0; i<checkboxes.length; i++)  {
		checked = checked | checkboxes[i].checked
	}
	if (checked || (errorMsg == '')) {
		document.getElementById("task").value = task;
		document.getElementById("siteForm").submit();
		return false;
	} else {
		alert(errorMsg);
		return false;
	}
}

function sorting(column, direction) {
	document.getElementById("orderfield").value = column;
	document.getElementById("orderdir").value = direction;
	submitForm("list", "");
}
EOD;
JFactory::getDocument()->addScriptDeclaration($script);

displayDirectory($this);
?>

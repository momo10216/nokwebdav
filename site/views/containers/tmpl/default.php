<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	ClubManagement-Member
* @copyright	Copyright (c) 2014 Norbert Kümin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/
defined('_JEXEC') or die; // no direct access

function decodeType($type) {
	switch($type) {
		case 'files': return JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_TYPE_FILES');
		case 'contacts': return JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_TYPE_CONTACTS');
		case 'events': return JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_TYPE_EVENTS');
		default: return $type;
	}
}

function decodeBoolean($value) {
	if ($value) { return JText::_('JYES'); }
	return JText::_('JNO');
}

$EOL = "\n";
$TAB = "\t";
$user = JFactory::getUser();

if (!empty($this->paramsMenuEntry->get('pretext'))) { echo $this->paramsMenuEntry->get('pretext'); }
echo '<table border="1" style="border-style:solid; border-width:1px">'.$EOL;
echo $TAB.'<tr>';
echo '<th>'.JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_NAME_LABEL').'</th>';
echo '<th>'.JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_TYPE_LABEL').'</th>';
echo '<th>'.JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_URL_LABEL').'</th>';
if ($this->paramsMenuEntry->get('show_access') == '1') {
	echo '<th>'.JText::_('COM_NOKWEBDAV_ACCESS_READ_CONTENT_LABEL').'</th>';
	echo '<th>'.JText::_('COM_NOKWEBDAV_ACCESS_CREATE_CONTENT_LABEL').'</th>';
	echo '<th>'.JText::_('COM_NOKWEBDAV_ACCESS_CHANGE_CONTENT_LABEL').'</th>';
	echo '<th>'.JText::_('COM_NOKWEBDAV_ACCESS_DELETE_CONTENT_LABEL').'</th>';
}
echo '</tr>'.$EOL;
if ($this->items) {
	foreach($this->items as $item) {
		$row = (array) $item;
		$hasAccess = ($this->paramsMenuEntry->get('filter_access') == '0') ||
			$user->authorise('content.read', 'com_nokwebdav.container.'.$item->id) ||
			$user->authorise('content.create', 'com_nokwebdav.container.'.$item->id) ||
			$user->authorise('content.change', 'com_nokwebdav.container.'.$item->id) ||
			$user->authorise('content.delete', 'com_nokwebdav.container.'.$item->id);
		if ($hasAccess) {
			echo $TAB.'<tr>';
			echo '<td>'.$item->name.'</td>';
			echo '<td>'.decodeType($item->type).'</td>';
			$url = explode('index.php',(isset($_SERVER['HTTPS']) ? "https" : "http").'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'])[0];
			$url .= 'components/com_nokwebdav/connector.php/'.$item->name.'/';
			echo '<td><a href="'.$url.'">'.$url.'</a></td>';
			if ($this->paramsMenuEntry->get('show_access') == '1') {
				echo '<td align="center">'.decodeBoolean($user->authorise('content.read', 'com_nokwebdav.container.'.$item->id)).'</td>';
				echo '<td align="center">'.decodeBoolean($user->authorise('content.create', 'com_nokwebdav.container.'.$item->id)).'</td>';
				echo '<td align="center">'.decodeBoolean($user->authorise('content.change', 'com_nokwebdav.container.'.$item->id)).'</td>';
				echo '<td align="center">'.decodeBoolean($user->authorise('content.delete', 'com_nokwebdav.container.'.$item->id)).'</td>';
			}
			echo '</tr>'.$EOL;
		}
	}
}
echo '</table>'.$EOL;
if (!empty($this->paramsMenuEntry->get('pretext'))) { echo $this->paramsMenuEntry->get('posttext'); }
?>

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

$EOL = "\n";
$TAB = "\t";
echo '<table border="1" style="border-style:solid; border-width:1px">'.$EOL;
echo $TAB.'<tr>';
echo '<th>'.JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_NAME_LABEL').'</th>';
echo '<th>'.JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_TYPE_LABEL').'</th>';
echo '<th>'.JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_URL_LABEL').'</th>';
echo '</tr>'.$EOL;
if ($this->items) {
	foreach($this->items as $item) {
		$row = (array) $item;
		echo $TAB.'<tr>';
		echo '<td>'.$item->name.'</td>';
		echo '<td>'.$item->type.'</td>';
		$url = 'com_nokwebdav/connector/'.$item->name.'/';
		echo '<td>'.$url.'</td>';
		echo '</tr>'.$EOL;
	}
}
echo '</table>'.$EOL;
?>

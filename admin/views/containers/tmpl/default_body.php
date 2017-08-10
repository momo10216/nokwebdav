<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	NoK-PrjMgnt
* @copyright	Copyright (c) 2017 Norbert Kümin. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

function calcQuota($value, $exp, $noForEmpty=true) {
	if ($noForEmpty && (empty($value) || ($value <= 0))) { return JText::_('JNO'); }
	if (strpos(strval($value),'.')) {
		$result = rtrim(rtrim($value,'0'),'.').' ';
	} else {
		$result = $value.' ';
	}
	switch($exp) {
		case 0:
			$result .= JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_QUOTA_EXP_BYTES');
			break;
		case 1:
			$result .= JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_QUOTA_EXP_KBYTES');
			break;
		case 2:
			$result .= JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_QUOTA_EXP_MBYTES');
			break;
		case 3:
			$result .= JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_QUOTA_EXP_GBYTES');
			break;
		case 4:
			$result .= JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_QUOTA_EXP_TBYTES');
			break;
		case 5:
			$result .= JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_QUOTA_EXP_PBYTES');
			break;
		case 6:
			$result .= JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_QUOTA_EXP_EBYTES');
			break;
	}
	return $result;
}

function getSize($path) {
	if (is_dir(rtrim($path,DIRECTORY_SEPARATOR))) {
		$total_size = 0;
		$files = scandir($path);
		foreach($files as $file) {
			if (($file != '.') && ($file != '..')) {
				$total_size += getSize(rtrim($path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file);
			}
		}
		return $total_size+filesize($path);
	} else {
		return filesize($path);
	}
}

function getFormatedSize($path) {
	$exp = 0;
	if ((strlen($path) < 1) || (substr($path,0,1) != DIRECTORY_SEPARATOR)) {
		// relative path
		$path = rtrim(JPATH_SITE,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
	}
	$value = getSize($path);
	while ($value > 1023) {
		$exp++;
		$value = $value/1024;
	}
	return calcQuota(round($value,2), $exp, false);
}


?>
<?php foreach($this->items as $i => $item): ?>
        <tr class="row<?php echo $i % 2; ?>">
                <td>
                        <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                </td>
                <td>
                        <?php echo $item->name; ?>
                </td>
                <td>
                        <?php echo JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_TYPE_'.strtoupper($item->type)); ?>
                </td>
                <td class="hidden-phone">
                        <?php echo $item->filepath; ?>
                </td>
                <td class="hidden-phone">
                        <?php echo calcQuota($item->quotaValue, $item->quotaExp); ?>
                </td>
                <td class="hidden-phone">
                        <?php if ($item->quotaValue > 0) { echo getFormatedSize($item->filepath); } ?>
                </td>
                <td class="hidden-phone">
                        <?php if ($item->published == '0') { echo JText::_('JNO'); } else { echo JText::_('JYES'); }; ?>
                </td>
        </tr>
<?php endforeach; ?>

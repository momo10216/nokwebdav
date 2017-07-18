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

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$listDirn	= $this->escape($this->state->get('list.direction'));
$listOrder	= $this->escape($this->state->get('list.ordering'));
?>
<tr>
	<th width="1%" class="hidden-phone">
		<?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th class="hidden-phone">
		<?php echo JHtml::_('grid.sort', 'COM_NOKWEBDAV_SHARE_FIELD_PUBLISHED_LABEL', 'p.title', $listDirn, $listOrder); ?>
	</th>
	<th>
		<?php echo JHtml::_('grid.sort', 'COM_NOKWEBDAV_SHARE_FIELD_NAME_LABEL', 'c.title', $listDirn, $listOrder); ?>
	</th>
	<th class="hidden-phone">
		<?php echo JHtml::_('grid.sort', 'COM_NOKWEBDAV_SHARE_FIELD_FILEPATH_LABEL', 'p.status', $listDirn, $listOrder); ?>
	</th>
</tr>

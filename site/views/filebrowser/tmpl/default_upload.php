<?php
/**
* @version	$Id$
* @package	Joomla
* @subpackage	NoKWebDAV
* @copyright	Copyright (c) 2020 Norbert K�min. All rights reserved.
* @license	http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE
* @author	Norbert Kuemin
* @authorEmail	momo_102@bluemail.ch
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>
<form enctype="multipart/form-data" action="<?php echo JRoute::_('index.php?option=com_nokwebdav&view=filebrowser&id='.$this->getItem()->id.'&davpath='.$this->getPath()); ?>"
		method="post" name="uploadForm" id="uploadForm" class="form-horizontal">
	<input required="" type="file" id="upload-file" name="upload-file" multiple="">
	<input type="hidden" name="task" value="upload_do">
	<input type="submit" value="<?php echo Text::_('COM_NOKWEBDAV_FILE_BROWSER_UPLOAD_SUBMIT'); ?>">
</form>


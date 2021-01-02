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

?>
<form enctype="multipart/form-data" action="<?php echo JRoute::_('index.php?option=com_nokwebdav&view=filebrowser&id='.$this->getItem()->id.'&davpath='.$this->getPath()); ?>"
		method="post" name="createFolderForm" id="createFolderForm" class="form-horizontal">
	<?php echo Text::_('COM_NOKWEBDAV_FILE_BROWSER_FOLDER_LABEL'); ?>: <input required="" type="text" id="folder" name="folder" multiple="">
	<input type="hidden" name="task" value="create_folder_do">
	<input type="submit" value="<?php echo Text::_('COM_NOKWEBDAV_FILE_BROWSER_FOLDER_SUBMIT'); ?>">
</form>


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

// load tooltip behavior
JHtml::_('behavior.tooltip');
$script = "/* <![CDATA[ */
Joomla.submitbutton = function(pressbutton) {
	if (pressbutton == 'projects.import_do')
	{
		// do field validation
		var form = document.getElementById('adminForm');
		if (form.importfile.value == \"\")
		{
			alert('".JText::_('COM_NOKWEBDAV_SHARES_IMPORT_ERROR')."');
			return false;
		}
		jQuery('#loading').css('display', 'block');
	}
	submitform(pressbutton);
	return true;
}
/* ]]> */";
JFactory::getDocument()->addScriptDeclaration($script);
?>
<form action="<?php echo JRoute::_('index.php?option=com_nokwebdav'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	<fieldset class="uploadform">
		<legend><?php echo JText::_('COM_NOKWEBDAV_SHARES_IMPORT_TITLE'); ?></legend>
		<div class="control-group">
			<label for="importfile" class="control-label"><?php echo JText::_('COM_NOKWEBDAV_SHARES_IMPORT_FILE_LABEL'); ?></label>
			<div class="controls">
				<input class="input_box" id="importfile" name="importfile" type="file" size="57" />
			</div>
		</div>
		<div class="form-actions">
			<input class="btn btn-primary" type="button" value="<?php echo JText::_('COM_NOKWEBDAV_SHARES_IMPORT_BUTTON'); ?>" onclick="Joomla.submitbutton('shares.import_do')" />
		</div>
	</fieldset>
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>

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

// No direct access
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.tooltip');
JHtml::_('formbehavior.chosen', 'select');
?>
<form action="<?php echo JRoute::_('index.php?option=com_nokwebdav&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" name="adminForm" id="adminForm">
	<div class="row-fluid">
		<div class="span12">
			<div class="row-fluid form-horizontal-desktop">
				<div class="span12">
					<?php echo $this->form->renderField('name'); ?>
				</div>
			</div>
		</div>
	</div>
	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_NOKWEBDAV_CONTAINER_TAB_COMMON', true)); ?>
		<div class="row-fluid">
			<div class="span12">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span6">
						<?php echo $this->form->renderField('type'); ?>
						<?php echo $this->form->renderField('filepath'); ?>
						<?php echo $this->form->renderField('published'); ?>
					</div>
					<div class="span6">
						<?php echo $this->form->renderField('quotaValue'); ?>
						<?php echo $this->form->renderField('quotaExp'); ?>
						<?php echo $this->form->renderField('query'); ?>
					</div>
				</div>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'custom', JText::_('COM_NOKWEBDAV_CONTAINER_TAB_RECORDINFO', true)); ?>
		<div class="row-fluid">
			<div class="span12">
				<div class="row-fluid form-horizontal-desktop">
					<div class="span6">
						<?php echo $this->form->renderField('createdby'); ?>
						<?php echo $this->form->renderField('createddate'); ?>
					</div>
					<div class="span6">
						<?php echo $this->form->renderField('modifiedby'); ?>
						<?php echo $this->form->renderField('modifieddate'); ?>
						<?php echo $this->form->renderField('id'); ?>
					</div>
				</div>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php if ($this->canDo->get('core.admin')) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('JCONFIG_PERMISSIONS_LABEL')); ?>
				<?php echo $this->form->getInput('rules'); ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>
	<input type="hidden" name="task" value="container.edit" />
	<?php echo JHtml::_('form.token'); ?>
</form>

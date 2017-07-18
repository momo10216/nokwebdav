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

defined('_JEXEC') or die;

class NoKWebDAVControllerShare extends JControllerForm {
	protected $component = 'com_nokwebdav';
	protected $type = 'share';

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowEdit($data = array(), $key = 'id') {
		$recordId = (int) isset($data[$key]) ? $data[$key] : 0;
		$user = JFactory::getUser();
		$userName = $user->get('name');
		// Check general edit permission first.
		if ($user->authorise('core.edit', $this->component.'.'.$this->type.'.'.$recordId)) { return true; }
		// Fallback on edit.own.
		// First test if the permission is available.
		if ($user->authorise('core.edit.own', $this->component.'.'.$this->type.'.'.$recordId)) {
			// Now test the owner is the user.
			$ownerName = isset($data['createdby']) ? $data['createdby'] : '';
			if (empty($createdby) && $recordId) {
				// Need to do a lookup from the model.
				$record = $this->getModel()->getItem($recordId);
				if (empty($record)) { return false; }
				$ownerName = $record->createdby;
			}
			// If the owner matches 'me' then do the test.
			if ($ownerName == $userName) { return true; }
		}
		// Since there is no asset tracking, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}
}
?>

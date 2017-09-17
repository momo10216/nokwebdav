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
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');
class NoKWebDAVModelContainers extends JModelList {
	public $_context = 'com_nokwebdav.containers';
	protected $_extension = 'com_nokwebdav';
	protected $paramsComponent;
	protected $paramsMenuEntry;
	private $_items = null;

	private function getFields() {
		$params = JComponentHelper::getParams('com_nokwebdav');
		return array (
			'id' => array(JText::_('COM_NOKWEBDAV_COMMON_FIELD_ID_LABEL',true),'`id`'),
			'name' => array(JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_NAME_LABEL',true),'`name`'),
			'type' => array(JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_TYPE_LABEL',true),'`type`'),
			'filepath' => array(JText::_('COM_NOKWEBDAV_CONTAINER_FIELD_FILEPATH_LABEL',true),'`filepath`'),
			'published' => array(JText::_('COM_NOKWEBDAV_COMMON_FIELD_PUBLISHED_LABEL',true),'`published`'),
			'createdby' => array(JText::_('COM_NOKWEBDAV_COMMON_FIELD_CREATEDBY_LABEL',true),'`createdby`'),
			'createddate' => array(JText::_('COM_NOKWEBDAV_COMMON_FIELD_CREATEDDATE_LABEL',true),'`createddate`'),
			'modifiedby' => array(JText::_('COM_NOKWEBDAV_COMMON_FIELD_MODIFIEDBY_LABEL',true),'`modifiedby`'),
			'modifieddate' => array(JText::_('COM_NOKWEBDAV_COMMON_FIELD_MODIFIEDDATE_LABEL',true),'`modifieddate`')
		);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null) {
		$app = JFactory::getApplication();
		$params = $app->getParams();
		$this->setState('params', $params);
		$this->setState('filter.published',1);
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return  string    An SQL query
	 * @since   1.6
	 */
	protected function getListQuery() {
		// Create a new query object.           
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		// Select some fields from the hello table
		$allFields = $this->getFields();
		$fields = array();
		foreach (array_keys($allFields) as $key) {
			if (isset($allFields[$key]) && !empty($allFields[$key])) {
				$field = $allFields[$key];
				array_push($fields,$field[1]." AS ".$key);
			}
		}
		$query->select($fields)->from($db->quoteName('#__nokWebDAV_containers'));
		// Filter by search in name.
		$query->where($db->quoteName('published').' = 1');
		// Add the list ordering clause.
		$query->order('name');
		return $query;
        }
}
?>

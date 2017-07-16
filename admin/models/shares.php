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

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * NoKWebDAV List Shares Model
 */
class NoKWebDAVModelShares extends JModelList {
	public function __construct($config = array()) {
		if (!isset($config['filter_fields']) || empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 's.id',
				'name', 's.name',
				'filepath', 's.filepath',
				'createddate', 'p.createddate',
				'createdby', 'p.createdby'
			);
			$app = JFactory::getApplication();
		}
		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null) {
		$app = JFactory::getApplication();
		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout')) {
			$this->context .= '.' . $layout;
		}
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
		// List state information.
		parent::populateState('s.name', 'asc');
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return      string  An SQL query
	 */
	protected function getListQuery() {
		// Create a new query object.           
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		// Select some fields from the hello table
		$query
			->select($db->quoteName(array('s.id', 's.name', 's.filepath')))
			->from($db->quoteName('#__nokWebDAV_shares','s'));
		// special filtering (houshold, excludeid).
		$whereExtList = array();
		$app = JFactory::getApplication();
		if ($excludeId = $app->input->get('excludeid')) {
			array_push($whereExtList,"NOT ".$db->quoteName("s.id")." = ".$excludeId);
		}
		$whereExt = implode(" AND ",$whereExtList);
		// Filter by search in name.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (!empty($whereExt)) $whereExt = " AND ".$whereExt;
			if (stripos($search, 'id:') === 0) {
				$query->where('s.id = ' . (int) substr($search, 3).$whereExt);
			} else {
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(s.name LIKE ' . $search . ' OR s.filepath LIKE ' . $search . ')'.$whereExt);
			}
		} else {
			if (!empty($whereExt)) {
				$query->where($whereExt);
			}
		}
		// Add the list ordering clause.
		$orderColText = $this->state->get('list.ordering', 's.name');
		$orderDirn = $this->state->get('list.direction', 'asc');
		$orderCols = explode(",",$orderColText);
		$orderEntry = array();
		foreach ($orderCols as $orderCol) {
			array_push($orderEntry,$db->escape($orderCol . ' ' . $orderDirn));
		}
		$query->order(implode(", ",$orderEntry));
                return $query;
        }

        /**
         * Method to build an SQL query to load the list data.
         *' 
         * @return      string  An SQL query
         */
        public function getFieldMapping() {
		return array (
			'name'=>'s.name',
			'filepath'=>'s.filepath',
			'createdby'=>'s.createdby',
			'createddate'=>'s.createddate'
		);
	}

	public function getTableName() {
		return "#__nokWebDAV_shares";
	}

	public function getIdFieldName() {
		return "id";
	}
}
?>
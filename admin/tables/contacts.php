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
 
// import Joomla table library
jimport('joomla.database.table');

/**
 * Contacts Table class
 */
class NoKWebDAVTableContacts extends JTable {
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db)  {
			parent::__construct('#__nokWebDAV_contacts', 'id', $db);
	}

	/**
	 * Stores a contact
	 *
	 * @param   boolean  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function store($updateNulls = false) {
		JLoader::register('TableHelper', __DIR__.'/../helpers/table.php', true);
		TableHelper::updateCommonFieldsOnSave($this);
		return parent::store($updateNulls);
	}

	public function bind($array, $ignore = '') {
		// Bind the rules. 
		if (isset($array['rules']) && is_array($array['rules'])) { 
			$rules = new JRules($array['rules']); 
			$this->setRules($rules); 
		}
		return parent::bind($array, $ignore);
	}
	
	/**
	 * Redefined asset name, as we support action control
	 */
        protected function _getAssetName() {
		$k = $this->_tbl_key;
		return 'com_nokwebdav.contact.'.(int) $this->$k;
        }
    
        /**
         * We provide our global ACL as parent
     	 * @see JTable::_getAssetParentId()
         */
	protected function _getAssetParentId($table = null, $id = null) {
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_nokwebdav');
		return $asset->id;
	}}
}
?>

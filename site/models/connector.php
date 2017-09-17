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

class NoKWebDAVModelConnector extends JModelForm {
	protected $view_item = 'connector';
	protected $_item = null;
	protected $_membershipItems = null;
	protected $_model = 'connector';
	protected $_component = 'com_nokwebdav';
	protected $_context = 'com_nokwebdav.connector';
	protected $_taskItems = null;

	protected function populateState() {
	}

	public function getTable($type = '', $prefix = 'NoKWebDAVTable', $config = array()) {
		return null;
	}

	public function getForm($data = array(), $loadData = true) {
		return false;
	}

	public function &getItem($pk = null) {
		return null;
	}

}
?>

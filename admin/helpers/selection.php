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

/**
 * Selection helper.
 *
 * @package     Joomla
 * @subpackage  com_clubmanagement
 * @since       3.0
 */
class SelectionHelper {
	public static function getSelection($paramName) {
		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_nokwebdav');
		$selectionText = $params->get($paramName);
		$selectionRows = explode(";",$selectionText);
		$result = array();
		foreach ($selectionRows as $selectionRow) {
			$fields = explode("=",$selectionRow,2);
			if (count($fields) < 2) {
				$result[$fields[0]] = $fields[0];
			} else {
				$result[$fields[0]] = $fields[1];
			}
		}
		return $result;
	}
}
?>

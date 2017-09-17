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
 
class WebDAVHelperPluginCommand {
	public static function execute($fileLocation) {
		$status = self::_check($fileLocation);
		$header = array();
		$content = '';
		$outFile = '';
		if (!$status) { $status = self::_delete($fileLocation); }
		return array($status, $header, $content, $outFile);
	}

	private static function _check($fileLocation) {
		global $_SERVER;
		if (!file_exists($fileLocation)) { return WebDAVHelper::$HTTP_STATUS_ERROR_CONFLICT; }
		if (WebDAVHelper::isLocked('files', $fileLocation)) {
			return WebDAVHelper::$HTTP_STATUS_ERROR_LOCKED;
		}
		return '';
	}

	private static function _delete($fileLocation) {
		if (is_dir($fileLocation)) {
			$output = array();
			$return_var = 0;
			$command = sprintf("rm -rf '%s'", str_replace('\'','',$fileLocation));
//			WebDAVHelper::debugAddMessage('Delete: shell command = '.$command);
			exec($command, $output, $return_var);
			$status = ($return_var == 0);
			if ($status) {
				self::_deleteLockAndProperty($fileLocation, true);
			}
		} else {
			$status = unlink($fileLocation);
			if ($status) {
				self::_deleteLockAndProperty($fileLocation, false);
			}
		}
		if (!$status) { return WebDAVHelper::$HTTP_STATUS_ERROR_FORBIDDEN; }
		return WebDAVHelper::$HTTP_STATUS_OK;
	}

	private static function _deleteLockAndProperty($resourceLocation, $recursiv=false) {
		$db = JFactory::getDBO();
		$whereLocarion = $db->quoteName('resourcelocation').'='.$db->quote(rtrim($resourceLocation,DIRECTORY_SEPARATOR));
		// delete lock
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__nokWebDAV_locks'))
			->where($db->quoteName('resourcetype').'='.$db->quote('files').' AND '.$whereLocarion);
		$db->setQuery($query);
//		WebDAVHelper::debugAddQuery($query);
		$db->execute();
		// delete properties
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__nokWebDAV_properties'))
			->where($db->quoteName('resourcetype').'='.$db->quote('files').' AND '.$whereLocarion);
		$db->setQuery($query);
//		WebDAVHelper::debugAddQuery($query);
		$db->execute();
		if ($recursiv) {
			$whereLocarion = $db->quoteName('resourcelocation').' LIKE '.$db->quote(rtrim($resourceLocation,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'%');
			// delete recursive locks
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__nokWebDAV_locks'))
				->where($db->quoteName('resourcetype').'='.$db->quote('files').' AND '.$whereLocarion);
			$db->setQuery($query);
//			WebDAVHelper::debugAddQuery($query);
			$db->execute();
			// delete recursive properties
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__nokWebDAV_properties'))
				->where($db->quoteName('resourcetype').'='.$db->quote('files').' AND '.$whereLocarion);
			$db->setQuery($query);
//			WebDAVHelper::debugAddQuery($query);
			$db->execute();
		}
	}
}
?>

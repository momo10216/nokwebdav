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

class NoKWebDAVViewFilebrowser extends JViewLegacy {
	protected $item;
	protected $pageHeading = 'COM_NOKWEBDAV_PAGE_TITLE_DEFAULT';
	protected $paramsComponent;
	protected $paramsMenuEntry;
	protected $user;
	protected $id;
	protected $path;
	protected $files = array();
	protected $error = '';
        protected $dirRead = false;
	protected $dirEntries = array();
        protected $separateFolderFiles = true;
        protected $sortDescending = false;
        protected $multiFileOption = true;

	function display($tpl = null) {
		// Init variables
		$this->user = JFactory::getUser();
		$app = JFactory::getApplication();
		$currentMenu = $app->getMenu()->getActive();
		if (is_object( $currentMenu )) {
			$this->paramsMenuEntry = $currentMenu->params;
		}

		// Read infos from URI
		$this->path = $app->input->get('davpath', '/', 'STRING');
		$this->files = $app->input->get('davfiles', array(), 'STRING');
		$file = $app->input->get('davfile', '', 'STRING');
		if ($file != '') {
			array_push($this->files, $file);
		}

		// Sanitize inputs
		$this->_sanitzeInput($this->path,'^[0-9a-zA-Z\ .-_+äöüÄÖÜß=()\/]*$',array('..'));
		foreach($this->files as $file) {
			$this->_sanitzeInput($file,'^[0-9a-zA-Z\ .,-_+äöüÄÖÜß=()]*$');
		}

		// Set correct model
		$this->item = $this->get('Item');

		switch($this->error) {
			case 'sanitize':
				$app->redirect('index.php', 400);
				break;
			case 'delete':
				$app->redirect('index.php', 200);
				break;
			default:
				// Init document
				JFactory::getDocument()->setMetaData('robots', 'noindex, nofollow');
				parent::display($tpl);
				break;
		}
	}

	function getDirEntries() {
		$this->_readDir();
		return $this->dirEntries;
	}

	function getUser() {
		return $this->user;
	}

	function getPath() {
		return $this->path;
	}

	function getItem() {
		return $this->item;
	}

	function getMultiFileOption() {
		return $this->multiFileOption;
	}

	function getIconPath() {
		return JURI::base().'components/com_nokwebdav/icons';
	}

	function getListLink($text, $path) {
		$uriList = new JURI(JURI::Root().'index.php');
		$uriList->setVar('id', $this->item->id);
		$uriList->setVar('view', 'filebrowser');
		$uriList->setVar('option', 'com_nokwebdav');
		$uriList->setVar('davpath', $path);
		$uriList->setVar('task', 'list');
		return '<a href="'.$uriList->toString().'">'.$text.'</a>';
	}

	function deleteFiles() {
		$this->dirRead = false;
		$path = $this->_getFullPath();
		foreach($this->files as $file) {
			$fileWithPath = $path.'/'.$file;
			if (is_dir($fileWithPath)) {
				if (!rmdir($fileWithPath)) {
					$app = JFactory::getApplication();
					$app->enqueueMessage(JText::_('COM_NOKWEBDAV_DELETE_ERROR'), 'error');
					$this->error = 'delete';
				}
			} else {
				if (!unlink($fileWithPath)) {
					$app = JFactory::getApplication();
					$app->enqueueMessage(JText::_('COM_NOKWEBDAV_DELETE_ERROR'), 'error');
					$this->error = 'delete';
				}
			}
		}
	}

	function _sanitzeInput($value, $regexp, $notAllowedList=array()) {
		if (preg_match('/'.$regexp.'/', $value) < 1) {
			$this->_displaySanitizationError();
		}
		foreach($notAllowedList as $notAllowed) {
			if (strpos($value, $notAllowed) !== false) {
				$this->_displaySanitizationError();
			}
		}
	}

	function _displaySanitizationError() {
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_('COM_NOKWEBDAV_SANITIZE_ERROR'), 'error');
		$this->error = 'sanitize';
	}

	function _readDir() {
		if ($this->dirRead) {
			return;
		}
		$folders = array();
		$files = array();
		$path = $this->_getFullPath();
		if ($handle = opendir($path)) {
			// Collect infos
			while (false !== ($entry = readdir($handle))) {
				$file = $path.'/'.$entry;
				if (is_dir($file)) {
					if (($entry != '.') && ($entry != '..')) {
						$folders[$entry] = lstat($file);
						$folders[$entry]['type'] = 'folder';
					}
				} else {
					$files[$entry] = lstat($file);
					$files[$entry]['type'] = $this->_getFileType($entry);
				}
			}
			closedir($handle);
			$this->_sortDirEntries($folders, $files);
			$this->dirRead = true;
		}
	}

	function _getFullPath() {
		JLoader::register('WebDAVHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav.php', true);
		$baseDir = $this->item->filepath;
		if ((strlen($baseDir) < 1) || (substr($baseDir,0,1) != '/')) {
			// relative path
			$baseDir = WebDAVHelper::joinDirAndFile(JPATH_BASE,$baseDir);
		}
		return WebDAVHelper::joinDirAndFile($baseDir, $this->path);
	}

	function _sortDirEntries($folders, $files) {
		if ($this->separateFolderFiles) {
			if ($this->sortDescending) {
				$this->dirEntries = array_merge($this->_keySort($files),$this->_keySort($folders));
			} else {
				$this->dirEntries = array_merge($this->_keySort($folders),$this->_keySort($files));
			}
		} else {
			$this->dirEntries = $this->_keySort(array_merge($folders,$files));
		}
	}

	function _keySort($list) {
		if ($this->sortDescending) {
			krsort($list);
		} else {
			ksort($list);
		}
		return $list;
	}

	function _getFileType($file) {
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		switch($ext) {
			case 'png':
			case 'jpg':
			case 'jpeg':
			case 'gif':
				return 'image';
			case 'doc':
			case 'docx':
			case 'odt':
				return 'document';
			case 'xls':
			case 'xlsx':
			case 'ods':
				return 'spreadsheet';
			case 'c':
			case 'java':
			case 'sh':
				return 'code';
		}
		return '';
	}
}

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
        protected $allowRecursiveDelete = true;
        protected $allowFolderCreation = true;
	protected $task = '';

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
		$this->task = $app->input->get('task', 'list', 'STRING');
		$this->folder = $app->input->get('folder', '', 'STRING');

		// Sanitize inputs
		$this->_sanitzeInput($this->path,'^[0-9a-zA-Z\ .-_+äöüÄÖÜß=()\/]*$',array('..'));
		foreach($this->files as $file) {
			$this->_sanitzeInput($file,'^[0-9a-zA-Z\ .,-_+äöüÄÖÜß=()]*$');
		}
		$this->_sanitzeInput($this->task,'^[a-z_]*$');
		$this->_sanitzeInput($this->folder,'^[0-9a-zA-Z\ .-_+äöüÄÖÜß=()\/]*$',array('..'));

		// Set correct model
		$this->item = $this->get('Item');

		// Read config

		if ($this->task == 'download') {
			$this->download();
		}

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

	function isFolderCreationAllowed() {
		return $this->allowFolderCreation;
	}

	function deleteFiles() {
		$this->dirRead = false;
		$path = $this->_getFullPath();
		foreach($this->files as $file) {
			$fileWithPath = $path.'/'.$file;
			if (is_dir($fileWithPath)) {
				if ($this->allowRecursiveDelete) {
					$this->_deleteDirRecursive($fileWithPath);
				}
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

	function uploadFiles($fieldName) {
		global $_FILES;
		if (is_array($_FILES[$fieldName]['name'])) {
			// Upload multiple files
			foreach($_FILES[$fieldName]['name'] as $key => $name) {
				$this->_uploadFile($name, $_FILES[$fieldName]['tmp_name'][$key]);
			}
		} else {
			// Upload single files
			$this->_uploadFile($_FILES[$fieldName]['name'], $_FILES[$fieldName]['tmp_name']);
		}
		$this->dirRead = false;
	}

	function download() {
		while ($this->_resolveDirectories()) {}
		if (count($this->files) == 1) {
			$path = $this->_getFullPath();
			$this->_downloadFile($path.'/'.$this->files[0], $this->files[0]);
		}
		if (count($this->files) > 1) {
			$zipFile = $this->_createZipFile();
			if ($zipFile != '') {
				$this->_downloadFile($zipFile, date('Ymd_His').'.zip');
				unlink($zipFile);
			} else {
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::_('COM_NOKWEBDAV_ZIP_ERROR'), 'error');
				$this->error = 'create_zip';
			}
		}
	}

	function createFolder() {
		$path = $this->_getFullPath();
		mkdir($path.'/'.$this->folder);
	}

	function _deleteDirRecursive($dir) {
		if ($dh = opendir($dir)) {
			while (($dirEntry = readdir($dh)) !== false) {
				if (($dirEntry != '.') && ($dirEntry != '..')) {
					$relFile = $dir.'/'.$dirEntry;
					if (is_dir($relFile)) {
						$this->_deleteDirRecursive($relFile);
						rmdir($relFile);
					} else {
						unlink($relFile);
					}
				}
			}
			closedir($dh);
		}
	}

	function _resolveDirectories() {
		$resolved = false;
		$path = $this->_getFullPath();
		$newFileList = array();
		foreach($this->files as $file) {
			if (is_dir($path.'/'.$file)) {
				$resolved = true;
				if ($dh = opendir($path.'/'.$file)) {
					while (($dirEntry = readdir($dh)) !== false) {
						if (($dirEntry != '.') && ($dirEntry != '..')) {
							$relFile = $file.'/'.$dirEntry;
							array_push($newFileList, $relFile);
						}
					}
					closedir($dh);
				}
			} else {
				array_push($newFileList, $file);
			}
		}
		if ($resolved) {
			$this->files = $newFileList;
		}
		return $resolved;
	}

	function _createZipFile() {
		$zipfilename = tempnam(sys_get_temp_dir(), '');
		$path = $this->_getFullPath();
		$zip = new ZipArchive;
		if ($zip->open($zipfilename) === TRUE) {
			foreach($this->files as $file) {
				$zip->addFile($path.'/'.$file, $file);
			}
			$zip->close();
		} else {
		    $zipfilename = '';
		}
		return $zipfilename;
	}

	function _downloadFile($localfile, $filename = '') {
		if ($filename == '') {
			$filename = basename($localfile);
		}
		JLoader::register('WebDAVHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav.php', true);
		// Transfer header
		header('Content-Type: '.WebDAVHelper::getMimeType($localfile));
		header('Content-Length: '.filesize($localfile));
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Pragma: no-cache');
		// Transfer content
		$fhRead = fopen($localfile, 'rb');
		$fhWrite = fopen('php://output', 'wb');
		stream_copy_to_stream($fhRead, $fhWrite);
		fclose($fhRead);
		fclose($fhWrite);
		// Close the application.
		$app = JFactory::getApplication();
		$app->close();
	}

	function _uploadFile($name, $tmp_name) {
		$filename = basename($name);
		move_uploaded_file($tmp_name, $this->_getFullPath().'/'.$name);
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
?>

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

define('_JEXEC', 1);

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

function getInfosFromPath() {
	global $_SERVER;
	if (!isset($_SERVER['PATH_INFO'])) {
		WebDAVHelper::debugAddArray($_SERVER, '_SERVER');
		return array('','');
	}
	JLog::add('getInfosFromPath Path: '.$_SERVER['PATH_INFO'], JLog::DEBUG);
	$locElements = explode('/',$_SERVER['PATH_INFO']);
	if (count($locElements) < 2) { return array('',''); }
	$containerName = $locElements[1];
	unset($locElements[1]);
	$location = implode('/',$locElements);
	if (empty($location)) { $location = '/'; }
	JLog::add('getInfosFromPath containerName: '.$containerName, JLog::DEBUG);
	JLog::add('getInfosFromPath location: '.$location, JLog::DEBUG);
	return array($containerName, $location);
}

function getTargetInfosFromUrl($url,$containerName) {
	global $_SERVER;

	$location = str_replace($_SERVER['SCRIPT_NAME'],'',$url);
	$location = '/'.explode('/'.$containerName.'/',$location)[1];
	return array($containerName, $location);
}

function getAccess($id) {
	$commands = array('read','create','change','delete');
	$access = array();
	$user = JFactory::getUser();
	$assetName = 'com_nokwebdav.container.'.$id;
	foreach($commands as $command) {
		$access[$command] = $user->authorise('content.'.$command, $assetName);
	}
	return $access;
}

function handleAuthentication() {
	global $_SERVER;

	if (!isset($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_USER'])) {
		if (isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
		} else {
			if (isset($_GET['Authorization']) && preg_match('/Basic\s+(.*)$/i', $_GET['Authorization'], $matches)) {
				list($name, $password) = explode(':', base64_decode($matches[1]));
				$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
				$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
			}
		}
	}
	//JLog::add('SERVER: '.json_encode($_SERVER), JLog::DEBUG);
	if (isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER'])) {
		$user = JFactory::getUser();
		if ($user->username != $_SERVER['PHP_AUTH_USER']) {
			$user = '';
			$password = '';
			if (isset($_SERVER['PHP_AUTH_USER'])) { $user = $_SERVER['PHP_AUTH_USER']; }
			if (isset($_SERVER['PHP_AUTH_PW'])) { $password = $_SERVER['PHP_AUTH_PW']; }
			$app = JFactory::getApplication();
			if (!empty($user)) {
				$credentials = array('username' => $user,'password' => $password);
				$options = array();
				if (!$app->login($credentials, $options)) {
					JLog::add('Login failed for user "'.$user.'"!', JLog::ERROR);
					return false;
				} else {
					JLog::add('Login success for user "'.$user.'"!', JLog::DEBUG);
				}
			}
		} else {
			JLog::add('User "'.$user->username.'" already logged in.', JLog::DEBUG);
		}
	} else {
		JLog::add('No username provided!', JLog::ERROR);
		return false;
	}
	return true;
}

function getUriLoaction() {
	global $_SERVER;
	if (isset($_SERVER["HTTPS"]) && !empty($_SERVER["HTTPS"])) {
		$uri = 'https';
	} else {
		$uri = 'http';
	}
	$uri .= '://'.$_SERVER["SERVER_NAME"];
	if ($_SERVER["SERVER_PORT"] != 80 ) { $uri .= ':'.$_SERVER["SERVER_PORT"]; }
	$uri .= $_SERVER["REQUEST_URI"];
	return $uri;
}

register_shutdown_function(function(){
    $err = error_get_last();
    if(is_array($err) && isset($err['type']) && ($err['type'] == E_ERROR || $err['type'] == E_PARSE)) {
        error_log("Fatal error: ".var_export($err, true), 1);
    }
});

error_reporting(E_ERROR | E_PARSE);
$component = 'com_nokwebdav';

define('JPATH_BASE', explode('/components/'.$component,__DIR__)[0]);
define('JPATH_COMPONENT', JPATH_BASE . DIRECTORY_SEPARATOR . 'components'. DIRECTORY_SEPARATOR . $component);
include_once (JPATH_BASE.'/includes/defines.php' );
if (!defined('JPATH_ADMINISTRATOR')) {
	define('JPATH_ADMINISTRATOR', JPATH_BASE . DIRECTORY_SEPARATOR . 'administrator');
}
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components'. DIRECTORY_SEPARATOR . $component);

require_once (JPATH_BASE.'/includes/framework.php' );
jimport( 'joomla.application.application' );
jimport( 'joomla.filter.filteroutput' );
$app = JFactory::getApplication('site');

// Logging
jimport('joomla.log.log');
JLog::addLogger(
	array(
		'text_file' => 'nokwebdav.log',
		'text_file_path' => '../../tmp/',
		'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
	),
//	JLog::ERROR | JLog::DEBUG
//	JLog::ALL
	JLog::ERROR
);
ini_set("log_errors", 1);
ini_set("error_log", JPATH_BASE.'/tmp/nokwebdav.log');

// Auth
handleAuthentication();

// Init controller
jimport('joomla.application.component.controller');
$controller = JControllerLegacy::getInstance('NoKWebDAV');
$container = $controller->getModel('container');

JLoader::register('WebDAVHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav.php', true);
$uriLocation = getUriLoaction();
list ($containerName, $location) = getInfosFromPath();
$item = $container->getItemByName($containerName);
if ($item === false || !$item->published) {
	JLog::add('Container "'.$containerName.'" not found.', JLog::ERROR);
	WebDAVHelper::sendHttpStatusAndHeaders(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND);
} else {
	JLog::add('Container "'.$containerName.'" found.', JLog::DEBUG);
	$webdavHelper = '';
	switch($item->type) {
		case 'files':
			JLog::add('Container contains files.', JLog::DEBUG);
			$baseDir = $item->filepath;
			JLog::add('File path: '.$baseDir, JLog::DEBUG);
			if ((strlen($baseDir) < 1) || (substr($baseDir,0,1) != '/')) {
				// relative path
				$baseDir = WebDAVHelper::joinDirAndFile(JPATH_BASE,$item->filepath);
			}
			JLog::add('Root dir: '.$baseDir, JLog::DEBUG);
			$fileLocation = WebDAVHelper::joinDirAndFile($baseDir, $location);
			$access = getAccess($item->id);
			$targetFileLocation = '';
			$targetAccess = array();
			$quota = round($item->quotaValue*pow(1024, $item->quotaExp),0);
			JLog::add('quota: '.$quota, JLog::DEBUG);
			if (isset($_SERVER["HTTP_DESTINATION"]) && !empty($_SERVER["HTTP_DESTINATION"])) {
				list ($targetContainerName, $targetLocation) = getTargetInfosFromUrl($_SERVER['HTTP_DESTINATION'], $containerName);
				JLog::add($_SERVER["HTTP_DESTINATION"].' => ('.$targetContainerName.', '.$targetLocation.')', JLog::DEBUG);
				if ($targetContainerName == $containerName) {
					$targetFileLocation = WebDAVHelper::joinDirAndFile($baseDir, $targetLocation);
					$targetAccess = $access;
				} else {
					$targetItem = $container->getItemByName($containerName);
					$targetBaseDir = $targetItem->filepath;
					if ((strlen($targetBaseDir) < 1) || (substr($targetBaseDir,0,1) != '/')) {
						// relative path
						$targetBaseDir = WebDAVHelper::joinDirAndFile(JPATH_BASE,$targetItem->filepath);
					}
					$targetFileLocation = WebDAVHelper::joinDirAndFile($targetBaseDir, $targetLocation);
					$targetAccess = getAccess($item->id);
				}
			}
			$webdavHelper = WebDAVHelper::getFilesInstance($access, $baseDir, $fileLocation, $targetAccess, $targetFileLocation, $uriLocation, $quota);
			break;
		default:
			break;
	}
	$webdavHelper->run();
}

// Exit
flush();
$app->close();
?>

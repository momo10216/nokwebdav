<?php
function getInfosFromUrl($url) {
	global $_SERVER;

	$location = str_replace($_SERVER['SCRIPT_NAME'],'',$url);
	$locElements = explode('/',$location);
	if (count($locElements) < 2) { return array('',''); }
	$containerName = $locElements[1];
	unset($locElements[1]);
	$location = implode('/',$locElements);
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

	if (isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER'])) {
		$user = JFactory::getUser();
		if ($user->username != $_SERVER['PHP_AUTH_USER']) {
			$user = '';
			$password = '';
			if (isset($_SERVER['PHP_AUTH_USER'])) { $user = $_SERVER['PHP_AUTH_USER']; }
			if (isset($_SERVER['PHP_AUTH_PW'])) { $password = $_SERVER['PHP_AUTH_PW']; }
			$app = JFactory::getApplication();
			//JLog::add('User: '.$user, JLog::DEBUG);
			//JLog::add('Password: '.$password, JLog::DEBUG);
			$credentials = array('username' => $user,'password' => $password);
			$options = array();
			$app->login($credentials, $options);
		} else {
			//JLog::add('User "'.$user->username.'" already logged in.', JLog::DEBUG);
		}
	} else {
		//JLog::add('No username provided.', JLog::DEBUG);
	}
	return true;
}

$component = 'com_nokwebdav';

define('_JEXEC', 1);
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
		'text_file_path' => '/tmp/',
		'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
	),
	JLog::ERROR | JLog::DEBUG
);

// Auth
handleAuthentication();

// Init controller
jimport('joomla.application.component.controller');
$controller = JControllerLegacy::getInstance('NoKWebDAV');
$container = $controller->getModel('container');

JLoader::register('WebDAVHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav.php', true);
$uriLocation = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
list ($containerName, $location) = getInfosFromUrl($_SERVER['PATH_INFO']);
$item = $container->getItemByName($containerName);
if ($item === false || !$item->published) {
	JLog::add('Container "'.$containerName.'" not found.', JLog::ERROR);
	WebDAVHelper::sendHttpStatusAndHeaders(WebDAVHelper::$HTTP_STATUS_ERROR_NOT_FOUND);
} else {
	$webdavHelper = '';
	switch($item->type) {
		case 'files':
			$baseDir = $item->filepath;
			if ((strlen($baseDir) < 1) || (substr($baseDir,0,1) != '/')) {
				// relative path
				$baseDir = WebDAVHelper::joinDirAndFile(JPATH_BASE,$item->filepath);
			}
			$fileLocation = WebDAVHelper::joinDirAndFile($baseDir, $location);
			$access = getAccess($item->id);
			$targetFileLocation = '';
			$targetAccess = array();
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
			$webdavHelper = WebDAVHelper::getFilesInstance($access, $fileLocation, $targetAccess, $targetFileLocation, $uriLocation);
			break;
		default:
			break;
	}

//print_r(getAccess($item->id));
//echo "$currentDir $uriLocation\n";
//flush();

	$webdavHelper->run();
}

// Exit
$app->close();
?>

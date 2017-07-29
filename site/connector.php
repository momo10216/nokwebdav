<?php
function getInfosFromUrl() {
	global $_SERVER;
	$location = str_replace($_SERVER["SCRIPT_INFO"],'',$_SERVER["PATH_INFO"]);
	$locElements = explode('/',$location);
	if (count($locElements) < 2) { return array('',''); }
	$containerName = $locElements[1];
	unset($locElements[1]);
	$location = implode('/',$locElements);
	return array($containerName, $location);
}

function joinDirAndFile($directory, $filename) {
	if (substr($directory,-1) == '/') {
		if (substr($filename,0,1) == '/') {
			return $directory.substr($filename,1);
		} else {
			return $directory.$filename;
		}
	} else {
		if (substr($filename,0,1) == '/') {
			return $directory.$filename;
		} else {
			return $directory.'/'.$filename;
		}
	}
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
	JLog::ALL
);

// Init controller
jimport('joomla.application.component.controller');
$controller = JControllerLegacy::getInstance('NoKWebDAV');
$container = $controller->getModel('container');

JLoader::register('WebDAVHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav.php', true);
$type = 'files';
$access = array('read' => true, 'create' => true, 'change' => true, 'delete' => true);
$uriLocation = $_SERVER['PHP_SELF'];
list ($containerName, $location) = getInfosFromUrl();
$item = $container->getItemByName($containerName);
$baseDir = $item->filepath;
if ((strlen($baseDir) < 1) || (substr($baseDir,0,1) != '/')) {
	// relative path
	$baseDir = joinDirAndFile(JPATH_BASE,$item->filepath);
}
$currentDir = joinDirAndFile($baseDir,$location);
//$webdavHelper = WebDAVHelper::getFilesInstance($access, $currentDir, $uriLocation);
//$webdavHelper->run();

print_r(getAccess($item->id));
//echo "$currentDir $uriLocation\n";
flush();
// Exit
$app->close();
?>

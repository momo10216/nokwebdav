<?php
function getLocation() {
	global $_SERVER;
	$location = str_replace($_SERVER["SCRIPT_INFO"],'',$_SERVER["PATH_INFO"]);
	return $location;
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
JDEBUG ? JProfiler::getInstance('Application')->setStart($startTime, $startMem)->mark('afterLoad') : null;
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

/*
// Init controller
jimport('joomla.application.component.controller');
$controller = JControllerLegacy::getInstance('NoKWebDAV');
*/

JLoader::register('WebDAVHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/webdav.php', true);
$type = 'files';
$access = array('read' => true, 'create' => true, 'change' => true, 'delete' => true);
$baseDir = '/var/www/html/J3';
$uriLocation = $_SERVER['PHP_SELF'];
$webdavHelper = WebDAVHelper::getFilesInstance($access, joinDirAndFile($baseDir,getLocation()), $uriLocation);
$webdavHelper->run();

// Exit
$app->close();
?>

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

/*
More Info http://php.net/manual/en/book.simplexml.php
*/

// No direct access
defined('_JEXEC') or die('Restricted access');
 
class ExImportHelper {
	private static $_modelStructure = array(
		'Containers' => array('Containers', 'Container', 'Container', '', array())
	);
	private static $_component = 'NoKWebDAV';

	public static function export() {
		$xmlRoot = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><'.self::$_component.'></'.self::$_component.'>');
		self::_exportList($xmlRoot,self::$_modelStructure);
		self::_saveXML($xmlRoot->asXML(),'export-'.date('Ymd').'.xml');
	}

	public static function import($xmltext) {
		$xmlRoot = new SimpleXMLElement($xmltext);
		self::_importList($xmlRoot,self::$_modelStructure);
	}

	private static function _exportList(&$xmlNode, $list, $parentId='') {
		$db = JFactory::getDBO();
		foreach($list as $modelName => $exportProp) {
			list($listName, $entryName, $singleModelName, $parentIdFieldName, $childs) = $exportProp;
			$xmlList = $xmlNode->addChild($listName);
			$model = JControllerLegacy::getInstance(self::$_component)->getModel($modelName);
			$rows = $model->getExportData($parentId);
			$excludeFields = $model->getExportExcludeFields();
			foreach($rows as $row) {
				$xmlEntry = $xmlList->addChild($entryName);
				foreach($row as $field => $value) {
					if (array_search($field,$excludeFields) === false) {
						$xmlEntry->addAttribute($field,$value);
					}
				}
				if (isset($childs) && is_array($childs) && (count($childs)>0)) {
					self::_exportList($xmlEntry,$childs,$row[$model->getExImportPrimaryKey()]);
				}
			}
		}
	}

	private static function _saveXML($xmltext, $filename) {
		header('Content-Type: text/xml; charset=utf-8');
		header('Content-Length: '.strlen($xmltext));
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Pragma: no-cache');
		print $xmltext;
		// Close the application.
		$app = JFactory::getApplication();
		$app->close();
	}

	private static function _importList(&$xmlNode, $list, $parentId='') {
		foreach ($xmlNode->children() as $listChild) {
			list($modelName, $importProp) = self::_getModelEntryByListName($list, $listChild->getName());
			if (!empty($modelName) && (count($importProp)>0)) {
				list($listName, $entryName, $singleModelName, $parentIdFieldName, $childs) = $importProp;
				$model = JControllerLegacy::getInstance(self::$_component)->getModel($modelName);
				foreach ($listChild->children() as $entryChild) {
					$rowData = current($entryChild->attributes());
					if (!empty($parentIdFieldName) && !empty($parentId)) { $rowData[$parentIdFieldName] = $parentId; }
					$rowData[$model->getExImportPrimaryKey()] = self::_importRow($model,$rowData,$singleModelName,$parentId);
					if (isset($childs) && is_array($childs) && (count($childs)>0)) {
						self::_importList($entryChild,$childs,$rowData[$model->getExImportPrimaryKey()]);
					}
				}
			}
		}
	}

	private static function _importRow($model, $rowData, $singleModelName, $parentId='') {
		$db = JFactory::getDBO();
		$rowData = self::_resolveForeignKeys($model, $rowData);
		$parentIdField = $model->getExImportParentFieldName();
		$singleModel = JControllerLegacy::getInstance(self::$_component)->getModel($singleModelName);
		$singleTable = $singleModel->getTable();
		if (!empty($parentId) && !empty($parentIdField)) {
			$rowData[$parentIdField] = $parentId;
		}
		if (method_exists($model,'importPreSave')) {
			$rowData = $model->importPreSave($rowData);
		}
		$id = self::_findRecordWithKeyFields($model, $rowData);
		$query = $db->getQuery(true);
		if ($id) {
			$rowData[$model->getExImportPrimaryKey()] = $id;
		}
		$singleTable->bind($rowData);
		$singleTable->store();
		$newRow = (array) $singleTable;
		if (!$id) {
			$id = $newRow[$model->getExImportPrimaryKey()];
		}
		if (method_exists($model,'importPostSave')) {
			$model->importPostSave($newRow,$id);
		}
		return $id;
	}

	private static function _getModelEntryByListName($list, $name) {
		foreach($list as $modelName => $importProp) {
			list($listName,$entryName,$childs) = $importProp;
			if ($listName == $name) {
				return array($modelName,$importProp);
			}
			if (isset($childs) && is_array($childs) && (count($childs)>0)) {
				list($resultModel, $resultImportProp) = self::_getModelEntryByListName($child,$name);
				if (!empty($resultModel)) { return array($resultModel, $resultImportProp); }
			}
		}
		return array('',array());
	}

	private static function _findRecordWithKeyFields($model, $row) {
		$db = JFactory::getDBO();
		$keyFields = $model->getExImportUniqueKeyFields();
		$expressions = array();
		foreach ($keyFields as $keyField) {
			array_push($expressions,$db->quoteName($keyField)."=".$db->quote($row[$keyField]));
		}
		$query = $db->getQuery(true);
		$where = implode(" AND ",$expressions);
		$query
			->select($db->quoteName($model->getExImportPrimaryKey()))
			->from($db->quoteName($model->getExImportTableName()))
			->where($where);
		$db->setQuery($query);
		$results = $db->loadRowList();
		if ($results) {
			return $results[0][0];
		}
		return false;
	}

	private static function _resolveForeignKeys($model, $row) {
		$foreign_keys = $model->getExImportForeignKeys();
		$db = JFactory::getDBO();
		foreach ($foreign_keys as $targetField => $foreignKeyData) {
			list($tableName, $tableAlias, $foreignPrimaryKey, $conditions) = $foreignKeyData;
			$where = array();
			foreach ($conditions as $tableField => $dataField) {
				if (empty($row[$dataField])) {
					array_push($where, "(".$db->quoteName($tableAlias.".".$tableField)." IS NULL OR ".
						$db->quoteName($tableAlias.".".$tableField)."='0000-00-00' OR ".
						$db->quoteName($tableAlias.".".$tableField)."='')");
				} else {
					array_push($where, $db->quoteName($tableAlias.".".$tableField)."=".$db->quote($row[$dataField]));
				}
				unset($row[$dataField]);
			}
			$query = $db->getQuery(true);
			$query->select($db->quoteName($tableAlias.".".$foreignPrimaryKey))
				->from($db->quoteName($tableName,$tableAlias))
				->where(implode(" AND ",$where));
			$db->setQuery($query);
			$results = $db->loadRowList();
			if ($results) {
				$row[$targetField] = $results[0][0];
			}
		}
		return $row;
	}
}
?>

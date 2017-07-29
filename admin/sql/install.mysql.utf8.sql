DROP TABLE IF EXISTS `#__nokWebDAV_events`;
DROP TABLE IF EXISTS `#__nokWebDAV_contacts`;
DROP TABLE IF EXISTS `#__nokWebDAV_containers`;

CREATE TABLE `#__nokWebDAV_containers` (
  `id` integer NOT NULL auto_increment,
  `asset_id` INT(255) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL default '',
  `type` varchar(30) NOT NULL default 'files',
  `filepath` varchar(50) NULL default NULL,
  `query` text NULL default NULL,
  `published` int(1) NOT NULL default 0,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  CONSTRAINT UC_container_name UNIQUE (`name`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_contacts` (
  `id` integer NOT NULL auto_increment,
  `asset_id` INT(255) UNSIGNED NOT NULL DEFAULT '0',
  `container_id` integer NOT NULL,
  `published` int(1) NOT NULL default 0,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_events` (
  `id` integer NOT NULL auto_increment,
  `asset_id` INT(255) UNSIGNED NOT NULL DEFAULT '0',
  `container_id` integer NOT NULL,
  `published` int(1) NOT NULL default 0,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;


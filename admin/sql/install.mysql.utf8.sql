DROP TABLE IF EXISTS `#__nokWebDAV_events`;
DROP TABLE IF EXISTS `#__nokWebDAV_contacts`;
DROP TABLE IF EXISTS `#__nokWebDAV_locks`;
DROP TABLE IF EXISTS `#__nokWebDAV_properties`;
DROP TABLE IF EXISTS `#__nokWebDAV_containers`;

CREATE TABLE `#__nokWebDAV_containers` (
	`id` integer NOT NULL auto_increment,
	`asset_id` INT(255) UNSIGNED NOT NULL DEFAULT '0',
	`name` varchar(50) NOT NULL default '',
	`type` varchar(30) NOT NULL default 'files',
	`filepath` varchar(50) NULL default NULL,
	`query` text NULL default NULL,
	`published` int(1) NOT NULL default 0,
	`quotaValue` float(12,8) NOT NULL default 0,
	`quotaExp` int(2) NOT NULL default 0,
	`createdby` varchar(50) NULL default NULL,
	`createddate` datetime NULL default NULL,
	`modifiedby` varchar(50) NOT NULL default '',
	`modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`id`),
	CONSTRAINT UC_container_name UNIQUE (`name`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_locks` (
	`token` varchar(255) NOT NULL default '',
	`resourcetype` varchar(30) NOT NULL default 'files',
	`resourcelocation` varchar(200) NOT NULL default '',
	`expires` int(11) NOT NULL default '0',
	`recursive` int(1) default 0,
	`scope` varchar(30) default NULL,
	`type` varchar(30) default NULL,
	`owner` varchar(200) default NULL,
	`createtime` int(11) NOT NULL default 0,
	`modifytime` int(11) NOT NULL default 0,
	PRIMARY KEY (`token`),
	KEY KEY_lock_location (`resourcetype`,`resourcelocation`),
	KEY KEY_lock_expires (`expires`),
	CONSTRAINT UC_lock_name UNIQUE (`resourcetype`,`resourcelocation`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_properties` (
	`id` integer NOT NULL auto_increment,
	`resourcetype` varchar(30) NOT NULL default 'files',
	`resourcelocation` varchar(200) NOT NULL default '',
	`name` varchar(120) NOT NULL default '',
	`namespace` varchar(120) NOT NULL default 'DAV:',
	`value` text,
	`createdby` varchar(50) NULL default NULL,
	`createddate` datetime NULL default NULL,
	`modifiedby` varchar(50) NOT NULL default '',
	`modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`id`),
	KEY KEY_property_location (`resourcetype`,`resourcelocation`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_contacts` (
	`id` integer NOT NULL auto_increment,
	`container_id` integer NOT NULL,
	`published` int(1) NOT NULL default 0,
	`createdby` varchar(50) NULL default NULL,
	`createddate` datetime NULL default NULL,
	`modifiedby` varchar(50) NOT NULL default '',
	`modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_events` (
	`id` integer NOT NULL auto_increment,
	`container_id` integer NOT NULL,
	`published` int(1) NOT NULL default 0,
	`createdby` varchar(50) NULL default NULL,
	`createddate` datetime NULL default NULL,
	`modifiedby` varchar(50) NOT NULL default '',
	`modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;


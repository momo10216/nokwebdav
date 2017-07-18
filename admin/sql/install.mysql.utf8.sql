DROP TABLE IF EXISTS `#__nokWebDAV_calendar_entries`;
DROP TABLE IF EXISTS `#__nokWebDAV_calendars`;
DROP TABLE IF EXISTS `#__nokWebDAV_contacts`;
DROP TABLE IF EXISTS `#__nokWebDAV_contact_lists`;
DROP TABLE IF EXISTS `#__nokWebDAV_shares`;

CREATE TABLE `#__nokWebDAV_shares` (
  `id` integer NOT NULL auto_increment,
  `name` varchar(50) NULL default NULL,
  `filepath` varchar(50) NULL default NULL,
  `published` int(1) NOT NULL default 0,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  CONSTRAINT UC_share_name UNIQUE (`name`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_contact_lists` (
  `id` integer NOT NULL auto_increment,
  `name` varchar(50) NULL default NULL,
  `query` text NULL default NULL,
  `published` int(1) NOT NULL default 0,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  CONSTRAINT UC_contactlist_name UNIQUE (`name`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_contacts` (
  `id` integer NOT NULL auto_increment,
  `list_id` integer NOT NULL,
  `published` int(1) NOT NULL default 0,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_calendars` (
  `id` integer NOT NULL auto_increment,
  `name` varchar(50) NULL default NULL,
  `query` text NULL default NULL,
  `published` int(1) NOT NULL default 0,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  CONSTRAINT UC_calendar_name UNIQUE (`name`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_calendar_entries` (
  `id` integer NOT NULL auto_increment,
  `calendar_id` integer NOT NULL,
  `published` int(1) NOT NULL default 0,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;


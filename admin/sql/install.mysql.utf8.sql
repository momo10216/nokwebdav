CREATE TABLE `#__nokWebDAV_shares` (
  `id` integer NOT NULL auto_increment,
  `name` varchar(50) NULL default NULL,
  `filepath` varchar(50) NULL default NULL,
  
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_contacts` (
  `id` integer NOT NULL auto_increment,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;

CREATE TABLE `#__nokWebDAV_events` (
  `id` integer NOT NULL auto_increment,
  `createdby` varchar(50) NULL default NULL,
  `createddate` datetime NULL default NULL,
  `modifiedby` varchar(50) NOT NULL default '',
  `modifieddate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
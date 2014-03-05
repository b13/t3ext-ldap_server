#
# Table structure for table 'tx_ldapserver'
#
CREATE TABLE tx_ldapserver (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(10) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	be tinyint(4) unsigned DEFAULT '0' NOT NULL,
	servername tinytext NOT NULL,
	servertype varchar(4) DEFAULT 'ad' NOT NULL,
	protocol varchar(4) DEFAULT '' NOT NULL,
	basedn tinytext NOT NULL,
	filter_person tinytext NOT NULL,
	username tinytext NOT NULL,
	password tinytext NOT NULL,
	config text NOT NULL,
	domain tinytext NOT NULL,
	charset tinytext NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_ldapserver_dn tinytext NOT NULL
);

#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	tx_ldapserver_dn tinytext NOT NULL
);

#
# Table structure for table 'fe_groups'
#
CREATE TABLE fe_groups (
	tx_ldapserver_dn tinytext NOT NULL
);

#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
	tx_ldapserver_dn tinytext NOT NULL
);
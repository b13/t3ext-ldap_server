<?php

########################################################################
# Extension Manager/Repository config file for ext "ldap_server".
#
# Auto generated 09-12-2009 14:44
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'LDAP Server Konfiguration',
	'description' => 'This extension provides a framework for extensions like ldap_auth and ldap_sync which need to connect to LDAP sources.',
	'category' => 'misc',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Daniel Thomas,Benjamin Mack',
	'author_email' => 'dt@dpool.net,benni@typo3.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
			'ldap_lib' => '1.0.0-1.0.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:10:{s:23:"class.tx_ldapserver.php";s:4:"0766";s:12:"ext_icon.gif";s:4:"0898";s:14:"ext_tables.php";s:4:"0ba0";s:14:"ext_tables.sql";s:4:"c545";s:22:"icon_tx_ldapserver.gif";s:4:"0898";s:16:"locallang_db.php";s:4:"a273";s:7:"tca.php";s:4:"0bd2";s:14:"doc/manual.sxw";s:4:"2bc7";s:19:"doc/wizard_form.dat";s:4:"fcd0";s:20:"doc/wizard_form.html";s:4:"46d6";}',
);

?>
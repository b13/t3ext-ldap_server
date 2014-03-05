<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver',		
		'label' => 'servername',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',	
		'delete' => 'deleted',	
		'type' => 'servertype',
		'enablecolumns' => array(		
			'disabled' => 'hidden',
		),
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ldap_server') . 'Resources/Public/Icons/tx_ldapserver.gif',
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,servername,servertype,basedn,filter_person,username,password,config,domain'
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'hidden,servername,servertype,basedn,filter_person,username,password,config,domain',
	),
	'columns' => array(
		'hidden' => array(		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'servername' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.servername',		
			'config' => array(
				'type' => 'input',	
				'size' => '30',
			)
		),
		'servertype' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.servertype',		
			'config' => array(
				'type' => 'select',
				'items' => array(
					Array('LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.servertype.I.0', 'ad'),
					Array('LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.servertype.I.1', 'nt'),
					Array('LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.servertype.I.2', 'nds'),
					Array('LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.servertype.I.3', 'x500'),
				),
				'size' => 1,	
				'maxitems' => 1,
				'default' => 'ad',
			)
		),
		'be' => array(		
			'exclude' => 1,
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.be',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'protocol' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.protocol',		
			'config' => array(
				'type' => 'select',
				'items' => array(
					Array('2', '2'),
					Array('3', '3'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'basedn' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.basedn',		
			'config' => array(
				'type' => 'input',	
				'size' => '30',
			)
		),
		'filter_person' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.filter_person',		
			'config' => array(
				'type' => 'input',	
				'size' => '40',
			)
		),
		'username' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.username',		
			'config' => array(
				'type' => 'input',	
				'size' => '30',
			)
		),
		'password' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.password',		
			'config' => array(
				'type' => 'input',	
				'size' => '30',
				'eval' => 'password',
			)
		),
		'config' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.config',		
			'config' => array(
				'type' => 'text',
				'cols' => '30',	
				'rows' => '5',
				'wrap' => 'OFF'
			)
		),
		'domain' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.domain',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'charset' => array(		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ldap_server/Resources/Private/Language/db.xml:tx_ldapserver.charset',
			'config' => array(
				'type' => 'input',	
				'size' => '30',
			)
		),
	),
	'types' => array(
		'ad' => Array('showitem' => 'hidden;;1;;1-1-1, be, servername, servertype, protocol, basedn, filter_person, charset, domain, username, password, config'),
		'nt' => Array('showitem' => 'hidden;;1;;1-1-1, be, servername, servertype, protocol, basedn, filter_person, charset, domain, username, password, config'),
		'nds' => Array('showitem' => 'hidden;;1;;1-1-1, be, servername, servertype, protocol, basedn, filter_person, charset, username, password, config'),
		'x500' => Array('showitem' => 'hidden;;1;;1-1-1, be, servername, servertype, protocol, basedn, filter_person, charset, username, password, config')
	),
	'palettes' => array(
		'1' => Array('showitem' => '')
	)
);
<?php

namespace B13\LdapServer;

/***************************************************************
*  Copyright notice
*
*  (c) 2004-2005 Daniel Thomas (dt@dpool.net)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * @author	Daniel Thomas <dt@dpool.net>
 */

class Server {
	protected $filter_person = '';
	protected $remoteCharset = '';
	protected $localCharset = '';

	
	/**
	 * Get setup data for the LDAP-Server we want to connect to
	 *
	 * @return	array 	with LDAP-server details
	 */	
	public function initLDAP($folderIDs = '', $serverIDs = '', $logintype = '') {

		// we need to have connection to the database
		if (!is_object($GLOBALS['TYPO3_DB'])) {
			return FALSE;
		}

			// define the central LDAP connectivity object
		$GLOBALS['LDAP_CONNECT'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('B13\\LdapLib\\Connector');
		$GLOBALS['LDAP_CONNECT']->cid = FALSE;

			// query the db for records
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_ldapserver',
			'NOT hidden AND NOT deleted' . $this->getAddWhereClause($folderIDs, $serverIDs, $logintype),
			'',
			'sorting'
		);
		
		if ($res) {
				// with the query result we try to connect to one of the records
				// the first server getting a connection will return the server data as valid record
				// and will have initialed a bind connection to the LDAP server we use from there
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$GLOBALS['LDAP_CONNECT']->LDAP(
						$row['username'],
						$row['password'],
						$row['servername'],
						$row['basedn'],
						'',
						$row['protocol']
				);
				if ($GLOBALS['LDAP_CONNECT']->bid) {
					$this->initCharset($row['charset']);
					return $row;
				}
			}
		}
	}
	
	/**
	 * gets the username for bind to the respective type of LDAP server
	 * 
	 * Active Directory needs user: username@domain
	 * NT Server needs user: domain//username
	 * Novell eDirectory needs user: full DN
	 * x500 need user: full DN
	 * 
	 * @param	array	$user: user record to be transformed
	 * @param	array	$server: details of the server
	 * @return	string 	transformed username
	 */
	public function getUsernameForAuth($user, $server) {
		$username = FALSE;
		
		if (($server['servertype'] == 'ad') && ($server['domain'])) {
			$username = trim($user['username']) . '@' . $server['domain'];
		}
		if (($server['servertype'] == 'nt') && ($server['domain'])) {
			$username = $server['domain'] . '//' . trim($user['username']);
		}
		if ($server['servertype'] == 'nds') {
			$username = $user['dn'];
		}
		if ($server['servertype'] == 'x500') {
			$username = $user['dn'];
		}

		return $username;
	}
	
	/**
	 * This function is used to write the data of a LDAP user record into the TYPO3 database
	 * 
	 * 
	 * @param	array	$user: record to be saved
	 * @param	array	$conf: configuration for the record
	 * @param	INT		$pid: where to insert the record
	 * @return	void
	 */
	public function transferData($data, $conf, $pid, $mode = '') {
		$ok = FALSE;

		// if there is a pid defined in the TS take that
		// else the value is taken from the server records pid
		if ($conf['pid']) {
			if ($conf['pid'] == 'root') {
				$pid = '0';
			} else {
				$pid = $conf['pid'];
			}
		}
		
			// now get the values mapped
		foreach ($conf['fields.'] as $k => $v) {
			if ($v == 'MAP_OBJECT') {
					// make live easier and code readable
				$fieldConf = $conf['fields.'][$k.'.'];
				
					// if there is data in the attribute in question
				if ($data[$fieldConf['attribute']]) {
				
						// user function for data processing - set a standard here
						// configuration array for user function
					$userFunc = $fieldConf['userFunc'] ? $fieldConf['userFunc'] : 'tx_ldapserver->getSingleValue';
					$userFuncConf = $fieldConf['userFunc.'];
					$userFuncConf['value'] = $data[$fieldConf['attribute']];
					
					$tmp = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunc,$userFuncConf,$this);
					$toDB[$k] = $this->csObj->conv($tmp, $this->remoteCharset, $this->localCharset);
				} elseif ($special = $fieldConf['special']) {
						// if the special attribute is selected
						// else do userFunc processing
					if ($special == 'DN') {
						$tmp = $data['dn'];
						$toDB[$k] = $this->csObj->conv($tmp, $this->remoteCharset, $this->localCharset);
					}
				}
			}
		}
		
		// TYPO3 allows a backendlogin even if no groupmembership is set
		// That would lead to every LDAP user being able to access the TYPO3 Backend - even though no
		if ($conf['requireGroupMemberShip'] && !$toDB['usergroup']) {
			return $ok;
		}

		// either insert or update the data into database
		if ($uid = $this->recordExists($conf['uniqueField'], $toDB[$conf['uniqueField']], $pid, $conf['table'])) {
			if ($mode == 'simulate') {
				$toDB['uid'] = $uid;
				$toDB['__STATUS__'] = 'updated';
				return $toDB;
			} else {
				$tmp = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					$conf['table'],
					'uid=' . intval($uid),
					$toDB
				);
				if ($tmp) {
					$toDB['uid'] = $uid;
					$toDB['__STATUS__'] = 'updated';
					return $toDB;
				}
			}
		} else {
			$toDB['pid'] = $pid;
			if ($mode == 'simulate') {
				$toDB['uid'] = '__NEW__';
				$toDB['__STATUS__'] = 'new';
				return $toDB;
			} else {
				$toDB['pid'] = $pid;
				$GLOBALS['TYPO3_DB']->debugOutput = TRUE;
				$tmp = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
					$conf['table'],
					$toDB
				);
				if ($tmp) {
					$toDB['uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
					$toDB['__STATUS__'] = 'new';
					return $toDB;
				}
			}
		}
		return $ok;
	}
	
/*
	STUFF FOR USE IN CONFIGURATION
*/
	
	/**
	 * This function is used to return the standard LDAP attibute single value
	 * 
	 * 
	 * @param	array	$data: LDAP data array for attribute
	 * @param	array	$conf: optional configuration
	 * @return	string 	value for inserting into db
	 */
	public function getSingleValue($conf, &$pObj) {
		return $conf['value'][0];
	}
	
	/**
	 * This function is used to set a default string value for the field
	 * 
	 * 
	 * @param	array	$data: LDAP data array for attribute
	 * @param	array	$conf: optional configuration
	 * @return	string 	value for inserting into db
	 */
	public function setDefaultValue($conf, &$pObj) {
		return $conf['defaultValue'];
	}
	
	/**
	 * This function is used to return a series of fe_group uids on basis of the membership of the user
	 * It will take the value input and search the LDAP tree in order to find a group object which links to the user objects value.
	 * 
	 * 
	 * @param	array	$data: LDAP data array for attribute
	 * @param	array	$conf: optional configuration
	 * @return	string 	value for inserting into db
	 */

	public function getStaticGroups($conf, &$pObj) {
		if ($conf['value']) {
				// we can either search for a standard LDAP value which is an array.
				// in that case we select the first value of the array as the string we want to look for
				// if $conf['value'] is not an array this means that we are looking for the DN of the user
			if (is_array($conf['value'])) {
				$conf['value'] = $conf['value'][0];
			}
			
			$uids = array();
			$this->initCharset();
			$filter = str_replace('###IDENT###', $this->escapeStringForLDAPfilter($conf['value']), $conf['filter']);
			$table = $conf['table'];
				// we set the pid value to zero if the conf value is root
			$pid = $conf['pid'] == 'root' ? '0' : $conf['pid'];
			$identField = $conf['identField'];
			
			$GLOBALS['LDAP_CONNECT']->search($filter);
				
			while ($data = $GLOBALS['LDAP_CONNECT']->fetch()) {
					// now we have a record which links to the user
					// we now need to find that attribute of the record which identifies the record in TYPO3 so that we
					// can transfer the membership. If the administrator has choosen the DN to identify the grouprecords in TYPO3
					// we have the special case DN. Otherwise we assume that a standard singleValue field is named by $conf['identFieldAtt']
				if ($conf['identFieldAtt'] == 'DN') {
					$v = $GLOBALS['LDAP_CONNECT']->getDN();
				} else {
					$v = $data[$conf['identFieldAtt']][0];
				}
				
				$v = $this->csObj->conv($v, $this->remoteCharset, $this->localCharset);
				if ($uid = $this->recordExists($identField, $v, $pid, $table, ' AND NOT hidden')) {
					$uids[] = $uid;
				}
			}
			return implode(',', $uids);
		}
	}
	
	/**
	 * This function is used to return a series of group uids on basis of the membership of the user.
	 * Use this function if your groupmembership is stored in the user record.
	 * 
	 * 
	 * @param	array	$data: LDAP data array for attribute
	 * @param	array	$conf: optional configuration
	 * @return	string 	value for inserting into db
	 */
	public function getDynamicGroups($conf, &$pObj) {
		if (is_array($conf['value'])) {
			$this->initCharset();
			$table = $conf['table'];
			$pid = $conf['pid'] == 'root' ? '0' : $conf['pid'];
			$identField = $conf['identField'];
			
			$uids = array();
			reset($conf['value']);
			next($conf['value']);	// count key
			while(list(,$v) = each($conf['value'])) {
				$v = $this->csObj->conv($v, $this->remoteCharset, $this->localCharset);
				if($uid = $this->recordExists($identField, $v, $pid, $table, ' AND NOT hidden')) {
					$uids[] = $uid;
				}
			}
			return implode(',', $uids);
		}
	}
	
	/**
	 * Wrapper function to make configuration easier.
	 * Sets a standard fe_group table and calls internal function getDynamicGroups()
	 * 
	 * 
	 * @param	array	$data: LDAP data array for attribute
	 * @param	array	$conf: optional configuration
	 * @return	string 	value for inserting into db
	 */
	public function getFEGroups($conf, &$pObj) {
		$conf['table'] = 'fe_groups';
		$conf['identField'] = $conf['identField'] ? $conf['identField'] : 'tx_ldapserver_dn';
		return $this->getDynamicGroups($conf, $pObj);
	}

	/**
	 * Wrapper function to make configuration easier.
	 * Sets a standard be_group table and pid-value to root and calls internal function getDynamicGroups()
	 * 
	 * 
	 * @param	array	$data: LDAP data array for attribute
	 * @param	array	$conf: optional configuration
	 * @return	string 	value for inserting into db
	 */
	public function getBEGroups($conf, &$pObj) {
		$conf['table'] = 'be_groups';
		$conf['identField'] = $conf['identField'] ? $conf['identField'] : 'tx_ldapserver_dn';
		$conf['pid'] = 'root';
		return $this->getDynamicGroups($conf, $pObj);
	}
	
	/**
	 * Function used to retrieve groupmembership both based on infos in the record and infos in the group
	 * 
	 * 
	 * @param	array	$data: LDAP data array for attribute
	 * @param	array	$conf: optional configuration
	 * @return	string 	value for inserting into db
	 */
	public function getStaticAndDynamicGroups($conf, &$pObj) {
		$table = $conf['table'];
		$pid = $conf['pid'] == 'root' ? '0' : $conf['pid'];
			// if $conf['value'] is an array we transform it into a string in order to use it in a search
		if (is_array($conf['value'])) {
			$conf['value'] = $conf['value'][0];
		}			
	
		$this->initCharset();
		
			// First we retrieve the user record and check for the dynamic group membership
			// This is done by selecting the record from the LDAP server and then
			// calling the standard getDynamicGroup function after setting some configuration values
		$filter = str_replace('###IDENT###', $this->escapeStringForLDAPfilter($conf['value']), $conf['dynamic.']['filter']);
		$GLOBALS['LDAP_CONNECT']->search($filter);
		while ($user = $GLOBALS['LDAP_CONNECT']->fetch()) {
			$dn = $GLOBALS['LDAP_CONNECT']->getDN();
			$lConf = $conf;
			$lConf['value'] = $user[$lConf['dynamic.']['identAtt']];
			$lConf['identField'] = $conf['dynamic.']['identField'];
			$uids = $this->getDynamicGroups($lConf, $pObj);
		}
		
			// then we get infos from static groups
		$conf['filter'] = $conf['static.']['filter'];
		$conf['identFieldAtt'] = $conf['static.']['identFieldAtt'];
		$conf['identField'] = $conf['static.']['identField'];
		$conf['value'] = $dn;
		$uids .= ','.$this->getStaticGroups($conf, $pObj);
		
		return $uids;
	}



/*
	INTERNAL STUFF
*/
	
	/**
	 * This function is used to return the standard LDAP attibute single value
	 * http://msdn.microsoft.com/library/default.asp?url=/library/en-us/adsi/adsi/search_filter_syntax.asp
	 * 
	 * @param	string	$str: string
	 * @return	string 	
	 */
	protected function escapeStringForLDAPfilter($str) {
		$str = str_replace('(','\28',$str);
		$str = str_replace(')','\29',$str);
		$str = str_replace('/','\5c',$str);
		$str = str_replace('/','\2f',$str);
		return $str;
	}
	
	/**
	 * This function is used to parse the config information of the server record into an array
	 * Define the type of konfiguration you want to have
	 * Specify a table if you want only this configuration
	 * 
	 * @param	string	$config: TS configuration
	 * @param	string	$type: LDAP_SYNC, LDAP_AUTH ... (ALL returns the whole configuration)
	 * @param	string	$table: if only a specific configuration should be given back
	 * @return	array 	multidimensional TS array
	 */
	public function getConf($config, $type, $table = '') {
		$parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
		$parser->parse($config);
		
			// after the TS is parsed we delete all konfigurations which do not fit the type AND
			// which are not enabled
		foreach ($parser->setup as $k => $v) {
			if (!strpos($k,'.')) {
					// if the value of the key matches the type parameter AND
					// the enable flag is set
				if (($parser->setup[$k] == $type) && ($parser->setup[$k.'.']['enable'] == '1')) {
						// if the table fits the asked for tableparameter then we give it back
					if ($parser->setup[$k.'.']['table'] == $table) {
						$conf = $parser->setup[$k.'.'];
					}
				} else {
					unset($parser->setup[$k]);
					unset($parser->setup[$k.'.']);
				}
			}
		}
		
			// if we want all configurations we can get them here
		if (!$table) {
			$conf = $parser->setup;
		}
			// if we want all configurations we can get them here
		if ($type == 'ALL') {
			$conf = $parser->setup;
		}
		
		return $conf;
	}
	
	/**
	 * This function checks whether the incoming LDAP data belongs to an entity already in TYPO3
	 * 
	 * 
	 * @param	string	$field: Name of the unique databasefield
	 * @param	string	$value: Value against which to check existance
	 * @param	INT		$pid: sysfolder uid in which to check
	 * @param	string	$table: table in which to check
	 * @return	boolean
	 */
	protected function recordExists($field, $value, $pid, $table, $where='') {
		$ok = FALSE;
		
		$value = $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $table);

			// query the db for record
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$field . ', pid, uid',
			$table,
			$field . '=' . $value . ' AND pid=' . intval($pid) . ' AND NOT deleted' . $where
		);
		
		if($res) {
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$ok = $row['uid'];
			}
		}
		return $ok;
	}
	
	/**
	 * This function is used to get an additional whereclause depending on 
	 * 
	 * 
	 * @param	string	$folderIDs: comma separated list of sysfolder uids
	 * @param	string	$serverIDs: comma separated list of server uids
	 * @return	string 	transformed username
	 */
	protected function getAddWhereClause($folderIDs, $serverIDs, $logintype) {
	
		if ($logintype == 'BE') {
			$additionalWhereClause = " AND be = '1'";
		} elseif ($serverIDs) {
			$additionalWhereClause = ' AND uid IN ('.$serverIDs.')';
		} elseif ($folderIDs) {
			$additionalWhereClause = ' AND pid IN ('.$folderIDs.')';
		} elseif ($storageFolder = $this->getSiterootStorageFolder()) {
			$additionalWhereClause = ' AND pid IN ('.$storageFolder.')';
		} else {
			$additionalWhereClause = '';
		}
		return $additionalWhereClause;
	}
	
	/**
	 * This function is used to determine the storage folder uids of the current site
	 * 		This is probably not a very nice way to do things. index_ts.php does not hold much information about the page. ID, aliases ...
	 * 
	 * @return	string 	site storage folder uid
	 */
	protected function getSiterootStorageFolder() {

		$tmp_TSFE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
			$GLOBALS['TYPO3_CONF_VARS'],
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'),
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type'),
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('no_cache'),
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cHash'),
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('jumpurl'),
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('MP'),
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RDCT')
		);
	
		if (is_object($tmp_TSFE)) {
			$tmp_TSFE->fe_user = $GLOBALS['TSFE']->fe_user;		// is needed in ->initUserGroups() called in ->fetch_the_id()
			$tmp_TSFE->fetch_the_id();
			$tmp_TSFE->getPageAndRootline();
			$res = $tmp_TSFE->getStorageSiterootPids();
			return $res['_STORAGE_PID'];
		}

		return FALSE;
	}
	
	/**
	 * This function is used to set the charsets for remoteServer and localServer
	 * 
	 * @return	void
	 */
	protected function initCharset($charset = FALSE) {

		// initialize the Charset object
		// and the vars remoteCharset and localCharset
		if (isset($GLOBALS['TSFE'])) {
			$this->csObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			$this->csObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
		}

		$this->remoteCharset = $this->csObj->parse_charset($charset ? $charset : 'utf-8');
		$this->localCharset = $this->csObj->parse_charset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : 'utf-8');
	}
}
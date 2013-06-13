<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2003 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2004-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 *
 * Part of the agency (Agency Registration) extension.
 *
 * Front End creating/editing/deleting records authenticated by fe_user login.
 * A variant restricted to front end user self-registration and profile maintenance, with a number of enhancements (see the manual).
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
class tx_agency_control_main {
	public $config = array();
	public $incomingData = FALSE;
	public $nc = ''; // "&no_cache=1" if you want that parameter sent.
	public $additionalUpdateFields = '';
	public $auth; // object of type tx_agency_auth
	public $control; // object of type tx_agency_control
	public $controlData; // data used for the control
	public $data; // object of type tx_agency_data
	public $urlObj;
	public $display; // object of type tx_agency_display
	public $email; // object of type tx_agency_email
	public $langObj; // object of type tx_agency_lang
	public $tca;  // object of type tx_agency_tca
	public $marker; // object of type tx_agency_marker
	public $extKey;


	public function main (
		$content,
		$conf,
		$pibaseObj,
		$theTable,
		$adminFieldList = 'username,password,name,disable,usergroup,by_invitation,tx_agency_password',
		$buttonLabelsList = '',
		$otherLabelsList = ''
	) {
		$this->extKey = $pibaseObj->extKey;
		$staticInfoObj = FALSE;

		$success = $this->init(
			$pibaseObj,
			$conf,
			$theTable,
			$adminFieldList,
			$buttonLabelsList,
			$otherLabelsList,
			$staticInfoObj
		);
		$cmd = $this->controlData->getCmd();
		$cmdKey = $this->controlData->getCmdKey();
		$theTable = $this->controlData->getTable();
		$origArray = $this->data->getOrigArray();
		$dataArray = $this->data->getDataArray();
		$templateCode = $this->data->getTemplateCode();

		if ($success !== FALSE) {
			$error_message = '';
			$content = $this->control->doProcessing(
				$pibaseObj->cObj,
				$this->langObj,
				$this->controlData,
				$staticInfoObj,
				$theTable,
				$cmd,
				$cmdKey,
				$origArray,
				$dataArray,
				$templateCode,
				$error_message
			);
		} else {
			$content = '<em>Internal error in ' . $pibaseObj->extKey . '!</em><br /> Maybe you forgot to include the basic template file under statics from extensions.';
		}
		$content = $pibaseObj->pi_wrapInBaseClass($content);
		return $content;
	}

	/**
	* Creates and initializes all component classes
	*
	* @param object pi_base object
	* @param array $conf: the configuration of the cObj
	* @param string $theTable: the table in use
	* @param string $adminFieldList: list of table fields that are considered reserved for administration purposes
	* @param string $buttonLabelsList: a list of button label names
	* @param string $otherLabelsList: a list of other label names
	* @return boolean TRUE, if initialization was successful, FALSE otherwise
	*/
	public function init (
		$pibaseObj,
		$conf,
		$theTable,
		$adminFieldList,
		$buttonLabelsList,
		$otherLabelsList,
		&$staticInfoObj
	) {
		$cObj = $pibaseObj->cObj;
		$this->tca = t3lib_div::getUserObj('&tx_agency_tca');

			// plugin initialization
		$this->conf = $conf;

		if (
			isset($conf['table.']) &&
			is_array($conf['table.']) &&
			$conf['table.']['name']
		) {
			$theTable  = $conf['table.']['name'];
		}
		$confObj = t3lib_div::getUserObj('&tx_agency_conf');
		$confObj->init($conf);

		$this->tca->init($this->extKey, $theTable);

		$tablesObj = t3lib_div::getUserObj('&tx_agency_lib_tables');
		$tablesObj->init($theTable);
		$authObj = t3lib_div::getUserObj('&tx_agency_auth');
		$authObj->init($confObj); // $config is changed
		$this->controlData = t3lib_div::getUserObj('&tx_agency_controldata');
		$this->controlData->init(
			$conf,
			$pibaseObj->prefixId,
			$this->extKey,
			$pibaseObj->piVars,
			$theTable
		);

		if ($this->extKey != AGENCY_EXT) {

					// Static Methods for Extensions for fetching the texts of agency
				tx_div2007_alpha5::loadLL_fh002(
					$pibaseObj,
					'EXT:' . AGENCY_EXT . '/pi/locallang.xml',
					FALSE
				);
		} // otherwise the labels from agency need not be included, because this has been done in

		if (t3lib_extMgm::isLoaded(STATIC_INFO_TABLES_EXT)) {


				// Initialise static info library
			if (class_exists('SJBR\\StaticInfoTables\\PiBaseApi')) {
				$staticInfoObj = t3lib_div::getUserObj('&SJBR\\StaticInfoTables\\PiBaseApi');
			} else {
				$staticInfoObj = t3lib_div::getUserObj('&tx_staticinfotables_pi1');
			}

			if (
				is_object($staticInfoObj) &&
				(
					!method_exists($staticInfoObj, 'needsInit') ||
					$staticInfoObj->needsInit()
				)
			) {
				$staticInfoObj->init();
			}
		}

		$this->langObj = t3lib_div::getUserObj('&tx_agency_lang');
		$this->urlObj = t3lib_div::getUserObj('&tx_agency_url');
		$this->data = t3lib_div::getUserObj('&tx_agency_data');
		$this->marker = t3lib_div::getUserObj('&tx_agency_marker');
		$this->display = t3lib_div::getUserObj('&tx_agency_display');
		$this->setfixedObj = t3lib_div::getUserObj('&tx_agency_setfixed');
		$this->email = t3lib_div::getUserObj('&tx_agency_email');
		$this->control = t3lib_div::getUserObj('&tx_agency_control');

		$this->urlObj->init(
			$this->controlData,
			$cObj
		);

		$this->langObj->init(
			$pibaseObj,
			$cObj,
			$conf,
			$pibaseObj->scriptRelPath,
			$this->extKey
		);
		$success = $this->langObj->loadLL();

		if ($success !== FALSE) {

			$this->control->init(
				$this->langObj,
				$cObj,
				$this->controlData,
				$this->display,
				$this->marker,
				$this->email,
				$this->tca,
				$this->setfixedObj,
				$this->urlObj
			);

			$this->data->init(
				$cObj,
				$conf,
				$this->langObj,
				$this->tca,
				$this->control,
				$theTable,
				$this->controlData,
				$staticInfoObj
			);

			$this->control->init2( // only here the $conf is changed
				$confObj,
				$staticInfoObj,
				$theTable,
				$this->controlData,
				$this->data,
				$adminFieldList
			);

			$uid = $this->data->getRecUid();

			$this->marker->init(
				$confObj,
				$this->data,
				$this->tca,
				$this->langObj,
				$this->controlData,
				$this->urlObj,
				$staticInfoObj,
				$uid,
				$this->controlData->readToken()
			);

			if ($buttonLabelsList != '') {
				$this->marker->setButtonLabelsList($buttonLabelsList);
			}

			if ($otherLabelsList != '') {
				$this->marker->addOtherLabelsList($otherLabelsList);
			}
		}

		return $success;
	}	// init
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/control/class.tx_agency_control_main.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/control/class.tx_agency_control_main.php']);
}
?>
<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @author		Stanislas Rolland <stanislas.rolland(arobas)sjbr.ca>
 *
 * @package 	TYPO3
 * @subpackage agency
 * @version		$Id$
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_directmail_select_categories
 *   67:     function get_localized_categories($params)
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Localize categories in backend forms
 *
 */
class tx_agency_select_dmcategories {
	public $sys_language_uid = 0;
	public $collate_locale = 'C';

	/**
	 * Get the localization of the select field items (right-hand part of form)
	 * Referenced by TCA
	 *
	 * @param	array		$params: array of searched translation
	 * @return	void		...
	 */
	public function get_localized_categories (&$params) {
		global $TCA, $TYPO3_DB, $LANG;

/*
		$params['items'] = &$items;
		$params['config'] = $config;
		$params['TSconfig'] = $iArray;
		$params['table'] = $table;
		$params['row'] = $row;
		$params['field'] = $field;
*/

		$items = $params['items'];
		$config = $params['config'];
		$table = $config['itemsProcFunc_config']['table'];

			// initialize backend user language
		if ($LANG->lang && t3lib_extMgm::isLoaded('static_info_tables')) {
			$res = $TYPO3_DB->exec_SELECTquery(
				//'sys_language.uid,static_languages.lg_collate_locale',
				'sys_language.uid',
				'sys_language LEFT JOIN static_languages ON sys_language.static_lang_isocode=static_languages.uid',
				'static_languages.lg_typo3=' . $TYPO3_DB->fullQuoteStr($LANG->lang, 'static_languages') .
					t3lib_pageSelect::enableFields('sys_language') .
					t3lib_pageSelect::enableFields('static_languages')
				);
			while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				$this->sys_language_uid = $row['uid'];
				$this->collate_locale = $row['lg_collate_locale'];
			}
		}

		if ($this->sys_language_uid && isset($items) && is_array($items)) {
			foreach($items as $k => $item) {
				$res = $TYPO3_DB->exec_SELECTquery(
					'*',
					$table,
					'uid=' . intval($item[1])
					);
				while($rowCat = $TYPO3_DB->sql_fetch_assoc($res)) {
					$localizedRowCat =
						tx_agency_dmstatic::getRecordOverlay(
							$table,
							$rowCat,
							$this->sys_language_uid,
							''
						);

					if($localizedRowCat) {
						$params['items'][$k][0] = $localizedRowCat['category'];
					}
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/scripts/class.tx_agency_select_dmcategories.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/' . AGENCY_EXT . '/scripts/class.tx_agency_select_dmcategories.php']);
}

?>
<?php

namespace CReifenscheid\CtypeManager\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use function array_key_exists;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 C. Reifenscheid
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Class GeneralUtility
 *
 * @package \CReifenscheid\CtypeManager\Utility\
 */
class GeneralUtility
{
    /**
     * Returns the rootline for the given uid
     *
     * @param int $uid
     *
     * @return array
     */
    public static function getRootline(int $uid) : array
    {
        $rootlineUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(RootlineUtility::class, $uid);

        return $rootlineUtility->get();
    }

    /**
     * Returns page information
     *
     * @param int $pageUid
     *
     * @return array
     */
    public static function getPage(int $pageUid) : array
    {
        $pageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PageRepository::class);

        return $pageRepository->getPage($pageUid);
    }

    /**
     * Returns all configured ctypes
     *
     * @return array
     */
    public static function getTcaCtypes() : array
    {
        return $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
    }

    /**
     * Returns all configured ctype groups
     *
     * @return array
     */
    public static function getTcaCtypeGroups() : array
    {
        return $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups'];
    }

    /**
     * Resolves pageTSConfig to get kept and removed ctypes and available list_types
     *
     * @param int $pageId
     *
     * @return array
     */
    public static function resolvePageTSConfig(int $pageId) : array
    {
        $result = [];

        $pageTSconfig = \TYPO3\CMS\Core\Utility\GeneralUtility::removeDotsFromTS(BackendUtility::getPagesTSconfig($pageId));

        // check for TCEFORM -> tt_content -> CType
        self::getCTypeConfiguration($result, $pageTSconfig);

        // check for mod -> wizards -> newContentElement -> wizardItems
        self::getListTypeConfiguration($result, $pageTSconfig);

        DebuggerUtility::var_dump($result, __CLASS__ . ':' . __FUNCTION__ . '::' . __LINE__);

        return $result;
    }

    /**
     * Returns the ctypes configured to keep
     *
     * @param array $configuration
     *
     * @return array|null
     */
    public static function getKeptCTypes(array $configuration) : ?array
    {
        return self::getCTypes($configuration, 'keep');
    }

    /**
     * Returns the ctypes configured to be removed
     *
     * @param array $configuration
     *
     * @return array|null
     */
    public static function getRemovedCTypes(array $configuration) : ?array
    {
        return self::getCTypes($configuration, 'remove');
    }

    /**
     * Returns the ctypes configured
     *
     * @param array  $configuration
     * @param string $key
     *
     * @return array|null
     */
    private static function getCTypes(array $configuration, string $key) : ?array
    {
        // check for items to keep
        if (array_key_exists($key, $configuration) && !empty($configuration[$key])) {
            return $configuration[$key];
        }

        return null;
    }

    /**
     * Returns the located label, if it's locatable
     *
     * @param string $stringToLocate
     *
     * @return string
     */
    public static function locate(string $stringToLocate) : string
    {
        return str_starts_with($stringToLocate, 'LLL:') ? LocalizationUtility::translate($stringToLocate) : $stringToLocate;
    }

    /**
     * Returns the located label of the given CType
     *
     * @param string $requestedIdentifier
     *
     * @return string|null
     */
    public static function getCTypeLabel(string $requestedIdentifier) : ?string
    {
        foreach (self::getTcaCtypes() as $ctype) {
            [$label, $identifier, , $group] = $ctype;

            if ($identifier === $requestedIdentifier) {
                return self::locate($label);
            }
        }

        return null;
    }

    /**
     * Get CType configuration from TCEFORM tsconfig
     *
     * @param array $result
     * @param array $pageTSconfig
     *
     * @return void
     */
    private static function getCTypeConfiguration(array &$result, array $pageTSconfig) : void
    {
        $ctypeConfiguration = self::getArrayKeyValue($pageTSconfig, 'TCEFORM.tt_content.CType');
        if ($ctypeConfiguration !== false) {
            // check for items to keep
            if (array_key_exists('keepItems', $ctypeConfiguration) && !empty($ctypeConfiguration['keepItems'])) {
                $result['keep'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $ctypeConfiguration['keepItems']);
            }

            // check for items to remove
            if (array_key_exists('removeItems', $ctypeConfiguration) && !empty($ctypeConfiguration['removeItems'])) {
                $result['remove'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $ctypeConfiguration['removeItems']);
            }
        }
    }

    /**
     * Get list_type configuration from mod wizard configuration
     *
     * @param array $result
     * @param array $pageTSconfig
     *
     * @return void
     */
    private static function getListTypeConfiguration(array &$result, array $pageTSconfig) : void
    {
        $wizardGroups = self::getArrayKeyValue($pageTSconfig, 'mod.wizards.newContentElement.wizardItems');
        if ($wizardGroups !== false) {

            $listTypes = [];

            foreach ($wizardGroups as $groupName => $groupConfiguration) {

                // if "show" is existing and not *
                if (array_key_exists('show', $groupConfiguration) && !empty($groupConfiguration['show']) && $groupConfiguration['show'] !== '*') {

                    // loop through every configured plugin to show
                    foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $groupConfiguration['show']) as $identifier) {

                        $elementConfiguration = $groupConfiguration['elements'][$identifier];

                        // if "list_type" definition exists within "tt_content_defValues"
                        $configuredListType = self::getArrayKeyValue($elementConfiguration, 'tt_content_defValues.list_type');
                        if (!empty($configuredListType)) {

                            // build list type information
                            $listType = [
                                'identifier' => $identifier
                            ];

                            if (array_key_exists('title', $elementConfiguration) && !empty($elementConfiguration['title'])) {
                                $listType['label'] = self::locate($elementConfiguration['title']);
                            }

                            $listTypes[$configuredListType] = $listType;
                        }
                    }
                } else {
                    if (array_key_exists('show', $groupConfiguration) && $groupConfiguration['show'] === '*') {
                        // 2. if show == '*' -> loop through every element -> tt_content_defValues -> is existing list_type
                        if (array_key_exists('elements', $groupConfiguration) && !empty($groupConfiguration['elements'])) {
                            foreach ($groupConfiguration['elements'] as $pluginIdentifier => $pluginConfiguration) {

                            }
                        }
                    }
                }
            }

            if (!empty($listTypes)) {
                $result['listTypes'] = $listTypes;
            }

//            DebuggerUtility::var_dump($wizardGroups, __CLASS__ . ':' . __FUNCTION__ . '::' . __LINE__);
        }
    }

    /**
     * Returns the value of a key within an array
     *
     * @param array  $array
     * @param string $keyChain - dot separated list of keys to check, last is checked for value, e.g. tt_content.columns.sys_language_uid.label
     *
     * @return mixed
     */
    public static function getArrayKeyValue(array $array, string $keyChain)
	{
		$keys = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $keyChain);

        if (!empty($keys) && array_key_exists($keys[0], $array)
      ) {
      	    $keyValue = $array[$keys[0]];
         
			if (is_array($keyValue)) {
         	array_shift($keys);
         	    $_keyChain = implode('.', $keys);
         	    if (empty($keys)) {
         		    return $keyValue;
         	    }
         	
				return self::getArrayKeyValue($keyValue, $_keyChain);
         } else {
         	    return $keyValue;
         	}
		}
		
		return false;
	}
}

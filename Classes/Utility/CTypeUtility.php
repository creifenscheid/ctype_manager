<?php

namespace CReifenscheid\CtypeManager\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
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
 * Class CTypeUtility
 *
 * @package \CReifenscheid\CtypeManager\Utility\
 */
class CTypeUtility
{
    /**
     * Returns all configured ctypes
     *
     * @return array
     */
    public static function getItems() : array
    {
        return $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
    }

    /**
     * Returns all configured ctype groups
     *
     * @return array
     */
    public static function getGroups() : array
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
        $ctypeConfiguration = GeneralUtility::getArrayKeyValue($pageTSconfig, 'TCEFORM.tt_content.CType');
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
        $ctypeConfiguration = GeneralUtility::getArrayKeyValue($configuration, $key);
        if ($ctypeConfiguration !== false) {
            return $ctypeConfiguration;
        }

        return null;
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
        foreach (self::getItems() as $ctype) {
            [$label, $identifier, , $group] = $ctype;

            if ($identifier === $requestedIdentifier) {
                return GeneralUtility::locate($label);
            }
        }

        return null;
    }

    /**
     * Function to get the current activation state of the given ctype
     *
     * @param array  $configuration
     * @param string $identifier
     *
     * @return bool
     */
    public static function getActivationState(array $configuration, string $identifier) : bool
    {
        // define default state
        $return = true;

        // if the current ctype is listed in removeItems - it's not active
        if (array_key_exists('remove', $configuration) && in_array($identifier, $configuration['remove'], true)) {
            $return = false;
        }

        // if the current ctype is not listed in keepItems - it's not active
        if (array_key_exists('keep', $configuration) && !in_array($identifier, $configuration['keep'], true)) {
            $return = false;
        }

        // if no keepItems configuration exists or the current ctype is listed in the configuration - it's active
        return $return;
    }
}

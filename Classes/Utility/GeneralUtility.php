<?php

namespace CReifenscheid\CtypeManager\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use function array_key_exists;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2022 Christian Reifenscheid
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
 * @package \CReifenscheid\CtypeManager\Utility
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
     * Returns uid of root page
     *
     * @param int $pageUid
     *
     * @return int
     */
    public static function getRootPageId(int $pageUid) : int
    {
        $rootline = self::getRootline($pageUid);
        $rootpage = end($rootline);

        return $rootpage['uid'];
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
        return StringUtility::beginsWith($stringToLocate, 'LLL:') ? LocalizationUtility::translate($stringToLocate) : $stringToLocate;
    }

    /**
     * Returns the value of a key within an array
     *
     * @param array  $array
     * @param string $keyChain - dot separated list of keys to check, last is checked for value, e.g. tt_content.columns.sys_language_uid.label
     *
     * @return array|mixed|null
     */
    public static function getArrayKeyValue(array $array, string $keyChain)
    {
        $keys = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $keyChain);

        if (!empty($keys) && array_key_exists($keys[0], $array)) {
            $keyValue = $array[$keys[0]];

            if (is_array($keyValue)) {
                array_shift($keys);
                if (empty($keys)) {
                    return $keyValue;
                }

                return self::getArrayKeyValue($keyValue, implode('.', $keys));
            }

            return $keyValue;
        }

        return null;
    }

    /**
     * Resolves pageTSConfig to get kept and removed items of the given field
     *
     * @param int    $pageId
     * @param string $field
     *
     * @return array
     */
    public static function resolvePageTSConfig(int $pageId, string $field) : array
    {
        $result = [];

        $pageTSconfig = \TYPO3\CMS\Core\Utility\GeneralUtility::removeDotsFromTS(BackendUtility::getPagesTSconfig($pageId));

        // check for TCEFORM -> tt_content -> CType
        $configuration = self::getArrayKeyValue($pageTSconfig, 'TCEFORM.tt_content.' . $field);
        if (!empty($configuration)) {
            // check for items to keep
            if (array_key_exists('keepItems', $configuration) && !empty($configuration['keepItems'])) {
                $result['keep'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $configuration['keepItems']);
            }

            // check for items to remove
            if (array_key_exists('removeItems', $configuration) && !empty($configuration['removeItems'])) {
                $result['remove'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $configuration['removeItems']);
            }
        }

        return $result;
    }

    /**
     * Function to get the current activation state of the given identifier
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

        // if the current identifier is listed in removeItems - it's not active
        if (array_key_exists('remove', $configuration) && in_array($identifier, $configuration['remove'], true)) {
            $return = false;
        }

        // if the current identifier is not listed in keepItems - it's not active
        if (array_key_exists('keep', $configuration) && !in_array($identifier, $configuration['keep'], true)) {
            $return = false;
        }

        // if no keepItems configuration exists or the current identifier is listed in the configuration - it's active
        return $return;
    }

    /**
     * Returns the items configured to keep
     *
     * @param array $configuration
     *
     * @return array|null
     */
    public static function getKeptItems(array $configuration) : ?array
    {
        return self::getItems($configuration, 'keep');
    }

    /**
     * Returns the items configured to be removed
     *
     * @param array $configuration
     *
     * @return array|null
     */
    public static function getRemovedItems(array $configuration) : ?array
    {
        return self::getItems($configuration, 'remove');
    }

    /**
     * Returns the items configured
     *
     * @param array  $configuration
     * @param string $key
     *
     * @return array|null
     */
    private static function getItems(array $configuration, string $key) : ?array
    {
        $result = self::getArrayKeyValue($configuration, $key);
        if (!empty($result)) {
            return $result;
        }

        return null;
    }

    /**
     * Returns the located label of the given identifier
     *
     * @param array  $items
     * @param string $requestedIdentifier
     *
     * @return string|null
     */
    public static function getLabel(array $items, string $requestedIdentifier) : ?string
    {
        foreach ($items as $item) {
            [$label, $identifier, , $group] = $item;

            if ($identifier === $requestedIdentifier) {
                return GeneralUtility::locate($label);
            }
        }

        return null;
    }
}

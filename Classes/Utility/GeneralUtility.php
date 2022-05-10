<?php

namespace CReifenscheid\CtypeManager\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\RootlineUtility;
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

        return false;
    }
}

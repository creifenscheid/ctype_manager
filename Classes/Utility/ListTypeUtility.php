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
 * Class ListTypeUtility
 *
 * @package \CReifenscheid\CtypeManager\Utility\
 */
class ListTypeUtility
{
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

        // check for mod -> wizards -> newContentElement -> wizardItems
        $wizardGroups = GeneralUtility::getArrayKeyValue($pageTSconfig, 'mod.wizards.newContentElement.wizardItems');
        if ($wizardGroups !== false) {

            $listTypes = [];

            foreach ($wizardGroups as $groupName => $groupConfiguration) {

                // if "show" is existing and not *
                if (array_key_exists('show', $groupConfiguration) && !empty($groupConfiguration['show']) && $groupConfiguration['show'] !== '*') {

                    // loop through every configured plugin to show
                    foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $groupConfiguration['show']) as $elementIdentifier) {
                        $elementConfiguration = $groupConfiguration['elements'][$elementIdentifier];
                        $listType = self::resolveListTypeConfiguration($elementIdentifier, $elementConfiguration);
                        if ($listType !== null) {
                            $listTypes[$listType['list_type']] = $listType;
                        }
                    }
                } else {
                    if (array_key_exists('show', $groupConfiguration) && $groupConfiguration['show'] === '*') {
                        // 2. if show == '*' -> loop through every element -> tt_content_defValues -> is existing list_type
                        if (array_key_exists('elements', $groupConfiguration) && !empty($groupConfiguration['elements'])) {
                            foreach ($groupConfiguration['elements'] as $elementIdentifier => $elementConfiguration) {
                                $listType = self::resolveListTypeConfiguration($elementIdentifier, $elementConfiguration);
                                if ($listType !== null) {
                                    $listTypes[$listType['list_type']] = $listType;
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($listTypes)) {
                $result['listTypes'] = $listTypes;
            }
        }

        return $result;
    }

    /**
     * Returns an array with the corresponding list_type configuration
     *
     * @param string $identifier
     * @param array  $configuration
     *
     * @return string[]|null
     */
    private static function resolveListTypeConfiguration(string $identifier, array $configuration) : ?array
    {
        $configuredListType = GeneralUtility::getArrayKeyValue($configuration, 'tt_content_defValues.list_type');
        if (!empty($configuredListType)) {

            // build list type information
            $listType = [
                'identifier' => $identifier,
                'list_type' => $configuredListType
            ];

            if (array_key_exists('title', $configuration) && !empty($configuration['title'])) {
                $listType['label'] = GeneralUtility::locate($configuration['title']);
            }

            return $listType;
        }

        return null;
    }
}

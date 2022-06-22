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
     * Returns all configured list_types
     *
     * @return array
     */
    public static function getItems() : array
    {
        return $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'];
    }

    /**
     * Returns all configured wizard items
     *
     * @param int $pageId
     *
     * @return array|null
     */
    public static function getWizardItems(int $pageId) : ?array
    {
        $pageTSconfig = \TYPO3\CMS\Core\Utility\GeneralUtility::removeDotsFromTS(BackendUtility::getPagesTSconfig($pageId));

        // check for mod -> wizards -> newContentElement -> wizardItems
        $wizardGroups = GeneralUtility::getArrayKeyValue($pageTSconfig, 'mod.wizards.newContentElement.wizardItems');

        if ($wizardGroups) {

            $listTypes = [];

            foreach ($wizardGroups as $groupName => $groupConfiguration) {
                $groupConfigurationElements = GeneralUtility::getArrayKeyValue($groupConfiguration, 'elements');
                if ($groupConfigurationElements) {
                    foreach ($groupConfigurationElements as $elementIdentifier => $elementConfiguration) {
                        $listType = self::resolveListTypeConfiguration($elementIdentifier, $elementConfiguration);
                        if ($listType !== null) {
                            $listType['group'] = $groupName;
                            $listTypes[$listType['list_type']] = $listType;
                        }
                    }
                }
            }

            if (!empty($listTypes)) {
                return $listTypes;
            }
        }

        return null;
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
        /**
         * @SeppToDo: Here is something wrong: it is possible that nothing is returned or elements are missing, if TSConfig is set on this page.
         */
        $configuredListType = GeneralUtility::getArrayKeyValue($configuration, 'tt_content_defValues.list_type');
        if (!empty($configuredListType)) {
            // build list type information
            $listType = [
                'identifier' => $identifier,
                'list_type' => $configuredListType,
                'group' => $configuredListType
            ];

            if (array_key_exists('title', $configuration) && !empty($configuration['title'])) {
                $listType['label'] = GeneralUtility::locate($configuration['title']);
            }

            return $listType;
        }

        return null;
    }
}

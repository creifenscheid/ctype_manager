<?php

namespace CReifenscheid\CtypeManager\Utility;

use function array_key_exists;

use TYPO3\CMS\Backend\Utility\BackendUtility;

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
 * Class ListTypeUtility
 */
class ListTypeUtility
{
    public static function getItems(): array
    {
        return $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'];
    }

    public static function getWizardItems(int $pageId): ?array
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

            if ($listTypes !== []) {
                return $listTypes;
            }
        }

        return null;
    }

    private static function resolveListTypeConfiguration(string $identifier, array $configuration): ?array
    {
        $configuredListType = GeneralUtility::getArrayKeyValue($configuration, 'tt_content_defValues.list_type');
        if (!empty($configuredListType)) {
            // build list type information
            $listType = [
                'identifier' => $identifier,
                'list_type' => $configuredListType,
            ];

            if (array_key_exists('title', $configuration) && !empty($configuration['title'])) {
                $listType['label'] = GeneralUtility::locate($configuration['title']);
            }

            return $listType;
        }

        return null;
    }
}

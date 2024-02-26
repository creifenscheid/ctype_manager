<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Utility\CTypeUtility;
use CReifenscheid\CtypeManager\Utility\GeneralUtility;
use CReifenscheid\CtypeManager\Utility\ListTypeUtility;
use Doctrine\DBAL\DBALException;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_key_exists;
use function array_unique;
use function count;
use function end;
use function implode;
use function in_array;

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
class ConfigurationController extends BaseController
{
    /**
     * Array of ctypes configured in pageTSConfig
     */
    private array $ctypeConfiguration = [];

    /**
     * Array of list_types configured in pageTSConfig
     */
    private array $listTypeConfiguration = [];

    public function indexAction(): ResponseInterface
    {
        if ($this->pageUid && $this->pageUid > 0) {
            // store all variables for the view
            $assignments = [
                'page' => GeneralUtility::getPage($this->pageUid),
                'sourceController' => $this->sourceController,
            ];

            // resolve page tsconfig for the current page
            $this->resolvePageTSConfig($this->pageUid);

            // CTYPES
            // sort CTypes by group
            $ctypes = [];
            $ctypeStates = [];
            $groupStates = [];

            foreach (CTypeUtility::getItems() as $ctype) {
                if ($this->typo3Version->getMajorVersion() < 12) {
                    $label = $ctype[0] ?? null;
                    $identifier = $ctype[1] ?? null;
                    $group = $ctype[3] ?? null;
                } else {
                    $label = $ctype['label'] ?? null;
                    $identifier = $ctype['value'] ?? null;
                    $group = $ctype['group'] ?? null;
                }

                // check group existence
                if (empty($group)) {
                    $group = 'unassigned';
                }

                // init group storage
                if (!array_key_exists($group, $ctypes)) {
                    $ctypes[$group] = [];
                }

                // set group label
                if (!array_key_exists('label', $ctypes[$group])) {
                    // get the group label from TCA group
                    if ($group === 'unassigned') {
                        $ctypes[$group]['label'] = GeneralUtility::locate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:group.unassigned');
                    } else {
                        $configuredGroups = CTypeUtility::getGroups();
                        $ctypes[$group]['label'] = array_key_exists($group, $configuredGroups) ? GeneralUtility::locate($configuredGroups[$group]) : ucfirst((string)$group);
                    }
                }

                // exclude divider items
                if ($identifier !== '--div--') {
                    $ctypeState = GeneralUtility::getActivationState($this->ctypeConfiguration, $identifier);
                    $ctypes[$group]['ctypes'][$identifier] = [
                        'label' => GeneralUtility::locate($label),
                        'active' => $ctypeState,
                    ];

                    // store ctype state to determine group state
                    $ctypeStates[$group][] = $ctypeState;
                }
            }

            // set state of group
            foreach ($ctypeStates as $groupKey => $states) {
                $groupState = $this->getMainState($states);
                $groupStates[] = $groupState;
                $ctypes[$groupKey]['state'] = $groupState;
            }

            $assignments['ctypes'] = $ctypes;
            $assignments['groupsState'] = $this->getMainState($groupStates);

            // LIST TYPES
            $listTypes = [];
            foreach (ListTypeUtility::getItems() as $listType) {
                if ($this->typo3Version->getMajorVersion() < 12) {
                    [$label, $identifier] = $listType;
                } else {
                    $label = $listType['label'] ?? null;
                    $identifier = $listType['value'] ?? null;
                }

                if (!empty($identifier)) {
                    $listTypeState = GeneralUtility::getActivationState($this->listTypeConfiguration, $identifier);
                    $listTypes[$identifier] = [
                        'label' => GeneralUtility::locate($label),
                        'active' => $listTypeState,
                    ];
                }
            }

            if ($listTypes !== []) {
                $assignments['listTypes'] = $listTypes;
            }

            $this->view->assignMultiple($assignments);
        }

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @throws StopActionException
     * @throws DBALException
     */
    public function submitAction(): ResponseInterface
    {
        // get request arguments
        $arguments = $this->request->getArguments();

        // get enabled ctypes
        $enabledCtypes = empty($arguments['ctypes']) ? [] : $arguments['ctypes'];

        // get enabled list_types
        $enabledListTypes = empty($arguments['listTypes']) ? [] : $arguments['listTypes'];

        // resolve page tsconfig for the current page
        $this->resolvePageTSConfig($this->pageUid);

        $ctypesDiffer = $this->configurationService->hasChanged(CTypeUtility::getItems(), $this->ctypeConfiguration, $enabledCtypes);
        $listTypesDiffer = $this->configurationService->hasChanged(ListTypeUtility::getItems(), $this->listTypeConfiguration, $enabledListTypes);

        // only write pageTSConfig if the submitted configuration differs from to existing one
        if ($ctypesDiffer || $listTypesDiffer) {
            // define ctype configuration
            $tsConfig[] = '### START ' . $this->configurationService::CONFIG_ID;
            $tsConfig[] = '# The following lines are set and updated by EXT:ctype_manager - do not remove or remove completely';

            // >>>> START CTYPE
            // unset existing removeItems configuration
            $tsConfig[] = 'TCEFORM.tt_content.CType.removeItems >';

            // build keep ctype configuration
            $ctypeConfiguration = 'TCEFORM.tt_content.CType.keepItems';
            $tsConfig[] = empty($enabledCtypes) ? $ctypeConfiguration . ' = none' : $ctypeConfiguration . ' = ' . implode(',', $enabledCtypes);
            // <<<< END CTYPE

            // >>>> START LIST TYPE
            // unset existing removeItems configuration
            $tsConfig[] = 'TCEFORM.tt_content.list_type.removeItems >';

            // build keep list_type configuration
            $listTypeConfiguration = 'TCEFORM.tt_content.list_type.keepItems';
            $tsConfig[] = empty($enabledListTypes) ? $listTypeConfiguration . ' = none' : $listTypeConfiguration . ' = ' . implode(',', $enabledListTypes);

            // get all available wizard items of current root
            $wizardConfiguration = ListTypeUtility::getWizardItems(GeneralUtility::getRootPageId($this->pageUid));

            // store all list types to remove from wizard for each group
            $listTypeRemovals = [];

            // loop through every wizard group
            if ($wizardConfiguration) {
                foreach ($wizardConfiguration as $wizardElement) {
                    ['identifier' => $identifier, 'list_type' => $listType, 'group' => $group, 'label' => $label] = $wizardElement;

                    // check if wizard item has a group and is not listed in enabled list types
                    if (!empty($group) && !in_array($listType, $enabledListTypes, true)) {
                        // clear wizard item configuration
                        $tsConfig[] = 'mod.wizards.newContentElement.wizardItems.' . $group . '.elements.' . $identifier . ' >';

                        // add item to removal storage
                        $listTypeRemovals[$group][] = $identifier;
                    }
                }
            }

            // adjust "show" configuration for each group, if needed
            foreach ($listTypeRemovals as $group => $listTypesToRemove) {
                $tsConfig[] = 'mod.wizards.newContentElement.wizardItems.' . $group . '.show := removeFromList(' . implode(',', $listTypesToRemove) . ')';
            }

            // <<<< END LIST TYPE

            $tsConfig[] = '### END ' . $this->configurationService::CONFIG_ID;

            $this->configurationService->writeConfiguration($this->pageUid, $tsConfig);

            // persist changes
            $this->configurationService->persist();
        }

        $messagePrefix = 'LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:configuration.message';
        $this->addFlashMessage(LocalizationUtility::translate($messagePrefix . '.bodytext'), LocalizationUtility::translate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:message.header.' . AbstractMessage::OK));

        // redirect to index
        return $this->redirect('index', $this->sourceController, 'CtypeManager', ['pageUid' => $this->pageUid]);
    }

    /**
     * Resolves pageTSConfig to get kept and removed ctypes and list_types
     */
    private function resolvePageTSConfig(int $currentPageId): void
    {
        // CTYPE
        // get content element configuration for the current page
        $ctypeConfiguration = GeneralUtility::resolvePageTSConfig($currentPageId, 'CType');

        $keptCTypes = GeneralUtility::getKeptItems($ctypeConfiguration);
        if ($keptCTypes) {
            $this->ctypeConfiguration['keep'] = $keptCTypes;
        }

        $removedCTypes = GeneralUtility::getRemovedItems($ctypeConfiguration);
        if ($removedCTypes) {
            $this->ctypeConfiguration['remove'] = $removedCTypes;
        }

        // LIST_TYPE
        // extract list_type configuration
        $listTypeConfiguration = GeneralUtility::resolvePageTSConfig($currentPageId, 'list_type');

        $keptListTypes = GeneralUtility::getKeptItems($listTypeConfiguration);
        if ($keptListTypes) {
            $this->listTypeConfiguration['keep'] = $keptListTypes;
        }

        $removedListTypes = GeneralUtility::getRemovedItems($listTypeConfiguration);
        if ($removedListTypes) {
            $this->listTypeConfiguration['remove'] = $removedListTypes;
        }
    }

    /**
     * Function to determine the state of a group
     */
    private function getMainState(array $states): bool
    {
        // remove duplicate states
        $states = array_unique($states);

        // if there are more than 1 state left (true and false) the state is false, otherwise the state of the group equals the leftover state (true or false)
        return count($states) > 1 ? false : end($states);
    }
}

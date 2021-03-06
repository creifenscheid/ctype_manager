<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Service\ConfigurationService;
use CReifenscheid\CtypeManager\Utility\CTypeUtility;
use CReifenscheid\CtypeManager\Utility\GeneralUtility;
use CReifenscheid\CtypeManager\Utility\ListTypeUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use function array_key_exists;
use function count;

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
 * Class CtypeController
 *
 * @package \CReifenscheid\CtypeManager\Controller
 */
class CtypeController extends ActionController
{
    /**
     * Configuration identifier
     */
    private const CONFIG_ID = 'ctype-manager';

    /**
     * Array of ctypes configured in pageTSConfig
     *
     * @var array
     */
    private array $ctypeConfiguration = [];

    /**
     * Array of list_types configured in pageTSConfig
     *
     * @var array
     */
    private array $listTypeConfiguration = [];

    /**
     * Index action
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function indexAction() : void
    {
        // get the current page from request
        if ($this->request->hasArgument('pageUid')) {
            $pageUid = (int)$this->request->getArgument('pageUid');
        } else {
            $pageUid = $this->request->getQueryParams()['id'];
        }

        if ($pageUid && $pageUid > 0) {

            // store all variables for the view
            $assignments = [
                'page' => GeneralUtility::getPage($pageUid)
            ];

            // resolve page tsconfig for the current page
            $this->resolvePageTSConfig($pageUid);

            // CTYPES
            // sort CTypes by group
            $ctypes = [];
            $ctypeStates = [];
            $groupStates = [];
            foreach (CTypeUtility::getItems() as $ctype) {
                [$label, $identifier, , $group] = $ctype;

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
                    $groupLabel = CTypeUtility::getGroups()[$group];

                    if ($groupLabel) {
                        $ctypes[$group]['label'] = GeneralUtility::locate($groupLabel);
                    } else {
                        if ($group === 'unassigned') {
                            $ctypes[$group]['label'] = GeneralUtility::locate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:group.unassigned');
                        } else {
                            $ctypes[$group]['label'] = ucfirst($group);
                        }
                    }
                }

                // exclude divider items
                if ($identifier !== '--div--') {
                    $ctypeState = GeneralUtility::getActivationState($this->ctypeConfiguration, $identifier);
                    $ctypes[$group]['ctypes'][$identifier] = [
                        'label' => GeneralUtility::locate($label),
                        'active' => $ctypeState
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
                [$label, $identifier] = $listType;

                if (!empty($identifier)) {
                    $listTypeState = GeneralUtility::getActivationState($this->listTypeConfiguration, $identifier);
                    $listTypes[$identifier] = [
                        'label' => GeneralUtility::locate($label),
                        'active' => $listTypeState
                    ];
                }
            }

            if (!empty($listTypes)) {
                $assignments['listTypes'] = $listTypes;
            }

            $this->view->assignMultiple($assignments);
        }
    }

    /**
     * Submit action
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function submitAction() : void
    {
        // get request arguments
        $arguments = $this->request->getArguments();

        // get the page uid to store page tsconfig in
        $pageUid = (int)$arguments['pageUid'];

        // get enabled ctypes
        $enabledCtypes = empty($arguments['ctypes']) ? [] : $arguments['ctypes'];

        // get enabled list_types
        $enabledListTypes = empty($arguments['listTypes']) ? [] : $arguments['listTypes'];

        // resolve page tsconfig for the current page
        $this->resolvePageTSConfig($pageUid);

        $ctypesDiffer = $this->configurationDiffers(CTypeUtility::getItems(), $this->ctypeConfiguration, $enabledCtypes);
        $listTypesDiffer = $this->configurationDiffers(ListTypeUtility::getItems(), $this->listTypeConfiguration, $enabledListTypes);

        // only write pageTSConfig if the submitted configuration differs from to existing one
        if ($ctypesDiffer || $listTypesDiffer) {
            // define ctype configuration
            $tsConfig[] = '### START ' . self::CONFIG_ID;
            $tsConfig[] = '# The following lines are set and updated by EXT:ctype_manager - do not remove';

            // CTYPE
            // unset existing removeItems configuration
            $tsConfig[] = 'TCEFORM.tt_content.CType.removeItems >';

            // build keep ctype configuration
            $ctypeConfiguration = 'TCEFORM.tt_content.CType.keepItems';
            $tsConfig[] = empty($enabledCtypes) ? $ctypeConfiguration . ' = none' : $ctypeConfiguration . ' = ' . implode(',', $enabledCtypes);


            // LIST_TYPE
            // unset existing removeItems configuration
            $tsConfig[] = 'TCEFORM.tt_content.list_type.removeItems >';

            // build keep list_type configuration
            $listTypeConfiguration = 'TCEFORM.tt_content.list_type.keepItems';
            $tsConfig[] = empty($enabledListTypes) ? $listTypeConfiguration . ' = none' : $listTypeConfiguration . ' = ' . implode(',', $enabledListTypes);

            // get all available wizard items of current root
            $wizardConfiguration = ListTypeUtility::getWizardItems(GeneralUtility::getRootPageId($pageUid));

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
            if (!empty($listTypeRemovals)) {
                foreach ($listTypeRemovals as $group => $listTypesToRemove) {
                    $tsConfig[] = 'mod.wizards.newContentElement.wizardItems.' . $group . '.show := removeFromList(' . implode(',', $listTypesToRemove) . ')';
                }
            }

            $tsConfig[] = '### END ' . self::CONFIG_ID;

            /** @var \CReifenscheid\CtypeManager\Service\ConfigurationService $pageTSConfigService */
            $configurationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConfigurationService::class);
            $configurationService->writeConfiguration($pageUid, $tsConfig);

            // persist changes
            $configurationService->persist();
        }

        $messagePrefix = 'LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:index.message';
        $this->addFlashMessage(LocalizationUtility::translate($messagePrefix . '.bodytext'), LocalizationUtility::translate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:message.header.' . FlashMessage::OK), FlashMessage::OK, true);

        // redirect to index
        $this->redirect('index', 'Ctype', 'CtypeManager', ['pageUid' => $pageUid]);
    }

    /**
     * Resolves pageTSConfig to get kept and removed ctypes
     *
     * @param int $currentPageId
     *
     * @return void
     */
    private function resolvePageTSConfig(int $currentPageId) : void
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
     *
     * @param array $ctypeStates
     *
     * @return bool
     */
    private function getMainState(array $states) : bool
    {
        // remove duplicate states
        $states = array_unique($states);

        // if there are more then 1 state left (true and false) the state is false, otherwise the state of the group equals the leftover state (true or false)
        return count($states) > 1 ? false : end($states);
    }

    /**
     * Function to compare set configuration vs. configuration sent via form
     *
     * @param array $available
     * @param array $configuration
     * @param array $formEnabled
     *
     * @return boolean
     */
    private function configurationDiffers(array $available, array $configuration, array $formEnabled) : bool
    {
        // store already enabled
        $alreadyEnabled = [];

        foreach ($available as $item) {
            $identifier = $item[1];

            // exclude divider and empty items
            if ((!empty($identifier) && $identifier !== '--div--') && GeneralUtility::getActivationState($configuration, $identifier)) {
                $alreadyEnabled[] = $identifier;
            }
        }

        // compare the arrays - note: the larger one has to be the first to get a correct result
        if (count($alreadyEnabled) > count($formEnabled)) {
            $result = array_diff($alreadyEnabled, $formEnabled);
        } else {
            $result = array_diff($formEnabled, $alreadyEnabled);
        }

        return !empty($result);
    }
}
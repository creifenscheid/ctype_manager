<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Service\ConfigurationService;
use CReifenscheid\CtypeManager\Utility\CTypeUtility;
use CReifenscheid\CtypeManager\Utility\ListTypeUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use function array_key_exists;
use function count;
use function in_array;

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
 * @package \CReifenscheid\CtypeManager\Controller\
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
                'page' => \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage($pageUid)
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

                // init group storage
                if (!array_key_exists($group, $ctypes)) {
                    $ctypes[$group] = [];
                }

                // set group label
                if (!array_key_exists('label', $ctypes[$group])) {
                    // get the group label from TCA group
                    $groupLabel = CTypeUtility::getGroups()[$group];
                    $ctypes[$group]['label'] = \CReifenscheid\CtypeManager\Utility\GeneralUtility::locate($groupLabel);
                }

                // exclude divider items
                if ($identifier !== '--div--') {
                    $ctypeState = CTypeUtility::getActivationState($this->ctypeConfiguration, $identifier);
                    $ctypes[$group]['ctypes'][$identifier] = [
                        'label' => \CReifenscheid\CtypeManager\Utility\GeneralUtility::locate($label),
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
            foreach (ListTypeUtility::getItems() as $item) {
                ['identifier' => $identifier, 'list_type' => $listType, 'label' => $label, 'group' => $group] = $item;

                if (!empty($listType)) {
                    $listTypes[$listType] = [
                        'label' => \CReifenscheid\CtypeManager\Utility\GeneralUtility::locate($label),
                        'identifier' => $identifier,
                        'listType' => $listType,
                        'group' => $group,
                        'state' => array_key_exists($listType, $this->listTypeConfiguration)
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

        // resolve page tsconfig for the current page
        $this->resolvePageTSConfig($pageUid);

        // only write pageTSConfig if the submitted configuration differs from to existing one
        if ($this->configurationDiffers($enabledCtypes)) {
            // define ctype configuration
            $ctypeTSConfig[] = '### START ' . self::CONFIG_ID;
            $ctypeTSConfig[] = '# The following lines are set and updated by EXT:ctype_manager - do not remove';
            // unset existing removeItems configuration
            $ctypeTSConfig[] = 'TCEFORM.tt_content.CType.removeItems >';

            // build keep ctype configuration
            $ctypeConfiguration = 'TCEFORM.tt_content.CType.keepItems';
            $ctypeTSConfig[] = empty($enabledCtypes) ? $ctypeConfiguration . ' = none' : $ctypeConfiguration . ' = ' . implode(',', $enabledCtypes);
            $ctypeTSConfig[] = '### END ' . self::CONFIG_ID;

            /** @var \CReifenscheid\CtypeManager\Service\ConfigurationService $pageTSConfigService */
            $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
            $configurationService->writeConfiguration($pageUid, $ctypeTSConfig);

            // persist changes
            $configurationService->persist();
        }

        $messagePrefix = 'LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:index.message';
        $this->addFlashMessage(LocalizationUtility::translate($messagePrefix . '.bodytext'), LocalizationUtility::translate($messagePrefix . '.header'), FlashMessage::OK, true);

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
        // get content element configuration for the current page
        $ctypeConfiguration = CTypeUtility::resolvePageTSConfig($currentPageId);

        // extract list_type configuration
        $listTypeConfiguration = ListTypeUtility::resolvePageTSConfig($currentPageId);

        if (!empty($listTypeConfiguration)) {
            $this->listTypeConfiguration = $listTypeConfiguration;
        }

        $keptCTypes = CTypeUtility::getKeptCTypes($ctypeConfiguration);
        if ($keptCTypes) {
            $this->ctypeConfiguration['keep'] = $keptCTypes;
        }

        $removedCTypes = CTypeUtility::getRemovedCTypes($ctypeConfiguration);
        if ($removedCTypes) {
            $this->ctypeConfiguration['remove'] = $removedCTypes;
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
     * @param array $formEnabledCtypes
     *
     * @return boolean
     */
    private function configurationDiffers(array $formEnabledCtypes) : bool
    {
        // store already enabled ctypes
        $alreadyEnabledCtypes = [];

        foreach (CTypeUtility::getItems() as $ctype) {
            $identifier = $ctype[1];

            // exclude divider items
            if ($identifier !== '--div--') {
                if (CTypeUtility::getActivationState($this->ctypeConfiguration, $identifier)) {
                    $alreadyEnabledCtypes[] = $identifier;
                }
            }
        }

        // compare the arrays - note: the larger one has to be the first to get a correct result
        if (count($alreadyEnabledCtypes) > count($formEnabledCtypes)) {
            $result = array_diff($alreadyEnabledCtypes, $formEnabledCtypes);
        } else {
            $result = array_diff($formEnabledCtypes, $alreadyEnabledCtypes);
        }

        return !empty($result);
    }
}

/**
 * NOTES
 * TSCONFIG:
 *
# remove from select field
TCEFORM.tt_content.list_type.removeItems >
TCEFORM.tt_content.list_type.keepItems = fnncalendar_calendar
 *
# remove from custom content element wizard
mod.wizards.newContentElement.wizardItems.fnncalendar.show := removeFromList(EventTeaser,EventList,EventDatesList)
mod.wizards.newContentElement.wizardItems.fnncalendar.EventTeaser >
mod.wizards.newContentElement.wizardItems.fnncalendar.EventList >
mod.wizards.newContentElement.wizardItems.fnncalendar.EventDatesList >
mod.wizards.newContentElement.wizardItems.plugins.show := removeFromList(news)
mod.wizards.newContentElement.wizardItems.plugins.elements.news >
 *
 */
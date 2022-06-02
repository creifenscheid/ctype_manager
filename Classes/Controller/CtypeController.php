<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Service\ConfigurationService;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
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
     * Page repository
     *
     * @var \TYPO3\CMS\Core\Domain\Repository\PageRepository
     */
    private $pageRepository;

    /**
     * Array of ctypes configured in pageTSConfig
     *
     * @var array
     */
    private array $ctypeConfiguration = [];

    /**
     * Index action
     *
     * @return void
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
            // resolve page tsconfig for the current page
            $this->resolvePageTSConfig($pageUid);

            // sort CTypes by group
            $ctypes = [];
            $ctypeStates = [];
            $groupStates = [];
            foreach (\CReifenscheid\CtypeManager\Utility\GeneralUtility::getTcaCtypes() as $ctype) {
                [$label, $identifier, $icon, $group] = $ctype;

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
                    $groupLabel = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getTcaCtypeGroups()[$group];

                    if ($groupLabel) {
                        $ctypes[$group]['label'] = \CReifenscheid\CtypeManager\Utility\GeneralUtility::locate($groupLabel);
                    } else if ($group === 'unassigned') {
                        $ctypes[$group]['label'] = \CReifenscheid\CtypeManager\Utility\GeneralUtility::locate('LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:group.unassigned');
                    } else {
                        $ctypes[$group]['label'] = ucfirst($group);
                    }
                }

                // exclude divider items
                if ($identifier !== '--div--') {
                    $ctypeState = $this->getActivationState($identifier);
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

            $this->view->assignMultiple([
                'groupsState' => $this->getMainState($groupStates),
                'ctypes' => $ctypes,
                'page' => \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage($pageUid)
            ]);
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
        // get ctype configuration for the current page
        $ctypeConfiguration = \CReifenscheid\CtypeManager\Utility\GeneralUtility::resolvePageTSConfig($currentPageId);

        $keptCTypes = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getKeptCTypes($ctypeConfiguration);
        if ($keptCTypes) {
            $this->ctypeConfiguration['keep'] = $keptCTypes;
        }

        $removedCTypes = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getRemovedCTypes($ctypeConfiguration);
        if ($removedCTypes) {
            $this->ctypeConfiguration['remove'] = $removedCTypes;
        }
    }

    /**
     * Function to get the current activation state of the given ctype
     *
     * @param string $identifier
     *
     * @return bool
     */
    private function getActivationState(string $identifier) : bool
    {
        // define default state
        $return = true;

        // if the current ctype is listed in removeItems - it's not active
        if (array_key_exists('remove', $this->ctypeConfiguration) && in_array($identifier, $this->ctypeConfiguration['remove'], true)) {
            $return = false;
        }

        // if the current ctype is not listed in keepItems - it's not active
        if (array_key_exists('keep', $this->ctypeConfiguration) && !in_array($identifier, $this->ctypeConfiguration['keep'], true)) {
            $return = false;
        }

        // if no keepItems configuration exists or the current ctype is listed in the configuration - it's active
        return $return;
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

        foreach (\CReifenscheid\CtypeManager\Utility\GeneralUtility::getTcaCtypes() as $ctype) {
            $identifier = $ctype[1];

            // exclude divider items
            if ($identifier !== '--div--') {
                if ($this->getActivationState($identifier)) {
                    $alreadyEnabledCtypes[] = $identifier;
                }
            }
        }

        // compare the arrays - note: the larger one has to be the first to get an correct result
        if (count($alreadyEnabledCtypes) > count($formEnabledCtypes)) {
            $result = array_diff($alreadyEnabledCtypes, $formEnabledCtypes);
        } else {
            $result = array_diff($formEnabledCtypes, $alreadyEnabledCtypes);
        }

        return !empty($result);
    }
}
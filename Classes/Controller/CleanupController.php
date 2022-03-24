<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Service\ConfigurationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
class CleanupController extends ActionController
{
    /**
     * Page repository
     *
     * @var \TYPO3\CMS\Core\Domain\Repository\PageRepository
     */
    private $pageRepository;

    /**
     * PageTSconfigService
     *
     * @var \CReifenscheid\CtypeManager\Service\ConfigurationService
     */
    private $configurationService;

    /**
     * Double opt-in for cleanup
     *
     * @return void
     */
    public function approvalAction() : void
    {
        // get request arguments
        $arguments = $this->request->getArguments();

        // get the page uid to store page tsconfig in
        $pageUid = (int)$arguments['pageUid'];

        $this->view->assign('pageUid', $pageUid);
    }

    /**
     * Cleanup action to remove all ctype_manager tsconfig
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function cleanupAction() : void
    {
        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        // get request arguments
        $arguments = $this->request->getArguments();
        $cleanupMode = $arguments['cleanup-mode'];
        $pageUid = (int)$arguments['pageUid'];

        // initialize rootline utility
        if ($cleanupMode === 'rootpage' || $cleanupMode === 'rootline') {
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid);
            $rootline = $rootlineUtility->get();
        }

        switch ($cleanupMode) {
            case 'page':
                $this->configurationService->removeConfiguration($pageUid);
                break;

            case 'rootline':
                foreach ($rootline as $page) {
                    $this->configurationService->removeConfiguration($page['uid']);
                }

                break;

            case 'rootpage':
                // init page resository
                $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $rootPage = end($rootline);
                $this->cleanupPageRecursively($rootPage['uid']);

                break;

            case 'all':
                // get all pages
                $tableToQuery = 'pages';
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableToQuery);
                $result = $queryBuilder
                    ->select('uid')
                    ->from($tableToQuery)
                    ->executeQuery();

                while ($row = $result->fetchAssociative()) {
                    $this->configurationService->removeConfiguration($row['uid']);
                }
                break;
        }

        $messagePrefix = 'LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:cleanup.message';
        $this->addFlashMessage(LocalizationUtility::translate($messagePrefix . '.bodytext'), LocalizationUtility::translate($messagePrefix . '.header'), FlashMessage::OK, true);

        // redirect to index
        $this->redirect('index', 'Ctype');
    }

    /**
     * Function to clean up a page and its children
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function cleanupPageRecursively(int $pageUid)
    {
        // cleanup current page
        $this->configurationService->removeConfiguration($pageUid);

        // get children of page
        $children = $this->pageRepository->getMenu($pageUid);
        if (!empty($children)) {
            foreach ($children as $child) {
                $this->cleanupPageRecursively($child['uid']);
            }
        }
    }
}
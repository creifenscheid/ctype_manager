<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Service\ConfigurationService;
use Doctrine\DBAL\DBALException;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
 * Class CleanupController
 *
 * @package \CReifenscheid\CtypeManager\Controller
 */
class CleanupController extends BaseController
{
    /**
     * L10n base
     *
     * @var string
     */
    private const L10N = 'LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:';

    private PageRepository $pageRepository;

    private ConfigurationService $configurationService;

    public function indexAction() : ResponseInterface
    {
        $this->view->assign('page', \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage($this->pageUid));

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Double opt-in for cleanup
     *
     * @throws StopActionException
     */
    public function approvalAction() : ResponseInterface
    {
        if ($this->checkRequestArguments()) {
            $assignments = [];

            // get request arguments
            $arguments = $this->request->getArguments();
            $assignments['cleanupMode'] = $arguments['cleanupMode'];
            $assignments['page'] = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage($this->pageUid);
            $assignments['sourceController'] = $this->sourceController;

            $this->view->assignMultiple($assignments);
        }

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Cleanup action to remove all ctype_manager tsconfig
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function cleanupAction() : void
    {
        if (!$this->checkRequestArguments()) {
            return;
        }

        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        $cleanupMode = $this->request->getArgument('cleanupMode');

        // initialize rootline utility
        if ($cleanupMode === 'rootpage' || $cleanupMode === 'rootline') {
            $rootline = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getRootline($this->pageUid);
        }

        switch ($cleanupMode) {
            case 'page':
                $this->configurationService->removeConfiguration($this->pageUid);
                break;

            case 'rootline':
                foreach ($rootline as $page) {
                    $this->configurationService->removeConfiguration($page['uid']);
                }

                break;

            case 'rootpage':
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
                    ->where(
                        $queryBuilder->expr()->like('TSconfig', $queryBuilder->createNamedParameter('%' . $this->configurationService::CONFIG_ID . '%')),
                    )
                    ->executeQuery();

                while ($row = $result->fetchAssociative()) {
                    $this->configurationService->removeConfiguration($row['uid']);
                }

                break;
        }

        // persist changes
        $this->configurationService->persist();

        $messagePrefix = self::L10N . 'cleanup.message';
        $this->addMessage($messagePrefix, AbstractMessage::OK);

        // redirect to index
        $this->redirect('index', $this->sourceController, 'CtypeManager', ['pageUid' => $this->pageUid]);
    }

    /**
     * Function to clean up a page and its children
     *
     * @throws DBALException
     */
    private function cleanupPageRecursively(int $pageUid) : void
    {
        // init page repository
        if ($this->pageRepository === null) {
            $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        }

        // cleanup current page
        $this->configurationService->removeConfiguration($pageUid);

        // get children of page
        $children = $this->pageRepository->getMenu($pageUid);
        foreach ($children as $child) {
            $this->cleanupPageRecursively($child['uid']);
        }
    }

    /**
     * Function to add a flash message based on the given message prefix
     */
    private function addMessage(string $messagePrefix, int $type) : void
    {
        $this->addFlashMessage(LocalizationUtility::translate($messagePrefix . '.bodytext'), LocalizationUtility::translate(self::L10N . 'message.header.' . $type), $type);
    }

    /**
     * Function to check the request for the needed arguments
     *
     * @throws StopActionException
     */
    private function checkRequestArguments() : bool
    {
        $argumentsToCheck = [
            'pageUid',
            'cleanupMode'
        ];

        foreach ($argumentsToCheck as $argument) {
            if (!$this->request->hasArgument($argument)) {
                $messagePrefix = self::L10N . 'cleanup.message.error.' . $argument;
                $this->addMessage($messagePrefix, AbstractMessage::ERROR);

                // redirect to index
                $this->redirect('index', 'Cleanup');
            }
        }

        return true;
    }
}
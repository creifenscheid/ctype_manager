<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Service\ConfigurationService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Class CtypeController
 *
 * @package \CReifenscheid\CtypeManager\Controller
 */
class CleanupController extends BaseController
{
    /**
     * Configuration l10n base
     */
    private const L10N = 'LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:';

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
     * Index action
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function indexAction() : ResponseInterface
    {
        // get the current page id from the request
        if ($this->request->hasArgument('pageUid')) {
            $pageUid = (int)$this->request->getArgument('pageUid');
        } else {
            $pageUid = $this->request->getQueryParams()['id'];
        }

        $this->view->assign('page', \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage($pageUid));

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Double opt-in for cleanup
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function approvalAction() : ResponseInterface
    {
        if ($this->checkRequestArguments()) {

            $assignments = [];

            // get request arguments
            $arguments = $this->request->getArguments();
            $assignments['cleanupMode'] = $arguments['cleanupMode'];
            $assignments['page'] = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage((int)$arguments['pageUid']);
            $assignments['srcController'] = $this->request->hasArgument('srcController') && $arguments['srcController'] ? : 'Cleanup';

            $this->view->assignMultiple($assignments);
        }

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Cleanup action to remove all ctype_manager tsconfig
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function cleanupAction() : void
    {
        if (!$this->checkRequestArguments()) {
            return;
        }

        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);

        // get request arguments
        $arguments = $this->request->getArguments();
        $cleanupMode = $arguments['cleanupMode'];
        $srcController = $this->request->hasArgument('srcController') && $arguments['srcController'] ? : 'Cleanup';
        $pageUid = (int)$arguments['pageUid'];


        // initialize rootline utility
        if ($cleanupMode === 'rootpage' || $cleanupMode === 'rootline') {
            $rootline = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getRootline($pageUid);
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
        $this->addMessage($messagePrefix, FlashMessage::OK);

        // redirect to index
        $this->redirect('index', $srcController, 'CtypeManager', ['pageUid' => $pageUid]);
    }

    /**
     * Function to clean up a page and its children
     *
     * @throws \Doctrine\DBAL\DBALException
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
        if (!empty($children)) {
            foreach ($children as $child) {
                $this->cleanupPageRecursively($child['uid']);
            }
        }
    }

    /**
     * Function to add a flash message based on the given message prefix
     *
     * @param string $messagePrefix
     * @param int    $type
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    private function addMessage(string $messagePrefix, int $type) : void
    {
        $this->addFlashMessage(LocalizationUtility::translate($messagePrefix . '.bodytext'), LocalizationUtility::translate(self::L10N . 'message.header.' . $type), $type);
    }

    /**
     * Function to check the request for the needed arguments
     *
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    private function checkRequestArguments() : bool
    {
        if (!$this->request->hasArgument('pageUid')) {
            $messagePrefix = self::L10N . 'cleanup.message.error.pageuid';
            $this->addMessage($messagePrefix, FlashMessage::ERROR);

            // redirect to index
            $this->redirect('index', 'Cleanup');
        }

        if (!$this->request->hasArgument('cleanupMode')) {
            $messagePrefix = self::L10N . 'cleanup.message.error.cleanupMode';
            $this->addMessage($messagePrefix, FlashMessage::ERROR);

            // redirect to index
            $this->redirect('index', 'Cleanup');
        }

        return true;
    }
}
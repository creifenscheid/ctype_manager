<?php

namespace CReifenscheid\CtypeManager\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
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
#[AsController]
class CleanupController extends BaseController
{
    /**
     * @var string
     */
    private const L10N = 'LLL:EXT:ctype_manager/Resources/Private/Language/locallang_mod.xlf:';

    private ?PageRepository $pageRepository = null;

    public function indexAction(): ResponseInterface
    {
        if ($this->pageUid && $this->pageUid > 0) {
            $this->moduleTemplate->assign('page', \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage($this->pageUid));
        }

        return $this->moduleTemplate->renderResponse('Cleanup/Index');
    }

    public function approvalAction(): ResponseInterface
    {
        if ($this->checkRequestArguments()) {
            // get request arguments
            $arguments = $this->request->getArguments();

            $this->moduleTemplate->assignMultiple([
                'cleanupMode' => $arguments['cleanupMode'],
                'page' => \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage($this->pageUid),
                'sourceController' => $this->sourceController,
            ]);
        }

        return $this->moduleTemplate->renderResponse('Cleanup/Approval');
    }

    public function cleanupAction(): ResponseInterface
    {
        $rootline = [];

        if (!$this->checkRequestArguments()) {
            return $this->redirect('index', 'Cleanup');
        }

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
                $configuredPages = $this->configurationService->getConfiguredPages();

                foreach ($configuredPages as $page) {
                    $this->configurationService->removeConfiguration($page['uid']);
                }

                break;
        }

        // persist changes
        $this->configurationService->persist();

        $messagePrefix = self::L10N . 'cleanup.message';
        $this->createFlashMessage($messagePrefix, ContextualFeedbackSeverity::OK);

        // redirect to index
        return $this->redirect('index', $this->sourceController, 'CtypeManager', ['pageUid' => $this->pageUid]);
    }

    private function cleanupPageRecursively(int $pageUid): void
    {
        // init page repository
        if (!$this->pageRepository instanceof PageRepository) {
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

    private function createFlashMessage(string $messagePrefix, ContextualFeedbackSeverity $type): void
    {
        $this->addFlashMessage(LocalizationUtility::translate($messagePrefix . '.bodytext'), LocalizationUtility::translate(self::L10N . 'message.header.' . $type->value), $type);
    }

    private function checkRequestArguments(): bool
    {
        if (!$this->request->hasArgument('cleanupMode')) {
            $messagePrefix = self::L10N . 'cleanup.message.error.cleanupMode';
            $this->createFlashMessage($messagePrefix, ContextualFeedbackSeverity::ERROR);

            return false;
        }

        return true;
    }
}

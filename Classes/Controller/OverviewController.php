<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Utility\CTypeUtility;
use CReifenscheid\CtypeManager\Utility\GeneralUtility;
use CReifenscheid\CtypeManager\Utility\ListTypeUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;

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
class OverviewController extends BaseController
{
    public function indexAction(): ResponseInterface
    {
        $pages = $this->configurationService->getConfiguredPages();

        foreach ($pages as $key => $page) {
            // CTypes
            $cTypeConfiguration = GeneralUtility::resolvePageTSConfig((int)$page['uid'], 'CType');
            if ($cTypeConfiguration !== []) {
                $allowedCTypes = GeneralUtility::getKeptItems($cTypeConfiguration);
                foreach ($allowedCTypes as $allowedCType) {
                    if ($allowedCType === 'none') {
                        $page['allowedCTypes'] = 'none';
                        break;
                    }

                    $page['allowedCTypes'][$allowedCType] = GeneralUtility::getLabel(CTypeUtility::getItems(), $allowedCType);
                }
            }

            // List types
            $listTypeConfiguration = GeneralUtility::resolvePageTSConfig((int)$page['uid'], 'list_type');
            if ($listTypeConfiguration !== []) {
                $allowedListTypes = GeneralUtility::getKeptItems($listTypeConfiguration);

                foreach ($allowedListTypes as $allowedListType) {
                    if ($allowedListType === 'none') {
                        $page['allowedListTypes'] = 'none';
                        break;
                    }

                    $label = GeneralUtility::getLabel(ListTypeUtility::getItems(), $allowedListType);
                    if ($label) {
                        $page['allowedListTypes'][] = $label;
                    }
                }
            }

            $pages[$key] = $page;
        }

        if ($this->typo3Version->getMajorVersion() < 13) {
            if ($pages !== []) {
                $this->view->assign('pages', $pages);
            }
        } else {
            if ($pages !== []) {
                $this->moduleTemplate->assign('pages', $pages);
            }
        }

        if ($this->typo3Version->getMajorVersion() < 13) {
            $this->moduleTemplate->setContent($this->view->render());

            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        return $this->moduleTemplate->renderResponse('Overview/Index');
    }
}

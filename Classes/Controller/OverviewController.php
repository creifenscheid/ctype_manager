<?php

namespace CReifenscheid\CtypeManager\Controller;

use CReifenscheid\CtypeManager\Utility\CTypeUtility;
use CReifenscheid\CtypeManager\Utility\ListTypeUtility;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Class OverviewController
 *
 * @package \CReifenscheid\CtypeManager\Controller
 */
class OverviewController extends BaseController
{
    /**
     * Index action
     *
     * @return ResponseInterface
     * @throws DBALException
     * @throws Exception
     */
    public function indexAction() : ResponseInterface
    {
        $pages = $this->getPages();

        foreach ($pages as $key => $page) {
            // CTypes
            $cTypeConfiguration = \CReifenscheid\CtypeManager\Utility\GeneralUtility::resolvePageTSConfig((int)$page['uid'], 'CType');
            if (!empty($cTypeConfiguration)) {
                $allowedCTypes = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getKeptItems($cTypeConfiguration);
                foreach ($allowedCTypes as $allowedCType) {
                    if ($allowedCType === 'none') {
                        $page['allowedCTypes'] = 'none';
                        break;
                    }

                    $page['allowedCTypes'][$allowedCType] = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getLabel(CTypeUtility::getItems(), $allowedCType);
                }
            }

            // List types
            $listTypeConfiguration = \CReifenscheid\CtypeManager\Utility\GeneralUtility::resolvePageTSConfig((int)$page['uid'], 'list_type');
            if (!empty($listTypeConfiguration)) {
                $allowedListTypes = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getKeptItems($listTypeConfiguration);

                foreach ($allowedListTypes as $allowedListType) {
                    if ($allowedListType === 'none') {
                        $page['allowedListTypes'] = 'none';
                        break;
                    }

                    $label = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getLabel(ListTypeUtility::getItems(), $allowedListType);
                    if ($label) {
                        $page['allowedListTypes'][] = $label;
                    }
                }
            }

            $pages[$key] = $page;
        }

        if (!empty($pages)) {
            $this->view->assign('pages', $pages);
        }

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Returns array with all pages with ctype manager configuration
     *
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    private function getPages() : array
    {
        $table = 'pages';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

        return $queryBuilder->select('uid', 'title', 'is_siteroot')
            ->from($table)
            ->where(
                $queryBuilder->expr()->like('TSconfig', "'%### START " . parent::CONFIG_ID . "%'")
            )
            ->execute()->fetchAllAssociative();
    }
}
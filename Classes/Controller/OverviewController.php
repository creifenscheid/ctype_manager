<?php

namespace CReifenscheid\CtypeManager\Controller;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Class OverviewController
 *
 * @package \CReifenscheid\CtypeManager\Controller\
 */
class OverviewController extends ActionController
{
    /**
     * Configuration identifier
     */
    private const CONFIG_ID = 'ctype-manager';

    /**
     * Index action
     *
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function indexAction() : void
    {
        $pages = $this->getPages();

        foreach ($pages as $key => $page) {

            // CTypes
            $cTypeConfiguration = \CReifenscheid\CtypeManager\Utility\GeneralUtility::resolvePageTSConfig((int)$page['uid'], 'CType');
            if (!empty($cTypeConfiguration)) {
                $allowedCTypes = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getKeptItems($cTypeConfiguration);

                foreach ($allowedCTypes as $allowedCType) {
                    $page['allowedCTypes'][] = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getLabel(\CReifenscheid\CtypeManager\Utility\CTypeUtility::getItems(), $allowedCType);
                }
            } else {
                $page['allowedCTypes'] = '*';
            }

            // List types
            $listTypeConfiguration = \CReifenscheid\CtypeManager\Utility\GeneralUtility::resolvePageTSConfig((int)$page['uid'], 'list_type');
            if (!empty($listTypeConfiguration)) {
                $allowedListTypes = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getKeptItems($listTypeConfiguration);

                foreach ($allowedListTypes as $allowedListType) {
                    $page['allowedListTypes'][] = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getLabel(\CReifenscheid\CtypeManager\Utility\ListTypeUtility::getItems(), $allowedListType);
                }
            } else {
                $page['allowedListTypes'] = '*';
            }

            $pages[$key] = $page;
        }

        if (!empty($pages)) {
            $this->view->assign('pages', $pages);
        }
    }

    /**
     * Returns array with all pages with ctype manager configuration
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function getPages() : array
    {
        $table = 'pages';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

        return $queryBuilder
            ->select('uid', 'title', 'is_siteroot')
            ->from($table)
            ->where(
                $queryBuilder->expr()->like('TSconfig', '\'%### START ' . self::CONFIG_ID . '%\'')
            )
            ->execute()->fetchAllAssociative();
    }
}
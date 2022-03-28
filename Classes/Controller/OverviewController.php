<?php

namespace CReifenscheid\CtypeManager\Controller;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
        $pages = $this->getRelevantPages();
        $this->view->assign('pages', $pages);
    }

    /**
     * Returns array with all pages with ctype manager configuration
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getRelevantPages() : array
    {
        $table = 'pages';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

        return $queryBuilder
            ->select('uid', 'title', 'is_siteroot')
            ->from($table)
            ->where(
                $queryBuilder->expr()->like('TSconfig', '\'%### START ' . self::CONFIG_ID . '%\'')
            )
            ->execute()->fetchAll();
    }
}
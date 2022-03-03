<?php

namespace CReifenscheid\CtypeManager\Service;

use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Class ConfigurationService
 *
 * @package \CReifenscheid\CtypeManager\Service\
 */
class ConfigurationService implements SingletonInterface
{
    /**
     * Configuration identifier
     */
    private const CONFIG_ID = 'ctype-manager';

    /**
     * Page repository
     *
     * @var 
     */
    private $pageRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Domain\Repository\PageRepository::class);
    }

    /**
     * Function to write ctype manager configuration
     *
     * @param int   $pageUid
     * @param array $ctypeConfig
     *
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function writeConfiguration(int $pageUid, array $ctypeConfig) : void
    {
        $this->handleConfiguration($pageUid, $ctypeConfig);
    }

    /**
     * Function to remove ctype manager configuration
     *
     * @param int $pageUid
     *
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function removeConfiguration(int $pageUid) : void
    {
        $this->handleConfiguration($pageUid);
    }

    /**
     * Function to write or remove ctype manager configuration
     *
     * @param int   $pageUid
     * @param array $ctypeConfig
     *
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function handleConfiguration(int $pageUid, array $ctypeConfig = []) : void
    {
        /**
         * PREPARE TSCONFIG
         */
        $page = $this->pageRepository->getPage($pageUid);
        $tsConfig = GeneralUtility::trimExplode(PHP_EOL, $page['TSconfig']);

        // remove existing ctype_manager configuration
        $deleteLine = false;
        foreach ($tsConfig as $key => $line) {

            if ($line === '### START ' . self::CONFIG_ID) {
                $deleteLine = true;
            } elseif ($line === '### END ' . self::CONFIG_ID) {
                unset($tsConfig[$key]);
                $deleteLine = false;
            }

            if ($deleteLine) {
                unset($tsConfig[$key]);
            }
        }

        // merge existing tsconfig with ctype configuration
        $pageTSConfig = array_merge($tsConfig, $ctypeConfig);

        /**
         * UPDATE PAGE
         */
        if (!empty($pageTSConfig)) {
            $tableToQuery = 'pages';
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableToQuery);
            $queryBuilder
                ->update($tableToQuery)
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid, PDO::PARAM_INT))
                )
                ->set('TSconfig', implode(PHP_EOL, $pageTSConfig))
                ->executeStatement();
        }
    }
}

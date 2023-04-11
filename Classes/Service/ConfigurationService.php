<?php

namespace CReifenscheid\CtypeManager\Service;

use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
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
 * Class ConfigurationService
 */
class ConfigurationService implements SingletonInterface
{
    /**
     * Configuration identifier
     *
     * @var string
     */
    public const CONFIG_ID = 'ctype-manager';

    private ?Typo3Version $typo3Version = null;

    private DataHandler $dataHandler;

    private array $dataHandlerData = [];

    public function __construct()
    {
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $this->typo3Version = new Typo3Version();
    }

    public function writeConfiguration(int $pageUid, array $ctypeConfig): void
    {
        $this->handleConfiguration($pageUid, $ctypeConfig);
    }

    public function removeConfiguration(int $pageUid): void
    {
        $this->handleConfiguration($pageUid);
    }

    protected function handleConfiguration(int $pageUid, array $ctypeConfig = []): void
    {
        /**
         * PREPARE TSCONFIG
         */
        $page = \CReifenscheid\CtypeManager\Utility\GeneralUtility::getPage($pageUid);
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

        // add page to data handler data
        $this->dataHandlerData['pages'][$pageUid] = [
            'TSconfig' => $pageTSConfig === [] ? '' : implode(PHP_EOL, $pageTSConfig),
        ];
    }

    public function persist(): void
    {
        if ($this->dataHandlerData !== []) {
            $this->dataHandler->start($this->dataHandlerData, []);
            $this->dataHandler->process_datamap();
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws Exception
     */
    public function getConfiguredPages(): array
    {
        $tableToQuery = 'pages';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableToQuery);
        $result = $queryBuilder->select('uid', 'title', 'is_siteroot')
            ->from($tableToQuery)
            ->where(
                $queryBuilder->expr()->like('TSconfig', $queryBuilder->createNamedParameter('%' . self::CONFIG_ID . '%')),
            )
            ->executeQuery();

        return $result->fetchAllAssociative();
    }

    /**
     * Function to compare set configuration vs. configuration sent via form
     */
    public function hasChanged(array $availableItems, array $configuration, array $enabledInForm): bool
    {
        // store already enabled
        $alreadyEnabled = [];

        foreach ($availableItems as $item) {
            $identifier = $this->typo3Version->getMajorVersion() < 12 ? $item[1] : $item['value'];

            // exclude divider and empty items
            if ((!empty($identifier) && $identifier !== '--div--') && \CReifenscheid\CtypeManager\Utility\GeneralUtility::getActivationState($configuration, $identifier)) {
                $alreadyEnabled[] = $identifier;
            }
        }

        // compare the arrays - note: the larger one has to be the first to get a correct result
        if (count($alreadyEnabled) > count($enabledInForm)) {
            $result = array_diff($alreadyEnabled, $enabledInForm);
        } else {
            $result = array_diff($enabledInForm, $alreadyEnabled);
        }

        return $result !== [];
    }
}

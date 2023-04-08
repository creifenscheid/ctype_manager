<?php

defined('TYPO3') || die();

use CReifenscheid\CtypeManager\Controller\CleanupController;
use CReifenscheid\CtypeManager\Controller\ConfigurationController;
use CReifenscheid\CtypeManager\Controller\OverviewController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$extKey = 'ctype_manager';
$moduleIdentifier = GeneralUtility::underscoredToUpperCamelCase($extKey);

return [
    'web_' . $moduleIdentifier => [
        'parent' => 'web',
        'position' => ['after' => '*'],
        'access' => 'admin',
        'iconIdentifier' => 'ctype-manager-extension',
        'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => $extKey,
        'workspaces' => 'live',
        'path' => '/module/web/' . $moduleIdentifier,
        'controllerActions' => [
            ConfigurationController::class => 'index,submit',
            CleanupController::class => 'index,approval,cleanup',
            OverviewController::class => 'index',
        ],
    ],
];

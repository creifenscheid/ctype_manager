<?php

use CReifenscheid\CtypeManager\Controller\CleanupController;
use CReifenscheid\CtypeManager\Controller\ConfigurationController;
use CReifenscheid\CtypeManager\Controller\OverviewController;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

(static function ($extKey) {
    // BACKEND MODULE
    $typo3Version = new Typo3Version();
    if ($typo3Version->getMajorVersion() < 12) {
        ExtensionUtility::registerModule(
            ucfirst(GeneralUtility::underscoredToLowerCamelCase($extKey)),
            'web',
            $extKey,
            'bottom',
            [
                ConfigurationController::class => 'index,submit',
                CleanupController::class => 'index,approval,cleanup',
                OverviewController::class => 'index',
            ],
            [
                'access' => 'admin',
                'iconIdentifier' => 'ctype-manager-extension',
                'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod.xlf',
            ]
        );
    }

    // SKIN
    $GLOBALS['TBE_STYLES']['skins'][$extKey] = [
        'name' => 'CType manager',
        'stylesheetDirectories' => [
            'css' => 'EXT:' . $extKey . '/Resources/Public/Css/',
        ],
    ];
})('ctype_manager');

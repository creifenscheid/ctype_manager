<?php

use CReifenscheid\CtypeManager\Controller\CleanupController;
use CReifenscheid\CtypeManager\Controller\ConfigurationController;
use CReifenscheid\CtypeManager\Controller\OverviewController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

(static function ($extKey) {
    // BACKEND MODULE
    if (str_starts_with(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version(), '11.5.')) {
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

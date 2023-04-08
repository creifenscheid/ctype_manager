<?php

defined('TYPO3') || die();

(static function ($extKey) {

    // todo add version condition
    if ('typo3-version' >= 11 && 'typo3-version < 12) {
        // BACKEND MODULE
        ExtensionUtility::registerModule(
                ucfirst(GeneralUtility::underscoredToLowerCamelCase($extKey)),
            'web',
            $extKey,
            'bottom',
            [
                ConfigurationController::class => 'index,submit',
                CleanupController::class => 'index,approval,cleanup',
                OverviewController::class => 'index'
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
            'css' => 'EXT:' . $extKey . '/Resources/Public/Css/'
        ]
    ];
})('ctype_manager');

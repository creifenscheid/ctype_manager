<?php

defined('TYPO3') || die();

(static function ($extKey) {

    $currentTypo3Version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version();
    
    var_dump($currentTypo3Version);die();

    if ($currentTypo3Version === 'x') {
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

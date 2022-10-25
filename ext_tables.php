<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CReifenscheid\CtypeManager\Controller\CtypeController;
use CReifenscheid\CtypeManager\Controller\CleanupController;
use CReifenscheid\CtypeManager\Controller\OverviewController;
defined('TYPO3') or die();

(static function ($extKey) {
    // BACKEND MODULE
    ExtensionUtility::registerModule(
        ucfirst(GeneralUtility::underscoredToLowerCamelCase($extKey)),
        'web',
        $extKey,
        'bottom',
        [
            CtypeController::class => 'index,submit',
            CleanupController::class => 'index,approval,cleanup',
            OverviewController::class => 'index'
        ],
        [
            'access' => 'admin',
            'iconIdentifier' => 'ctype-manager-extension',
            'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod.xlf',
            'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
            'inheritNavigationComponentFromMainModule' => false,
        ]
    );
    // SKIN
    $GLOBALS['TBE_STYLES']['skins'][$extKey] = [
        'name' => 'CType manager',
        'stylesheetDirectories' => [
            'css' => 'EXT:' . $extKey . '/Resources/Public/Css/'
        ]
    ];
})('ctype_manager');

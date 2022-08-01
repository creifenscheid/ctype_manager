<?php

defined('TYPO3_MODE') or die();

(function ($extKey) {
    
    // BACKEND MODULE
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        ucfirst(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($extKey)),
        'web',
        $extKey,
        'bottom',
        [
            \CReifenscheid\CtypeManager\Controller\CtypeController::class => 'index,submit',
            \CReifenscheid\CtypeManager\Controller\CleanupController::class => 'index,approval,cleanup',
            \CReifenscheid\CtypeManager\Controller\OverviewController::class => 'index'
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
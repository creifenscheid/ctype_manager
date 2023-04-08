<?php

defined('TYPO3') || die();

(static function ($extKey) {
    // SKIN
    $GLOBALS['TBE_STYLES']['skins'][$extKey] = [
        'name' => 'CType manager',
        'stylesheetDirectories' => [
            'css' => 'EXT:' . $extKey . '/Resources/Public/Css/'
        ]
    ];
})('ctype_manager');

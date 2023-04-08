return [
    'web_module' => [
        'parent' => 'web',
        'position' => ['before' => '*'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/web/example',
        'iconIdentifier' => 'module-example',
        'navigationComponent' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
        'labels' => 'LLL:EXT:example/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => MyExampleModuleController::class . '::handleRequest',
            ],
        ],
    ],
    'web_ExtkeyExample' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-example',
        'path' => '/module/web/ExtkeyExample',
        'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Extkey',
        'controllerActions' => [
            MyExtbaseExampleModuleController::class => [
                'list',
                'detail'
            ],
        ],
    ],
];
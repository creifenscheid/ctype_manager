<?php

$extensionKey = 'ctype_manager';

return [
    'dependencies' => [
        'backend',
    ],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@creifenscheid/' . str_replace('_', '-', $extensionKey) . '/' => 'EXT:' . $extensionKey . '/Resources/Public/JavaScript/',
    ],
];

<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TC BE User Admin',
    'description' => 'A collection of modules for administer BE users more comfortably',
    'category' => 'module',
    'version' => '5.0.0-dev',
    'state' => 'stable',
    'author' => 'dkd Internet Service GmbH',
    'author_email' => 'typo3@dkd.de',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ]
    ],
    'autoload' => [
        'psr-4' => [
            'Dkd\\TcBeuser\\' => 'Classes'
        ]
    ]
];

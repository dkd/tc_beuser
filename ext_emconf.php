<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tc_beuser".
 *
 * Auto generated 05-08-2013 09:54
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'TC BE User Admin',
    'description' => 'A collection of modules for administer BE users more comfortably',
    'category' => 'module',
    'version' => '4.0.0',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'dkd Internet Service GmbH',
    'author_email' => 'typo3@dkd.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.1.99',
            'typo3' => '8.7.0-8.7.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Dkd\\TcBeuser\\' => 'Classes/'
        ]
    ]
];

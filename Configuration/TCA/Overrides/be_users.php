<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
    // enabling regular BE users to edit BE users
    $GLOBALS['TCA']['be_users']['ctrl']['adminOnly'] = 0;

    //wizard for the password generator
    $wizConfig = array(
        'type' => 'userFunc',
        'userFunc' => \Dkd\TcBeuser\Utility\PwdWizardUtility::class . '->main',
        'params' => array('type' => 'password')
    );

    $GLOBALS['TCA']['be_users']['columns']['password']['config']['wizards']['tx_tcbeuser'] = $wizConfig;
    $GLOBALS['TCA']['be_users']['columns']['usergroup']['config']['itemsProcFunc'] =
        \Dkd\TcBeuser\Utility\TcBeuserUtility::class . '->getGroupsID';
}

// fe_users modified
$be_users_cols = [
    'tc_beuser_switch_to' => [
        'label' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallang_tca.xlf:be_users.tc_beuser_switch_to',
        'exclude' => '1',
        'config' => [
            'type' => 'check'
        ]
    ]
];

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $be_users_cols);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'be_users',
    '--div--;tc_beuser,tc_beuser_switch_to'
);

unset($GLOBALS['TCA']['be_users']['columns']['usergroup']['config']['wizards']);

$GLOBALS['TCA']['be_users']['columns']['usergroup']['config']['size'] = '10';

<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE) {
    //hooks non-admin be_users
    $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'][] =
        \Dkd\TcBeuser\Utility\HooksUtility::class . '->fakeAdmin';

        //registering hooks for be_groups form mod3
    $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tc_beuser'] =
        \Dkd\TcBeuser\Utility\HooksUtility::class;
    $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tc_beuser'] =
        \Dkd\TcBeuser\Utility\HooksUtility::class;

    // add UserTS to automatically enable the password wizard for be_users
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('tc_beuser.passwordWizard = 1');

    //xclass-ing the record/info a.k.a show_item module
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController::class] = [
        'className' => \Dkd\TcBeuser\Xclass\RecordInfoController::class
    ];
}

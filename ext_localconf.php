<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}


if (TYPO3_MODE) {
    //hooks non-admin be_users
    $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'][] =
        'dkd\\TcBeuser\\Utility\\HooksUtility->fakeAdmin';

        //registering hooks for be_groups form mod3
    $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tc_beuser'] =
        'dkd\\TcBeuser\\Utility\\HooksUtility';

    // add UserTS to automatically enable the password wizard for be_users
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('tc_beuser.passwordWizard = 1');

    //xclass-ing the record/info a.k.a show_item module
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\ContentElement\\ElementInformationController'] = array(
        'className' => 'dkd\\TcBeuser\\Xclass\\RecordInfoController'
    );

    //add formdataprovider to enable exclude of be_users fields
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\dkd\TcBeuser\Form\FormDataProvider\BackendExcludeTableEnable::class] = [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
        ],
        'before' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
        ]
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][\dkd\TcBeuser\Form\FormDataProvider\BackendExcludeTableDisable::class] = [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class,
        ],
        'before' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
        ]
    ];
}

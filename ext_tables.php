<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
    $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tc_beuser');

        // add module before 'Help'
    if (!isset($GLOBALS['TBE_MODULES']['tcTools'])) {
        $temp_TBE_MODULES = array();
        foreach ($GLOBALS['TBE_MODULES'] as $key => $val) {
            if ($key == 'help') {
                $temp_TBE_MODULES['tcTools'] = '';
                $temp_TBE_MODULES[$key] = $val;
            } else {
                $temp_TBE_MODULES[$key] = $val;
            }
        }

        $GLOBALS['TBE_MODULES'] = $temp_TBE_MODULES;
    }

    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        '',
        '',
        '',
        array(
            'access' => 'group,user',
            'name' => 'tcTools',
            'labels' => array(
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangTcTools.xlf'
            ),
            'icon' => 'EXT:tc_beuser/Resources/Public/Images/moduleTcTools.svg'
        )
    );

    # UserAdmin Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        'UserAdmin',
        'bottom',
        '',
        array(
            'routeTarget' => Dkd\TcBeuser\Controller\UserAdminController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'tcTools_UserAdmin',
            'workspaces' => 'online',
            'labels' => array(
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModuleUserAdmin.xlf'
            ),
            'icon' => 'EXT:tc_beuser/Resources/Public/Images/moduleUserAdmin.svg'
        )
    );

    # GroupAdmin Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        'GroupAdmin',
        'bottom',
        '',
        array(
            'routeTarget' => Dkd\TcBeuser\Controller\GroupAdminController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'tcTools_GroupAdmin',
            'workspaces' => 'online',
            'labels' => array(
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModuleGroupAdmin.xlf'
            ),
            'icon' => 'EXT:tc_beuser/Resources/Public/Images/moduleGroupAdmin.svg'
        )
    );

    # FilemountsView Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        'FilemountsView',
        'bottom',
        '',
        array(
            'routeTarget' => Dkd\TcBeuser\Controller\FilemountsViewController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'tcTools_FilemountsView',
            'workspaces' => 'online',
            'labels' => array(
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModuleFilemountsView.xlf'
            ),
            'icon' => 'EXT:tc_beuser/Resources/Public/Images/moduleFilemountsView.svg'
        )
    );

    # Overview Module
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'tcTools',
        'Overview',
        'bottom',
        '',
        array(
            'routeTarget' => Dkd\TcBeuser\Controller\OverviewController::class . '::mainAction',
            'access' => 'group,user',
            'name' => 'tcTools_Overview',
            'workspaces' => 'online',
            'labels' => array(
                'll_ref' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModuleOverview.xlf'
            ),
            'icon' => 'EXT:tc_beuser/Resources/Public/Images/moduleOverview.svg'
        )
    );

    # Overview Module
    // Module Web > Access
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Dkd.TcBeuser',
        'web',
        'tx_Permission',
        'bottom',
        array(
            'Permission' => 'index, edit, update'
        ),
        array(
            'access' => 'group,user',
            'icon' => 'EXT:beuser/Resources/Public/Icons/module-permission.svg',
            'labels' => 'LLL:EXT:tc_beuser/Resources/Private/Language/locallangModulePermission.xlf',
            'navigationComponentId' => 'typo3-pagetree'
        )
    );

    // register a Ajax handler
    $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['PermissionAjaxController::dispatch'] = [
        'callbackMethod' => \Dkd\TcBeuser\Controller\PermissionAjaxController::class . '->dispatch',
        'csrfTokenCheck' => true
    ];
}

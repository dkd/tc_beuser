<?php
namespace Dkd\TcBeuser\Utility;

use TYPO3\CMS\Core\Utility\PathUtility;
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Ivan Kartolo (ivan.kartolo@dkd.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * class.tx_tcbeuser_pwd_wizard.php
 *
 * DESCRIPTION HERE
 * $Id$
 *
 * @author Ivan Kartolo <ivan.kartolo@dkd.de>
 */
class PwdWizardUtility
{
    public $backPath = '../../../../typo3/';

    public function main($PA, $pObj): string
    {
        $output = '';

        // check if per User or PageTS enabled
        if ($this->getBackendUser()->getTSConfig()['tc_beuser.']['passwordWizard']) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $output .= '<script src="../' . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('tc_beuser')) .
                'Resources/Public/JavaScript/pwdgen.js" type="text/javascript"></script>';
            $onclick = 'pass = mkpass();' .
                'document.'.$PA['formName'].'[\''.$PA['itemName'].'\'].value = pass;';
            $onclick .= 'top.TYPO3.Notification.success(\'' .
                htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/Resources/Private/Language/locallangUserAdmin.xlf:password-wizard-notif-header')) .
                '\', ' .
                '\'' . htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/Resources/Private/Language/locallangUserAdmin.xlf:password-wizard-notif-Text')) . '\'' .
                ' + pass, 0);';



            $output .= '<a href="#" class="btn btn-default" onclick="'.htmlspecialchars($onclick).'" title="' .
                htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:tc_beuser/Resources/Private/Language/locallangUserAdmin.xlf:password-wizard')) .'">'.
                $iconFactory->getIcon('actions-move-left', Icon::SIZE_SMALL)->render() .
                '</a>';
        }
        return $output;
    }

    /**
     * Returns the Backend User
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}

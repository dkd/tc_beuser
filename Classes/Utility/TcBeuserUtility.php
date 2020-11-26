<?php
namespace Dkd\TcBeuser\Utility;

/***************************************************************
*  Copyright notice
*
*  (c) 2006 Ingo Renner (ingo.renner@dkd.de)
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;

/**
 * clas for module configuration handling
 *
 * $Id$
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */
class TcBeuserUtility
{
    public $config;

    public static function fakeAdmin()
    {
        self::getBackendUser()->user['admin'] = 1;
    }

    public static function removeFakeAdmin()
    {
        self::getBackendUser()->user['admin'] = 0;
    }

    public static function getSubgroup($id)
    {
        $table = 'be_groups';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $res = $queryBuilder
            ->select('uid', 'title', 'subgroup')
            ->from($table)
            ->where($queryBuilder->expr()->eq('deleted', 0))
            ->andWhere($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )
            ->execute();
        $row = $res->fetch();
        $uid = '';
        if ($row['subgroup']) {
            $subGroup = GeneralUtility::intExplode(',', $row['subgroup']);
            foreach ($subGroup as $subGroupUID) {
                $uid .= $subGroupUID.','.self::getSubgroup($subGroupUID).',';
            }
            return $uid;
        } else {
            return $row['uid'];
        }
    }

    public static function allowWhereMember($TSconfig)
    {
        $userGroup = explode(',', self::getBackendUser()->user['usergroup']);

        $allowWhereMember = array();
        foreach ($userGroup as $uid) {
            $groupID = $uid.','.self::getSubgroup($uid);
            if (strstr($groupID, ',')) {
                $groupIDarray = explode(',', $groupID);
                $allowWhereMember = array_merge($allowWhereMember, array_unique($groupIDarray));
            } else {
                $allowWhereMember[] = $groupID;
            }
        }
        $allowWhereMember = ArrayUtility::removeArrayEntryByValue($allowWhereMember, '');

        return $allowWhereMember;
    }

    public static function allowCreated()
    {
        $table = 'be_groups';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $res = $queryBuilder
            ->select('uid')
            ->from('be_groups')
            ->where($queryBuilder->expr()->eq('pid', 0))
            ->andWhere($queryBuilder->expr()->eq('cruser_id', self::getBackendUser()->user['uid']))
            ->execute();
        $allowCreated = [];
        while ($row = $res->fetch()) {
            $allowCreated[] = $row['uid'];
        }

        return $allowCreated;
    }

    public static function allow($TSconfig)
    {
        $allowID = [];

        if (isset($TSconfig['allow']) && !empty($TSconfig['allow'])) {
            if ($TSconfig['allow'] == 'all') {
                $table = 'be_groups';
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($table);
                $queryBuilder
                    ->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $queryBuilder
                    ->select('uid')
                    ->from($table)
                    ->where($queryBuilder->expr()->eq('pid', 0));
                if (!empty($showGroupID)) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->notIn('uid', implode(',', $showGroupID))
                    );
                }
                $res = $queryBuilder->execute();
                while ($row = $res->fetch()) {
                    $allowID[] = $row['uid'];
                }
            } elseif (strstr($TSconfig['allow'], ',')) {
                $allowID = explode(',', $TSconfig['allow']);
            } else {
                $allowID = [ trim($TSconfig['allow']) ];
            }
        }

        return $allowID;
    }

    public static function denyID($TSconfig)
    {
        $denyID = [];

        if (isset($TSconfig['deny']) && !empty($TSconfig['deny'])) {
            if (strstr($TSconfig['deny'], ',')) {
                $denyID = explode(',', $TSconfig['deny']);
            } else {
                $denyID = [trim($TSconfig['deny'])];
            }
        }

        return $denyID;
    }

    public static function showPrefixID($TSconfig, $mode)
    {
        $showPrefixID = [];
        $table = 'be_groups';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('uid')->from($table)->where($queryBuilder->expr()->eq('pid', 0));
        if (isset($TSconfig[$mode]) && !empty($TSconfig[$mode])) {
            if (strstr($TSconfig[$mode], ',')) {
                $prefix = explode(',', $TSconfig[$mode]);
                $orStatements = $queryBuilder->expr()->orX();
                foreach ($prefix as $pre) {
                    $orStatements->add(
                        $queryBuilder->expr()->like(
                            'title',
                            $queryBuilder->createNamedParameter(trim($pre).'%')
                        )
                    );
                }
                $queryBuilder->andWhere($orStatements);
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->like(
                    'title',
                    $queryBuilder->createNamedParameter($TSconfig[$mode].'%'))
                );
            }
            $res = $queryBuilder->execute();
            while ($row = $res->fetch()) {
                $showPrefixID[] = $row['uid'];
            }
        }

        return $showPrefixID;
    }

    public static function showGroupID()
    {
        $TSconfig = [];
        if (self::getBackendUser()->getTSConfig()['tx_tcbeuser.']) {
            $TSconfig = self::getBackendUser()->getTSConfig()['tx_tcbeuser.'];
        }
            // default value
        $TSconfig['allowCreated'] = (strlen(trim($TSconfig['allowCreated'])) > 0)? $TSconfig['allowCreated'] : '1';
        $TSconfig['allowWhereMember'] = (strlen(trim($TSconfig['allowWhereMember'])) > 0)? $TSconfig['allowWhereMember'] : '1';

        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] == 'explicitAllow') {
            $showGroupID = [];

            //put ID allowWhereMember
            if ($TSconfig['allowWhereMember'] == 1) {
                $allowWhereMember = self::allowWhereMember($TSconfig);
                $showGroupID = array_merge($showGroupID, $allowWhereMember);
            }

            //put ID allowCreated
            if ($TSconfig['allowCreated'] == 1) {
                $showGroupID = array_merge($showGroupID, self::allowCreated());
            }

            //allow
            $allowID = self::allow($TSconfig);
            $showGroupID = array_merge($showGroupID, $allowID);

                //put ID showPrefix
            $showPrefix = self::showPrefixID($TSconfig, 'showPrefix');
            $showGroupID = array_merge($showGroupID, $showPrefix);
        } else {
            //explicitDeny
            $showGroupID = explode(',', self::getAllGroupsID());
            $denyGroupID = [];

            //put ID allowWhereMember
            if ($TSconfig['allowWhereMember'] == 0) {
                $allowWhereMember = self::allowWhereMember($TSconfig);
                $denyGroupID = array_merge($denyGroupID, $allowWhereMember);
            }

            //put ID allowCreated
            if ($TSconfig['allowCreated'] == 0) {
                $denyGroupID = array_merge($denyGroupID, self::allowCreated());
            }

            //deny
            if ($TSconfig['deny'] == 'all') {
                $denyGroupID = array_merge($denyGroupID, explode(',', self::getAllGroupsID()));
            } else {
                $denyID = self::denyID($TSconfig);
                $denyGroupID = array_merge($denyGroupID, $denyID);
            }

            //put ID dontShowPrefix
            $dontShowPrefix = self::showPrefixID($TSconfig, 'dontShowPrefix');
            $denyGroupID = array_merge($denyGroupID, $dontShowPrefix);

            //remove $denyGroupID from $showGroupID
            $showGroupID = array_diff($showGroupID, $denyGroupID);
        }

        return $showGroupID;
    }

    /**
     * Manipulate the list of usergroups based on TS Config
     */
    public static function getGroupsID(&$param, &$pObj)
    {
        $table = 'be_groups';
        $param['items'] = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->select('*')
            ->from($table)
            ->orderBy('title', 'ASC');
        if (self::getBackendUser()->user['admin'] == '0') {
            $queryBuilder->where($queryBuilder->expr()->eq('pid', 0));
            $groupID = implode(',', self::showGroupID());
            if (!empty($groupID)) {
                $queryBuilder->andWhere($queryBuilder->expr()->in('uid', $groupID));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->notIn('uid', self::getAllGroupsID()));
            }
        }
        $res = $queryBuilder->execute();
        while ($row = $res->fetch()) {
            $param['items'][] = [$GLOBALS['LANG']->sL($row['title']), $row['uid'], ''];
        }

        return $param;
    }

    /**
     * Get all ID in a comma-list
     */
    public static function getAllGroupsID()
    {
        $table = 'be_groups';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $res = $queryBuilder->select('uid')->from($table)->execute();
        $id = [];
        while ($row = $res->fetch()) {
            $id[] = $row['uid'];
        }

        return implode(',', $id);
    }

    /**
     * Switches to a given user (SU-mode) and then redirects to the start page
     * of the backend to refresh the navigation etc.
     *
     * @param string $switchUser BE-user record that will be switched to
     * @return void
     */
    public static function switchUser($switchUser)
    {
        $targetUser = BackendUtility::getRecord('be_users', $switchUser);
        if (is_array($targetUser)) {
            self::getBackendUser()->uc['startModuleOnFirstLogin'] = 'tctools_UserAdmin';
            self::getBackendUser()->writeUC();

            $sessionBackend = self::getSessionBackend();
            $sessionBackend->update(
                self::getBackendUser()->getSessionId(),
                [
                    'ses_userid' => (int)$targetUser['uid'],
                    'ses_backuserid' => (int)self::getBackendUser()->user['uid']
                ]
            );

            $redirectUrl = 'index.php' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? '' : '?commandLI=1');
            HttpUtility::redirect($redirectUrl);
        }
    }

    /**
     * Returns the Backend User
     * @return BackendUserAuthentication
     */
    protected static function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return SessionBackendInterface
     */
    protected static function getSessionBackend()
    {
        $loginType = self::getBackendUser()->getLoginType();
        return GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend($loginType);
    }
}

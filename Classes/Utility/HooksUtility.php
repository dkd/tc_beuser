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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * methods for some hooks
 * $Id$
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */
class HooksUtility
{
    public $columns;

    public function befuncPostProcessValue($params, $ref) {}

    public function fakeAdmin($params, &$pObj)
    {
        if (is_array($GLOBALS['MCONF']) &&
            GeneralUtility::isFirstPartOfStr($GLOBALS['MCONF']['name'], 'tcTools') &&
            $GLOBALS['BE_USER']->modAccess($GLOBALS['MCONF'], true)
        ) {
            return 31;
        }

        return $params['outputPermissions'];
    }

    /**
     * Updating be_users
     *
     * @param array $incomingFieldArray
     * @param string $table
     * @param int $id
     * @param DataHandler $tcemain
     */
    public function processDatamap_preProcessFieldArray($incomingFieldArray, $table, $id, $tcemain)
    {
        if ($table == 'be_groups') {
            //unset 'members' from TCA
            $this->removeBackendUserColumnMembersFromTCA();
        }
    }

    /**
     * Preprocess delete actions
     *
     * @param string $table
     * @param int $id
     * @param array $recordToDelete
     * @param bool $recordWasDeleted
     * @param DataHandler $dataHandler
     */
    public function processCmdmap_deleteAction($table, $id, $recordToDelete, $recordWasDeleted, DataHandler $dataHandler)
    {
        $this->removeBackendUserColumnMembersFromTCA();
    }

    /**
     * Removes backend user dummy column "members"
     * from TCA
     */
    protected function removeBackendUserColumnMembersFromTCA()
    {
        $this->columns['members'] = $GLOBALS['TCA']['be_groups']['columns']['members'];
        unset($GLOBALS['TCA']['be_groups']['columns']['members']);
    }

    /**
     * Put back 'members' field in be_groups TCA
     * @param $status
     * @param $table
     * @param $id
     * @param $fieldArray
     * @param $tce
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $tce)
    {
        if ($table == 'be_groups') {
            if (!empty($tce->datamap[$table][$id]['members'])) {
                //get uid and title of group
                if (strstr($id, 'NEW')) {
                    //if it's a new record
                    $uid = $tce->substNEWwithIDs[$id];
                } else {
                    $uid = $id;
                }
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($table);
                $res = $queryBuilder->select('uid', 'title')
                    ->from($table)
                    ->where($queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                    )
                    ->execute();

                while ($row = $res->fetch()) {
                    $usergroup[$row['uid']] = $row['title'];
                }

                $userList = explode(',', $tce->datamap[$table][$id]['members']);
                if (substr($tce->datamap[$table][$id]['members'], -1, 1) == ',') {
                    unset($userList[count($userList)-1]);
                }

                if (!empty($userList)) {
                    foreach ($userList as $userUid) {
                        //get list of groups from user
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                            ->getQueryBuilderForTable('be_users');
                        $queryBuilder->select('*')->from('be_users')
                            ->where($queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($userUid, \PDO::PARAM_INT))
                            )
                            ->execute();
                        $userData = $res->fetch();

                        //only new users
                        if (!GeneralUtility::inList($userData['usergroup'], $uid)) {
                            //update be_users with the new groups
                            $newGroup = $userData['usergroup']? $userData['usergroup'].','.$uid : $uid;
                            $updateArray = array(
                                'usergroup' => $newGroup
                            );
                            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                            $connection = $connectionPool->getConnectionForTable('be_users');
                            $res = $connection->update('be_users', $updateArray, [ 'uid' => $userUid ]);
                        }
                    }
                }
            }
            //remove user
            //get all user, which in the group but not in incomingFieldArray['members']
            if (isset($tce->datamap[$table][$id]['members'])) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('be_users');
               $queryBuilder->select('*')->from('be_users')
                    ->where($queryBuilder->expr()->like(
                        'usergroup',
                        $queryBuilder->createNamedParameter("'%" . $uid . "%'"))
                    );
                if (!empty($userList)) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->notIn('uid', implode(',', $userList))
                    );
                }
                $res = $queryBuilder->execute();

                while ($row = $res->fetch()) {
                    //remove groups id
                    $usergroup = explode(',', $row['usergroup']);
                    for ($i=0; $i<=(count($usergroup)-1); $i++) {
                        if ($usergroup[$i] == $id) {
                            unset($usergroup[$i]);
                        }
                    }
                    //put it back
                    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                    $connection = $connectionPool->getConnectionForTable('be_users');
                    $connection->update(
                        'be_users',
                        [ 'usergroup' => implode(',', $usergroup) ],
                        [ 'uid' => $row['uid'] ]
                    );
                }
            }


            //put back 'members' to TCA
            $tempCol = $this->columns;
            ExtensionManagementUtility::addTCAcolumns("be_groups", $tempCol);
        }
    }
}

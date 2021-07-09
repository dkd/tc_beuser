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

use Dkd\TcBeuser\Tree\View\AbstractTreeView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * GroupTreeUtility.php
 *
 * DESCRIPTION HERE
 * $Id$
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 */
class GroupTreeUtility extends AbstractTreeView
{
    public $fieldArray = ['uid', 'title'];
    public $defaultList = 'uid,title';

    /**
     * Initialize the tree class. Needs to be overwritten
     *
     * @param string $clause Record WHERE clause
     * @param string $orderByFields Record ORDER BY field
     */
    public function init($clause = '', $orderByFields = '')
    {
        parent::init(' AND deleted=0 '.$clause, 'title');

        $this->table    = 'be_groups';
        $this->treeName = 'groups';
    }

    /**
     * Recursively builds a data array from a root $id which is than used to
     * build a tree from it.
     *
     * @param int $id the root id from where to start
     * @return array hierarical array with tree data
     */
    public function buildTree(int $id)
    {
        $tree = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_groups');
        $res = $queryBuilder->select('uid', 'title', 'subgroup')
            ->from('be_groups')
            ->where($queryBuilder->expr()->eq('deleted', 0))
            ->andWhere($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )
            ->execute();

        $row = $res->fetch();
        $tree[$id] = $row;

        if ($row['subgroup']) {
            $subGroups = GeneralUtility::intExplode(',', $row['subgroup']);
            foreach ($subGroups as $newGroupId) {
                $row[$this->subLevelID][$newGroupId] = $this->buildTree($newGroupId);
            }
            return $tree[$id] = $row;
        } else {
            return $row;
        }
    }

    /**
     * Fetches the data for the tree
     *
     * @param int $uid item id for which to select subitems (parent id)
     * @param int $depth Max depth (recursivity limit)
     * @param string $depthData HTML-code prefix for recursive calls.
     * @return int The count of items on the level
     */
    public function getTree($uid, $depth = 999, $depthData = ''): int
    {
        // Buffer for id hierarchy is reset:
        $this->buffer_idH = [];
        // Init vars
        $depth = (int)$depth;
        $HTML = '';
        $a = 0;
        $res = $this->getDataInit($uid);
        $c = $this->getDataCount($res);
        $crazyRecursionLimiter = 999;
        $idH = [];
        // Traverse the records:
        while ($crazyRecursionLimiter > 0 && ($row = $this->getDataNext($res))) {
            $pageUid = ($this->table === 'pages') ? $row['uid'] : $row['pid'];
            if (!$this->getBackendUser()->isInWebMount($pageUid)) {
                // Current record is not within web mount => skip it
                continue;
            }

            $a++;
            $crazyRecursionLimiter--;
            $newID = $row['uid'];
            if ($newID == 0) {
                throw new \RuntimeException(
                    'Endless recursion detected: TYPO3 has detected an error in the database. Please fix it ' .
                    'manually (e.g. using phpMyAdmin) and change the UID of ' . $this->table . ':0 to a new value. ' .
                    'See http://forge.typo3.org/issues/16150 to get more information about a possible cause.',
                    1294586383
                );
            }
            // Reserve space.
            $this->tree[] = [];
            end($this->tree);
            // Get the key for this space
            $treeKey = key($this->tree);
            // If records should be accumulated, do so
            if ($this->setRecs) {
                $this->recs[$row['uid']] = $row;
            }
            // Accumulate the id of the element in the internal arrays
            $this->ids[] = ($idH[$row['uid']]['uid'] = $row['uid']);
            $this->ids_hierarchy[$depth][] = $row['uid'];
            $this->orig_ids_hierarchy[$depth][] = $row['_ORIG_uid'] ?: $row['uid'];

            // Make a recursive call to the next level
            $nextLevelDepthData = $depthData . '<span class="treeline-icon treeline-icon-' . ($a === $c ? 'clear' : 'line') . '"></span>';
            $hasSub = $this->expandNext($newID) && !$row['php_tree_stop'];
            if ($depth > 1 && $hasSub) {
                $nextCount = $this->getTree($newID, $depth - 1, $nextLevelDepthData);
                if (!empty($this->buffer_idH)) {
                    $idH[$row['uid']]['subrow'] = $this->buffer_idH;
                }
                // Set "did expand" flag
                $isOpen = 1;
            } else {
                $nextCount = $this->getCount($newID);
                // Clear "did expand" flag
                $isOpen = 0;
            }

            // if first element
            if ($a === 1) {
                $treeIcon = $depthData . '<span class="treeline-icon"></span>';
            }

            // last element and no subs
            if ($a === $c) {
                $treeIcon = $depthData . '<span class="treeline-icon treeline-icon-joinbottom"></span>';
            }

            // Set HTML-icons, if any:
            if ($this->makeHTML) {
                $HTML = $treeIcon . $this->PMicon($row, $a, $c, $nextCount, $isOpen) . $this->wrapStop($this->getIcon($row), $row);
            }
            // Finally, add the row/HTML content to the ->tree array in the reserved key.
            $this->tree[$treeKey] = [
                'row' => $row,
                'HTML' => $HTML,
                'invertedDepth' => $depth,
                'depthData' => $depthData,
                'bank' => $this->bank,
                'hasSub' => $nextCount && $hasSub,
                'isFirst' => $a === 1,
                'isLast' => $a === $c,
            ];
        }

        $this->getDataFree($res);
        $this->buffer_idH = $idH;
        return $c;
    }
}

<?php
namespace Dkd\TcBeuser\Controller;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use Dkd\TcBeuser\Utility\EditFormUtility;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006 Ingo Renner <ingo.renner@dkd.de>
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

use Dkd\TcBeuser\Module\AbstractModuleController;
use Dkd\TcBeuser\Utility\TcBeuserUtility;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Dkd\TcBeuser\Utility\RecordListUtility;

/**
 * Module 'User Admin' for the 'tc_beuser' extension.
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 * @author Ivan Kartolo <ivan.kartolo@dkd.de>
 * @package TYPO3
 * @subpackage tx_tcbeuser
 */
class UserAdminController extends AbstractModuleController
{
    /**
     * Name of the module
     *
     * @var string
     */
    protected $moduleName = 'tcTools_UserAdmin';

    public $jsCode;
    public $pageinfo;

    /**
     * @var object tx_tcbeuser_config $permChecker helps checking BE user permissions
     */
    public $permChecker;

    /**
     * @var EditFormUtility
     */
    protected $editForm;

    /**
     * working only with be_users table
     *
     * @var string
     */
    public $table = 'be_users';

    /**
     * Data value from GP
     *
     * @var string
     */
    public $data;

    /**
     * Command from GP
     *
     * @var string
     */
    public $cmd;

    /**
     * Disable RTE from GP
     *
     * @var string
     */
    public $disableRTE;

    /**
     * Error string
     *
     * @var array
     */
    public $error;

    /**
     * Load needed locallang files
     */
    public function loadLocallang()
    {
        $this->getLanguageService()
            ->includeLLFile('EXT:tc_beuser/Resources/Private/Language/locallangUserAdmin.xlf');
    }

    /**
     * the main call
     */
    public function main()
    {
        $this->init();

        $access = $this->getBackendUser()->modAccess($this->MCONF, true);

        if ($access || $this->getBackendUser()->isAdmin()) {
            // We need some uid in rootLine for the access check, so use first webmount
            $webmounts = $this->getBackendUser()->returnWebmounts();
            $this->pageinfo['uid'] = $webmounts[0];
            $this->pageinfo['_thePath'] = '/';

            $title = $this->getLanguageService()->getLL('title');
            $this->moduleTemplate->setTitle($title);

            $this->content = $this->moduleTemplate->header($title);
            $this->content .= $this->moduleContent();

            $this->generateMenu('UserAdminMenu');
        }

        $this->getBackendUser()->user['admin'] = 0;
    }

    /**
     * Do processing of data, submitting it to TCEmain.
     *
     * @return void
     */
    public function processData()
    {
        $fakeAdmin = false;

        if ($this->getBackendUser()->user['admin'] != 1) {
            //make fake Admin
            TcBeuserUtility::fakeAdmin();
            $fakeAdmin = true;
        }
        // GPvars specifically for processing:
        $this->data = GeneralUtility::_GP('data');
        $this->cmd = GeneralUtility::_GP('cmd') ? GeneralUtility::_GP('cmd') : array();
        $this->disableRTE = GeneralUtility::_GP('_disableRTE');

        //check data with fe user
        if (is_array($this->data)) {
            $table = array_keys($this->data);
            $uid = array_keys($this->data[$table[0]]);
            $data = $this->data[$table[0]][$uid[0]];
            $fePID = intval($this->extConf['pidFE']);
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('fe_users');
            $queryBuilder
                ->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $res = $queryBuilder->select('*')
                ->from('fe_users')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($fePID, \PDO::PARAM_INT)
                    )
                )
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        'username',
                        $queryBuilder->createNamedParameter($data['username'])
                    )
                )
                ->execute();

            while ($row = $res->fetch()) {
                if ((trim($data['realName']) == trim($row['name'])) && (trim($data['email']) == trim($row['email']))) {
                    $notSync = 0;
                } else {
                    $notSync = 1;
                }
            }
        }

        if ($notSync) {
            $this->error[] = array('error',$this->getLanguageService()->getLL('data-sync'));
        } else {
            // See tce_db.php for relevate options here:
            // Only options related to $this->data submission are included here.
            /** @var DataHandler $tce */
            $tce = GeneralUtility::makeInstance(DataHandler::class);

            // Setting default values specific for the user:
            $TCAdefaultOverride = $this->getBackendUser()->getTSConfig()['TCAdefaults.'] ?? null;
            if (is_array($TCAdefaultOverride)) {
                $tce->setDefaultsFromUserTS($TCAdefaultOverride);
            }

            // Setting internal vars:
            if ($this->getBackendUser()->uc['neverHideAtCopy']) {
                $tce->neverHideAtCopy = 1;
            }
            $tce->debug = 0;
            $tce->disableRTE = $this->disableRTE;

            // Loading TCEmain with data:
            $tce->start($this->data, $this->cmd);
            if (is_array($this->mirror)) {
                $tce->setMirror($this->mirror);
            }

            // If pages are being edited, we set an instruction about updating the page tree after this operation.
            if (isset($this->data['pages'])) {
                BackendUtility::setUpdateSignal('updatePageTree');
            }


            // Checking referer / executing
            $refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
            $httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
            if ($httpHost!=$refInfo['host'] &&
                !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {
                $tce->log(
                    '',
                    0,
                    0,
                    0,
                    1,
                    "Referer host '%s' and server host '%s' did not match and veriCode was not valid either!",
                    1,
                    array($refInfo['host'], $httpHost)
                );
            } else {
                // Perform the saving operation with TCEmain:
                $tce->process_uploads($_FILES);
                $tce->process_datamap();
                $tce->process_cmdmap();

                // If there was saved any new items, load them:
                if (count($tce->substNEWwithIDs_table)) {
                    // Resetting editconf:
                    $this->editconf = array();

                    // Traverse all new records and forge the content of ->editconf
                    // so we can continue to EDIT these records!
                    foreach ($tce->substNEWwithIDs_table as $nKey => $nTable) {
                        $editId = $tce->substNEWwithIDs[$nKey];
                        // translate new id to the workspace version:
                        if ($versionRec = BackendUtility::getWorkspaceVersionOfRecord($this->getBackendUser()->workspace, $nTable, $editId, 'uid')) {
                            $editId = $versionRec['uid'];
                        }

                        $this->editconf[$nTable][$editId] = 'edit';
                        if ($nTable=='pages' && $this->retUrl!='dummy.php' && $this->returnNewPageId) {
                            $this->retUrl .= '&id='.$tce->substNEWwithIDs[$nKey];
                        }
                    }
                }

                $tce->printLogErrorMessages();
            }
        }

        if ($fakeAdmin) {
            TcBeuserUtility::removeFakeAdmin();
        }

        if (isset($_POST['_saveandclosedok']) || $this->closeDoc < 0) {
            //If any new items has been save, the document is CLOSED because
            // if not, we just get that element re-listed as new. And we don't want that!
            $this->closeDocument();
        }
    }

    public function init()
    {
        parent::init();

        TcBeuserUtility::switchUser(GeneralUtility::_GP('SwitchUser'));

        $this->id = 0;
        $this->search_field = GeneralUtility::_GP('search_field');
        $this->pointer = MathUtility::forceIntegerInRange(GeneralUtility::_GP('pointer'), 0, 100000);
        $this->table = 'be_users';

        // if going to edit a record, a menu item is dynamicaly added to
        // the dropdown which is otherwise not visible
        $SET = GeneralUtility::_GET('SET');
        if (isset($SET['function']) && $SET['function'] == 'edit') {
            $this->MOD_SETTINGS['function'] = $SET['function'];
            $this->MOD_MENU['function']['edit'] = $this->getLanguageService()->getLL('edit-user');
            $this->editconf = GeneralUtility::_GET('edit');
        }

        //import fe user
        if ($SET['function'] == 'import') {
            $this->MOD_SETTINGS['function'] = $SET['function'];
        }

        if ($SET['function'] == 'action') {
            $this->MOD_SETTINGS['function'] = $SET['function'];
        }
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = [
            'function' => [
                '1' => $this->getLanguageService()->getLL('list-users'),
                '2'    => $this->getLanguageService()->getLL('create-user'),
                '3' => $this->getLanguageService()->getLL('create-user-wizard'),
            ],
            'hideDeactivatedUsers' => '0'
        ];
        parent::menuConfig();
    }

    /**
     * Generates the module content
     *
     * @return string
     * @throws RouteNotFoundException
     */
    public function moduleContent()
    {
        $content = '';
        if (!empty($this->editconf)) {
            $this->MOD_SETTINGS['function'] = 'edit';
        }

        switch ((string)$this->MOD_SETTINGS['function']) {
            case '1':
                // List Users
                BackendUtility::lockRecords();

                // get buttons for the header
                $this->getButtons();

                // the content
                $content .=  $this->getUserList();
                break;

            case '2':
                // Create a new user
                $data = GeneralUtility::_GP('data');
                $dataKey = is_array($data) ? array_keys($data[$this->table]): array();
                if (is_numeric($dataKey[0])) {
                    $this->editconf = array($this->table => array($dataKey[0] => 'edit'));
                } else {
                    // create new user
                    $this->editconf = array($this->table => array(0 => 'new'));
                }

                $content .= $this->getUserEdit();

                // get Save, close, etc button
                $this->getSaveButton();
                break;

            case '3':
                // Import frontend user
                //show list of fe users
                $this->table = 'fe_users';

                // get buttons for the header
                $this->getButtons();

                // the content
                $content .= $this->getUserList();
                break;

            case 'edit':
                // edit user
                $content .= $this->getUserEdit();

                // get Save, close, etc button
                $this->getSaveButton();
                break;

            case 'import':
                $this->feID = GeneralUtility::_GP('feID');
                $this->R_URI = $this->retUrl = GeneralUtility::makeInstance(UriBuilder::class)
                    ->buildUriFromRoute($GLOBALS['MCONF']['name']);
                $data = GeneralUtility::_GP('data');
                $dataKey = is_array($data) ? array_keys($data[$this->table]): array();
                if (is_numeric($dataKey[0])) {
                    $this->editconf = array($this->table => array($dataKey[0] => 'edit'));
                } else { // create new user
                    $this->editconf = array($this->table => array(0 =>'new'));
                }
                $content .= $this->getUserEdit();

                // get Save, close, etc button
                $this->getSaveButton();
                break;

            case 'action':
                $this->processData();
                HttpUtility::redirect(GeneralUtility::_GP('redirect'));
                break;
        }

        return $content;
    }

    /**
     * Get user list in a table
     *
     * @return string
     * @throws RouteNotFoundException
     */
    public function getUserList()
    {
        $content = '';
        /** @var RecordListUtility $dblist */
        $dblist = GeneralUtility::makeInstance(RecordListUtility::class);
        $dblist->permChecker = $this->permChecker;
        $dblist->script = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute($this->moduleName);
        $dblist->alternateBgColors = true;
        $dblist->userMainGroupOnly = true;

        $dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
        $dblist->showFields = array('realName', 'username', 'usergroup');
        $dblist->disableControls = array_merge($dblist->disableControls, array('import'=>true));

        //Setup for analyze Icon
        $dblist->analyzeLabel = $this->getLanguageService()->getLL('analyze');
        $dblist->analyzeParam = 'beUser';

        if ($this->MOD_SETTINGS['hideDeactivatedUsers']) {
            $dblist->hideDisabledRecords = true;
        }

        //dkd-kartolo
        //prepare to list fe_users
        if ($this->table != 'fe_users') {
            $pid = 0;
            $sortField = 'realName';
        } else {
            $pid = intval($this->extConf['pidFE']);
            $sortField = 'username';
            $dblist->showFields = ['username','first_name', 'last_name', 'email'];
            $dblist->disableControls = [
                'history' => true,
                'new' => true,
                'edit' => true,
                'detail' => true,
                'delete' => true,
                'hide' => true
            ];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('be_users');
            $queryBuilder
                ->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $res = $queryBuilder->select('username')->from('be_users')->execute();
            while ($row = $res->fetch()) {
                $exclude[] = "'".$row['username']."'";
            }
            $dblist->excludeBE = $exclude;
        }

        $dblist->start($pid, $this->table, $this->pointer, $this->search_field);

        // default sorting, needs to be set after $dblist->start()
        $sort = GeneralUtility::_GET('sortField') ? GeneralUtility::_GET('sortField') : $sortField;
        if (is_null($sort)) {
            $dblist->sortField = $sortField;
        }
        $dblist->generateList();
        $content .= $dblist->HTMLcode ? $dblist->HTMLcode : $this->getLanguageService()->getLL('not-found').'<br />';
        $content .= '<br />' .
            '<div class="checkbox">' .
            '<label for="SET[hideDeactivatedUsers]">' .
            BackendUtility::getFuncCheck(
                $this->id,
                'SET[hideDeactivatedUsers]',
                $this->MOD_SETTINGS['hideDeactivatedUsers'],
                '',
                '&search_field='.$this->search_field.'&sortField='.$sort.'&sortRev='.GeneralUtility::_GET('sortRev')
            ) .
            $this->getLanguageService()->getLL('hide-deaktivated-users') .
            '</label>' .
            '</div>';

        // Add JavaScript functions to the page:

        $this->moduleTemplate->addJavaScriptCode(
            'UserListInlineJS',
            '
				' . $this->moduleTemplate->redirectUrls($dblist->listURL()) . '
				' . $dblist->CBfunctions() . '
			'
        );


        // searchbox toolbar
        $searchBox = '';
        if (!$this->modTSconfig['properties']['disableSearchBox'] && ($dblist->HTMLcode || !empty($dblist->searchString))) {
            $searchBox = $dblist->getSearchBox();
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');

            $searchButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton();
            $searchButton
                ->setHref('#')
                ->setClasses('t3js-toggle-search-toolbox')
                ->setTitle(
                    $this->getLanguageService()->sL(
                        'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.searchIcon'
                    )
                )
                ->setIcon($this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL));
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $searchButton,
                ButtonBar::BUTTON_POSITION_LEFT,
                90
            );
        }

        // make new user link
        $content .= ($this->table != 'fe_users') ?
            '<!--
				Link for creating a new record:
			-->
<div id="typo3-newRecordLink">
<a href="' . GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute($this->moduleName, array('SET[function]' => 2)) . '">' .
            $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() .
            ' ' .
            $this->getLanguageService()->getLL('create-user') .
            '</a>' : '';

        $content = '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">' .
            $content .
            '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';

        return $searchBox . $content;
    }

    /**
     * Show edit form
     *
     * @return string
     */
    public function getUserEdit()
    {

        // lets fake admin
        $fakeAdmin = false;

        if ($this->getBackendUser()->user['admin'] != 1) {
            //make fake Admin
            TcBeuserUtility::fakeAdmin();
            $fakeAdmin = true;
        }

        $content = '';

        // the default field to show
        $showColumn = 'disable,username,password,usergroup,realName,email,lang,name,first_name,last_name';

        // get hideColumnGroup from TS and remove it from the showColumn
        if ($this->getBackendUser()->getTSConfig()['tc_beuser.']['hideColumnGroup']) {
            $removeColumnArray = explode(',', $this->getBackendUser()->getTSConfig()['tc_beuser.']['hideColumnUser']);
            $defaultColumnArray = explode(',', $showColumn);

            foreach ($removeColumnArray as $col) {
                $defaultColumnArray = ArrayUtility::removeArrayEntryByValue($defaultColumnArray, $col);
            }

            $showColumn = implode(',', $defaultColumnArray);
        }

        // Creating the editing form, wrap it with buttons, document selector etc.
        //show only these columns

        /** @var FormResultCompiler formResultCompiler */
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);

        /** @var EditFormUtility editForm */
        $this->editForm = GeneralUtility::makeInstance(EditFormUtility::class);
        $this->editForm->formResultCompiler = $formResultCompiler;
        $this->editForm->columnsOnly = $showColumn;
        $this->editForm->editconf = $this->editconf;
        $this->editForm->feID = $this->feID;
        $this->editForm->error = $this->error;
        $this->editForm->inputData = $this->data;
        $this->editForm->R_URI = $this->R_URI;

        $editForm = $this->editForm->makeEditForm();
        $this->viewId = $this->editForm->viewId;

        if ($editForm) {
            // ingo.renner@dkd.de
            reset($this->editForm->elementsData);
            $this->firstEl = current($this->editForm->elementsData);

            if ($this->viewId) {
                // Module configuration:
                $this->modTSconfig = BackendUtility::getModTSconfig($this->viewId, 'mod.xMOD_alt_doc');
            } else {
                $this->modTSconfig=array();
            }

            $content = $formResultCompiler->addCssFiles();
            $content .= $this->compileForm($editForm);
            $content .= $formResultCompiler->printNeededJSFunctions();
            $content .= '</form>';
        }

        if ($fakeAdmin) {
            TcBeuserUtility::removeFakeAdmin();
        }

        return $content;
    }
}

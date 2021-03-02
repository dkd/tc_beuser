<?php
namespace Dkd\TcBeuser\Controller;

use Dkd\TcBeuser\Utility\OverviewUtility;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotUpdatedException;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use Dkd\TcBeuser\Utility\RecordListUtility;
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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Module 'User / Group Overview' for the 'tc_beuser' extension.
 *
 * @author Ingo Renner <ingo.renner@dkd.de>
 * @author Ivan Kartolo <ivan.kartolo@dkd.de>
 * @package TYPO3
 * @subpackage tx_tcbeuser
 */
class OverviewController extends AbstractModuleController
{
    /**
     * Name of the module
     *
     * @var string
     */
    protected $moduleName = 'tcTools_Overview';

    public $jsCode;
    public $pageinfo;
    public $compareFlags;
    public $be_user;
    public $be_group;

    /**
     * Load needed locallang files
     */
    public function loadLocallang()
    {
        $this->getLanguageService()->includeLLFile('EXT:tc_beuser/Resources/Private/Language/locallangOverview.xlf');
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_alt_doc.xml');
    }

    /**
     * Entrance from the backend module. This replace the _dispatch
     *
     * @param ServerRequestInterface $request The request object from the backend
     * @param ResponseInterface $response The reponse object sent to the backend
     *
     * @return ResponseInterface Return the response object
     * @throws Exception
     * @throws RouteNotFoundException
     * @throws SessionNotUpdatedException
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $this->loadLocallang();

        if (GeneralUtility::_POST('ajaxCall')) {
            $method   = GeneralUtility::_POST('method');
            $groupId  = (int) GeneralUtility::_POST('groupId');
            $open     = GeneralUtility::_POST('open');
            $backPath = GeneralUtility::_POST('backPath');

            $userView = GeneralUtility::makeInstance(OverviewUtility::class);
            $content  = $userView->handleMethod($method, $groupId, $open, $backPath);

            echo $content;
        } else {
            $this->init();

            $this->main();

            // Wrap content with form tag
            $content= '<form action="' . htmlspecialchars($this->R_URI) . '" method="post" ' .
                'enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '" ' .
                'name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">' .
                $this->content .
                '</form>';

            $this->moduleTemplate->setContent($content);
            $response->getBody()->write($this->moduleTemplate->renderContent());
        }
        return $response;
    }

    /**
     * Empty function, not needed
     */
    public function processData() {}

    /**
     * @throws RouteNotFoundException
     * @throws SessionNotUpdatedException
     * @throws Exception
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

            if (GeneralUtility::_GP('beUser')) {
                $this->MOD_SETTINGS['function'] = 2;
            }

            if (GeneralUtility::_GP('beGroup')) {
                $this->MOD_SETTINGS['function'] = 1;
            }

            $title = '';
            if ($this->MOD_SETTINGS['function'] == 1) {
                $title = $this->getLanguageService()->getLL('overview-groups');
            } elseif ($this->MOD_SETTINGS['function'] == 2) {
                $title = $this->getLanguageService()->getLL('overview-users');
            }

            $this->moduleTemplate->setTitle($title);

            // Set JS for the AJAX call on overview
            $this->moduleTemplate->getPageRenderer()->addJsFile(
                '../' . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('tc_beuser'))
                . 'Resources/Public/JavaScript/prototype.js'
            );
            $this->moduleTemplate->getPageRenderer()->addJsFile(
                '../' . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('tc_beuser'))
                . 'Resources/Public/JavaScript/ajax.js'
            );

            $this->content = $this->moduleTemplate->header($title);
            $this->content .= $this->moduleContent();

            $this->generateMenu('OverviewMenu');
        }

        $this->getBackendUser()->user['admin'] = 0;
    }

    /**
     * @throws RouteNotFoundException
     * @throws SessionNotUpdatedException
     */
    public function init()
    {
        parent::init();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        TcBeuserUtility::switchUser(GeneralUtility::_GP('SwitchUser'));

        $this->moduleTemplate->addJavaScriptCode(
            'OverviewModule',
            '
            script_ended = 0;
			function jumpToUrl(URL) {
				document.location = URL;
			}

			var T3_BACKPATH = \''.$this->doc->backPath.'\';
			var ajaxUrl = \'' .
            GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute($GLOBALS['MCONF']['name']) .
            '\';' .
            $this->moduleTemplate->redirectUrls(GeneralUtility::linkThisScript())
        );

        $this->id = 0;

        // Update compareFlags
        if (GeneralUtility::_GP('ads')) {
            $this->compareFlags = GeneralUtility::_GP('compareFlags');
            $this->getBackendUser()->pushModuleData('tcTools_Overview/index.php/compare', $this->compareFlags);
        } else {
            $this->compareFlags = $this->getBackendUser()->getModuleData(
                'tcTools_Overview/index.php/compare',
                'ses'
            );
        }

        // Setting return URL
        $this->returnUrl = GeneralUtility::_GP('returnUrl');
        $this->retUrl    = $this->returnUrl ? $this->returnUrl : 'dummy.php';

        // Init user / group
        $beuser = GeneralUtility::_GET('beUser');
        if ($beuser) {
            $this->be_user = $beuser;
        }
        $begroup = GeneralUtility::_GET('beGroup');
        if ($begroup) {
            $this->be_group = $begroup;
        }
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = ['function' => [
            '1' => $this->getLanguageService()->getLL('overview-groups'),
            '2' => $this->getLanguageService()->getLL('overview-users'),
        ]];

        $groupOnly = [];
        if ($this->MOD_SETTINGS['function'] == 1) { // groups
            $groupOnly['members'] = $this->getLanguageService()->getLL('showCol-members');
        }

        $groupAndUser = [
            'filemounts'        => $this->getLanguageService()->getLL('showCol-filemounts'),
            'webmounts'         => $this->getLanguageService()->getLL('showCol-webmounts'),
            'pagetypes'         => $this->getLanguageService()->getLL('showCol-pagetypes'),
            'selecttables'      => $this->getLanguageService()->getLL('showCol-selecttables'),
            'modifytables'      => $this->getLanguageService()->getLL('showCol-modifytables'),
            'nonexcludefields'  => $this->getLanguageService()->getLL('showCol-nonexcludefields'),
            'explicitallowdeny' => $this->getLanguageService()->getLL('showCol-explicitallowdeny'),
            'limittolanguages'  => $this->getLanguageService()->getLL('showCol-limittolanguages'),
            'workspaceperms'    => $this->getLanguageService()->getLL('showCol-workspaceperms'),
            'workspacememship'  => $this->getLanguageService()->getLL('showCol-workspacememship'),
            'description'       => $this->getLanguageService()->getLL('showCol-description'),
            'modules'           => $this->getLanguageService()->getLL('showCol-modules'),
            'tsconfig'          => $this->getLanguageService()->getLL('showCol-tsconfig'),
            'tsconfighl'        => $this->getLanguageService()->getLL('showCol-tsconfighl'),
        ];
        $this->MOD_MENU['showCols'] = array_merge($groupOnly, $groupAndUser);

        parent::menuConfig();
    }

    /**
     * Generates the module content
     *
     * @return string
     * @throws Exception
     */
    public function moduleContent() : string
    {
        switch ((string)$this->MOD_SETTINGS['function']) {
            case '1':
                // Group view
                $content = $this->getGroupView($this->be_group);
                $this->getButtons();
                break;
            case '2':
                // User view
                $content = $this->getUserView($this->be_user);
                $this->getButtons();
                break;
            default:
                $content = '';
        }

        return $content;
    }

    /**
     * @param $userUid
     * @return string
     * @throws Exception
     */
    public function getUserView($userUid) : string
    {
        $content = '';

        if ($this->be_user == 0) {
            // Warning - no user selected
            $content .= $this->getLanguageService()->getLL('select-user');

            $this->id = 0;
            $this->search_field = GeneralUtility::_GP('search_field');
            $this->pointer = MathUtility::forceIntegerInRange(
                GeneralUtility::_GP('pointer'),
                0,
                100000
            );
            $this->table = 'be_users';

            /** @var RecordListUtility $dblist */
            $dblist = GeneralUtility::makeInstance(RecordListUtility::class);
            $dblist->backPath = $this->doc->backPath;
            $dblist->script = $this->MCONF['script'];
            $dblist->alternateBgColors = true;
            $dblist->userMainGroupOnly = true;
            $dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
            $dblist->showFields = array('username', 'realName', 'usergroup');
            $dblist->disableControls = array('edit' => true, 'hide' => true, 'delete' => true, 'import' => true);

            // Setup for analyze Icon
            $dblist->analyzeLabel = $this->getLanguageService()
                ->sL('LLL:EXT:tc_beuser/Resources/Private/Language/locallangUserAdmin.xlf:analyze');
            $dblist->analyzeParam = 'beUser';

            $dblist->start(0, $this->table, $this->pointer, $this->search_field);
            $dblist->generateList();

            $content .= $dblist->HTMLcode ? $dblist->HTMLcode : '<br />' .
                $this->getLanguageService()->getLL('not-found').'<br />';

            // Add JavaScript functions to the page:

            $this->moduleTemplate->addJavaScriptCode(
                'UserListInlineJS',
                '
				' . $this->moduleTemplate->redirectUrls($dblist->listURL()) . '
				' . $dblist->CBfunctions() . '
			'
            );

            // Searchbox toolbar
            $searchBox = '';
            if (!$this->modTSconfig['properties']['disableSearchBox'] && ($dblist->HTMLcode || !empty($dblist->searchString))) {
                $searchBox = $dblist->getSearchBox();
                $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');

                $searchButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton();
                $searchButton
                    ->setHref('#')
                    ->setClasses('t3js-toggle-search-toolbox')
                    ->setTitle($this->getLanguageService()->getLL('search-user'))
                    ->setIcon($this->getIcon('actions-search'));

                $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                    $searchButton,
                    ButtonBar::BUTTON_POSITION_LEFT,
                    90
                );

            }

            $content = $searchBox . $content;

        } else {
            // Real content
            $this->table = 'be_users';
            $userRecord = BackendUtility::getRecord($this->table, $userUid);
            $content .= $this->getColSelector();
            $content .= '<br />';
            $content .= $this->getUserViewHeader($userRecord);
            /** @var OverviewUtility $userView */
            $userView = GeneralUtility::makeInstance(OverviewUtility::class);

            // If there is member in the compareFlags array, remove it. There is no 'member' in user view
            unset($this->compareFlags['members']);
            $content .= $userView->getTable($userRecord, $this->compareFlags);
        }

        return $content;
    }

    /**
     * @param $groupUid
     * @return string
     * @throws Exception
     */
    public function getGroupView($groupUid) : string
    {
        $content = '';

        if ($this->be_group == 0) {
            // Warning - no user selected
            $content .= $this->getLanguageService()->getLL('select-group');

            $this->id = 0;
            $this->search_field = GeneralUtility::_GP('search_field');
            $this->pointer = MathUtility::forceIntegerInRange(
                GeneralUtility::_GP('pointer'),
                0,
                100000
            );
            $this->table = 'be_groups';

            /** @var RecordListUtility $dblist */
            $dblist = GeneralUtility::makeInstance(RecordListUtility::class);
            $dblist->backPath = $this->doc->backPath;
            $dblist->script = $this->MCONF['script'];
            $dblist->alternateBgColors = true;
            $dblist->userMainGroupOnly = true;
            $dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
            $dblist->showFields = array('title');
            $dblist->disableControls = array(
                'edit' => true,
                'hide' => true,
                'delete' => true,
                'history' => true,
                'new' => true,
                'import' => true
            );

            // Setup for analyze Icon
            $dblist->analyzeLabel = $this->getLanguageService()->sL(
                'LLL:EXT:tc_beuser/Resources/Private/Language/locallangGroupAdmin.xlf:analyze'
            );
            $dblist->analyzeParam = 'beGroup';

            $dblist->start(0, $this->table, $this->pointer, $this->search_field);
            $dblist->generateList();

            if ($dblist->HTMLcode) {
                $content .= $dblist->HTMLcode;
            } else {
                $content .= '<br />'
                    . $this->getLanguageService()->sL(
                        'LLL:EXT:tc_beuser/Resources/Private/Language/locallangGroupAdmin.xlf:not-found'
                    ) . '<br />';
            }

            // Searchbox toolbar
            if (
                !$this->modTSconfig['properties']['disableSearchBox']
                && ($dblist->HTMLcode || !empty($dblist->searchString))
            ) {
                $searchBox = $dblist->getSearchBox();
                $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');

                $searchButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton();
                $searchButton
                    ->setHref('#')
                    ->setClasses('t3js-toggle-search-toolbox')
                    ->setTitle($this->getLanguageService()->getLL('search-group'))
                    ->setIcon($this->getIcon('actions-search'));

                $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                    $searchButton,
                    ButtonBar::BUTTON_POSITION_LEFT,
                    90
                );

            }

            $content = $searchBox . $content;
        } else {
            // Real content
            $this->table = 'be_groups';
            $groupRecord = BackendUtility::getRecord($this->table, $groupUid);
            $content .= $this->getColSelector();
            $content .= '<br />';

            /** @var OverviewUtility $userView */
            $userView = GeneralUtility::makeInstance(OverviewUtility::class);

            $content .= $userView->getTableGroup($groupRecord, $this->compareFlags);
        }

        return $content;
    }

    public function getColSelector() : string
    {
        $content = '';

        foreach ($this->MOD_MENU['showCols'] as $key => $label) {
            $checked = $this->compareFlags[$key] ? 'checked="checked"' : '';
            $content .= <<<EOL
<span style="display: block; float: left; min-width: 200px;">
    <input type="checkbox" value="1" name="compareFlags[$key]"  id="compareFlags[$key]" $checked>&nbsp;
    <label for="compareFlags[$key]">$label</label>
</span>
EOL;
        }
        $content .= <<<EOL
<br style="clear: left;" />
<br />
<input class="btn btn-default" type="submit" name="ads" value="Update" />
<br />
EOL;

        return $content;
    }

    /**
     * @param $userRecord
     * @return string
     * @throws RouteNotFoundException
     */
    public function getUserViewHeader($userRecord) : string
    {
        $recTitle = htmlspecialchars(BackendUtility::getRecordTitle($this->table, $userRecord));
        $iconImg = $this->iconFactory->getIconForRecord($this->table, $userRecord,Icon::SIZE_SMALL)->render();
        $control = $this->makeUserControl($userRecord);

        return $iconImg . ' ' . $recTitle . ' ' . $control . '<br><br>';
    }

    /**
     * @param $userRecord
     * @return string
     * @throws RouteNotFoundException
     */
    public function makeUserControl($userRecord) : string
    {
        $control = '<div class="btn-group">';
        // Edit (Always shown)
        $icon = $this->getIcon('actions-open')->render();
        $onClick = htmlspecialchars($this->editOnClick(
            '&edit[' . $this->table . '][' . $userRecord['uid'] . ']=edit&SET[function]=edit',
            GeneralUtility::getIndpEnv('REQUEST_URI').'SET[function]=2'
        ));
        $control .= <<<EOL
<a href="#" class="btn btn-default" onclick="$onClick">
    $icon
</a>
EOL;

        // Info (Always shown)
        $icon = $this->getIcon('actions-document-info')->render();
        $onClick = htmlspecialchars(
            'top.launchView(\'' . $this->table . '\', \'' . $userRecord['uid'] . '\'); return false;'
        );
        $control .= <<<EOL
<a href="#" class="btn btn-default" onclick="$onClick">
    $icon
</a>
EOL;

        // Hide
        $hiddenField = $GLOBALS['TCA'][$this->table]['ctrl']['enablecolumns']['disabled'];
        if ($userRecord[$hiddenField]) {
            $icon = $this->getIcon('actions-edit-unhide')->render();
            $params = '&data[' . $this->table . '][' . $userRecord['uid'] . '][' . $hiddenField . ']=0&SET[function]=action';
            $control .= '<a href="#" class="btn btn-default" ' .
                'onclick="return jumpToUrl(\'' . htmlspecialchars($this->actionOnClick($params, -1)) . '\');">' .
                $icon .
                '</a>';
        } else {
            $icon = $this->getIcon('actions-edit-hide')->render();
            $params = '&data[' . $this->table . '][' . $userRecord['uid'] . '][' . $hiddenField . ']=1&SET[function]=action';
            $control .= '<a href="#" class="btn btn-default" ' .
                'onclick="return jumpToUrl(\'' . htmlspecialchars($this->actionOnClick($params, -1)) . '\');">' .
                $icon .
                '</a>';
        }

        // Delete
        $icon = $this->getIcon('actions-edit-delete')->render();
        $params = '&cmd['.$this->table.']['.$userRecord['uid'].'][delete]=1&SET[function]=action&prErr=1&uPT=1';
        $control .= '<a href="#" class="btn btn-default" ' . 'onclick="' .
            htmlspecialchars('if (confirm(' .
                GeneralUtility::quoteJSvalue(
                    sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:mess.delete'),
                        $userRecord['username']
                    ) .
                    BackendUtility::referenceCount(
                        $this->table, $userRecord['uid'], ' (There are %s reference(s) to this record!)'
                    )
                ) . ')) { return jumpToUrl(\'' .
                $this->actionOnClick(
                    $params,
                    GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute($GLOBALS['MCONF']['name'])
                ) . '\'); } return false;'
            ) . '">' . $icon . '</a>';

        // Switch user
        if (!$userRecord[$hiddenField] &&
            ($this->getBackendUser()->user['tc_beuser_switch_to'] || $this->getBackendUser()->isAdmin())
        ) {
            if ($this->getBackendUser()->user['uid'] !== (int)$userRecord['uid']) {
                $href = GeneralUtility::linkThisScript(array('SwitchUser' => $userRecord['uid']));
                $title = htmlspecialchars('Switch user to: ' . $userRecord['username']);
                $icon = $this->getIcon('actions-system-backend-user-switch')->render();
                $control .= <<<EOL
<a class="btn btn-default" href="$href" target="_top" title="$title">
    $icon
</a>
EOL;
            }
        }

        return $control . '</div>';
    }

    /**
     * ingo.renner@dkd.de: from BackendUtility, modified
     *
     * Returns a JavaScript string (for an onClick handler) which will load the EditDocumentController script that shows
     * the form for editing of the record(s) you have send as params.
     * REMEMBER to always htmlspecialchar() content in href-properties to ampersands get converted to entities (XHTML
     * requirement and XSS precaution)
     *
     * @param string $params Parameters sent along to EditDocumentController. This requires a much more details
     * description which you must seek in Inside TYPO3s documentation of the FormEngine API. And example could be
     * '&edit[pages][123] = edit' which will show edit form for page record 123.
     * @param string $requestUri An optional returnUrl you can set - automatically set to REQUEST_URI.
     *
     * @return string
     * @throws RouteNotFoundException
     * @see: BackendUtility::editOnClick
     */
    public static function editOnClick(string $params, string $requestUri = '') : string
    {
        if ($requestUri == -1) {
            $returnUrl = 'T3_THIS_LOCATION';
        } else {
            $returnUrl = GeneralUtility::quoteJSvalue(
                rawurlencode($requestUri ?: GeneralUtility::getIndpEnv('REQUEST_URI'))
            );
        }
        return 'window.location.href=' . GeneralUtility::quoteJSvalue(
            GeneralUtility::makeInstance(UriBuilder::class)
                    ->buildUriFromRoute('tcTools_UserAdmin') . $params . '&returnUrl='
        ) . '+' . $returnUrl . '; return false;';
    }


    /**
     * Create link for the hide/unhide and delete icon.
     * not using tce_db.php, because we need to manipulate user's permission
     *
     * @param string $params param with command (hide/unhide, delete) and records id
     * @param string $requestURI redirect link, after process the command
     * @return string jumpTo URL link with redirect
     * @throws RouteNotFoundException
     */
    public function actionOnClick(string $params, string $requestURI = '') : string
    {
        $redirect = '&redirect='
            . (
                $requestURI == -1 ? "'+T3_THIS_LOCATION+'" : rawurlencode(
                    $requestURI ? $requestURI : GeneralUtility::getIndpEnv('REQUEST_URI')
                )
            ) . '&prErr=1&uPT=1';
        return GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('tcTools_UserAdmin')
            . $params . $redirect;
    }

    private function getIcon(string $identifier) : Icon
    {
        return $this->iconFactory->getIcon($identifier, Icon::SIZE_SMALL);
    }
}

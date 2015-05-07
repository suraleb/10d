<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Module
 * @package  Admin
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * @see Cms_Module_Admin
 */
class Admin_cIndex extends Cms_Module_Admin
{
    protected $_accessList = array(
        'run' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ACP_VIEW
        ),

        'elfinderAction' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ELFINDER_VIEW
        ),

        'newlngAction' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ACP_VIEW
        ),

        'editortplsAction' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ACP_VIEW
        )
    );

    protected $_defaultModules = array(
        'static', 'poll', 'gallery', 'index', 'admins', 'language', 'update',
        'settings'
    );

    public function run()
    {
        $session = Cms_Session::getInstance();

        // Auto Update
        $this->_tpl->checkForUpdate = false;
        if (empty($session->skipUpdateCheck)) {
            if (($this->_tpl->checkForUpdate = $this->_autoUpdateCheck())) {
                $session->skipUpdateCheck = true;
            }
        }

        // Have new updates
        if ($this->_user->hasAcl(CMS_ACCESS_UPDATE_INSTALL)
            && empty($session->skipUpdateNotice)) {
            $list = Cms_Filemanager::dirList(CMS_TMP . 'packages', 0, '\.xml$');
            $this->_tpl->updateNotice = (count($list) > 0);
            $session->skipUpdateNotice = true;
        }

        // Advanced modules and default modules
        $this->_tpl->defaultModules = array();
        foreach ($this->_defaultModules as $mdl) {
            if ($mdl == 'index') {
                continue;
            }

            if ($this->_user->hasAcl(constant('CMS_ACCESS_' . strtoupper($mdl) . '_VIEW'))) {
                $this->_tpl->defaultModules[] = $mdl;
            }
        }

        $this->_tpl->advancedModules = array();
        foreach (glob(CMS_ROOT . 'actions/Admin/c*.php') as $mdl) {
            $name = substr(basename($mdl, '.php'), 1);
            $name = strtolower($name);
            if (in_array($name, $this->_defaultModules)) {
                continue;
            }
            $const = 'CMS_ACCESS_' . strtoupper($name) . '_VIEW';
            if (defined($const) && $this->_user->hasAcl(constant($const))) {
                $this->_tpl->advancedModules[] = $name;
            }
        }

        // Logs
        if ($this->_user->hasAcl(CMS_ACCESS_LOGS_VIEW)) {
            $db = new Cms_Db_Logs();
            $udn = new Cms_Db_Users();

            // sort set
            $sortSet = array(
                'action'  => 'l.text',
                'time'    => 'l.time',
                'ip'      => 'l.ip',
                'account' => 'u.name'
            );
            $sortBy = null;
            if (!empty($this->_input->sortby)) {
                if (isset($sortSet[$this->_input->sortby])) {
                    $this->_tpl->sortBy = $sortBy = $sortSet[$this->_input->sortby];
                }
            }

            // way set
            if ($sortBy) {
                $way = 'desc';
                if (!empty($this->_input->sortway)
                    && in_array($this->_input->sortway, array('asc', 'desc'))) {
                    $this->_tpl->sortway = $way = $this->_input->sortway;
                }
            }

            $query = $db->select()
                ->setIntegrityCheck(false)
                ->from(
                    array('l' => $db->getTableName()),
                    array('user_id', 'text', 'time', 'ip' => new Zend_Db_Expr('INET_NTOA(l.ip)'))
                )->joinLeft(
                    array('u' => $udn->getTableName()), 'u.id = l.user_id', 'name'
                );

            if (isset($way)) {
                $query->order("{$sortBy} {$way}");
            } else {
                $query->order('l.time DESC');
            }

            $this->_tpl->logPaginator = $db->getPaginatorRows(
                $query, $this->_input->logpage, 7
            );
        }

        // output
        $this->_layout->content = $this->_tpl->render(
            $this->getTemplate(__FUNCTION__)
        );

        echo $this->_layout->render();
    }

    public function elfinderAction()
    {
        if ($this->_input->mode == 'connector') {
            Zend_Loader::loadFile(
                'elFinder.class.php',
                CMS_HTDOCS . '3dparty/elfinder'
            );
            $opts = array(
                'root'      => CMS_TMP . 'uploads/public',
                'URL'       => CMS_HOST . '/tmp/uploads/public/',
                'rootAlias' => 'Home',
                'defaults'  => array(
                    'read'  => $this->_user->hasAcl(CMS_ACCESS_ELFINDER_READ),
                    'write' => $this->_user->hasAcl(CMS_ACCESS_ELFINDER_WRITE),
                    'rm'    => $this->_user->hasAcl(CMS_ACCESS_ELFINDER_REMOVE)
                )
            );
            $fm = new elFinder($opts);
            $fm->run();

            // we have exit, but to be sure
            Cms_Core::shutdown(true);
        }

        $this->_layout->workAreaOnly = true;
        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));
        echo $this->_layout->render();
    }

    public function newlngAction()
    {
        if (Cms_Locale::isLocale($this->_input->id)) {
            Cms_Core::setCookie('lng', $this->_input->id);
        }
        Cms_Core::redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Creates template list for the TinyMce editor
     *
     * @return void
     */
    public function editortplsAction()
    {
        // header
        header('Content-type: text/javascript');

        // prevent browser from caching
        header('pragma: no-cache');

        // i.e. contents have already expired
        header('expires: 0');

        // default types
        $types = array(
            'image'    => 'tinyMCEImageList',
            'link'     => 'tinyMCELinkList',
            'media'    => 'tinyMCEMediaList',
            'template' => 'tinyMCETemplateList'
        );

        // checking the type
        $type = strtolower($this->_input->type);
        if (!isset($types[$type])) {
            Cms_Core::shutdown(true);
        }

        $hash = array();

        $path = CMS_TMP . "uploads/public/editor/{$type}/";

        // building list
        $list = Cms_Filemanager::dirList($path, null, '\.htm$');
        foreach ($list as $entry) {
            $hash[] = array(
                pathinfo($entry, PATHINFO_FILENAME),
                '/' . str_replace(CMS_ROOT, '', $path) . $entry
            );
        }

        echo "var {$types[$type]} = " . Zend_Json::encode($hash) . ';';
        Cms_Core::shutdown(true);
    }

    protected function _autoUpdateCheck()
    {
        if (!$this->_user->hasAcl(CMS_ACCESS_UPDATE_CHECK)) {
            return false;
        }

        $c = Cms_Config::getInstance();
        if ($c->modules->update->disabled) {
            return false;
        }

        $t = Cms_Filemanager::fileRead(CMS_TMP . 'packages/update.time');
        if (empty($t)) {
            $t = 0;
        }

        return (intval($t) + intval($c->modules->update->period) < time());
    }
}

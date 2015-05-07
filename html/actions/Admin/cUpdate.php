<?php
/**
 * kkCms: Content Management System
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

class Admin_cUpdate extends Cms_Module_Admin
{
    # Module version
    const VERSION = '0.3';

    # updater version
    const UPDATER_VERSION = '1.0';

    protected $_errors = array();

    protected $_updDir;

    protected $_accessList = array(
        'run' => array(
            '_ROLE' => CMS_USER_ADMIN,
            '_ACCESS' => CMS_ACCESS_UPDATE_VIEW
        ),

        'checknowAction' => array(
            '_ROLE' => CMS_USER_ADMIN,
            '_ACCESS' => CMS_ACCESS_UPDATE_CHECK
        ),

        'infoAction' => array(
            '_ROLE' => CMS_USER_ADMIN,
            '_ACCESS' => CMS_ACCESS_UPDATE_CHECK
        ),

        'cancelAction' => array(
            '_ROLE' => CMS_USER_ADMIN,
            '_ACCESS' => CMS_ACCESS_UPDATE_INSTALL
        ),

        'zfAction' => array(
            '_ROLE' => CMS_USER_ADMIN,
            '_ACCESS' => CMS_ACCESS_UPDATE_INSTALL
        ),

        'zfgetAction' => array(
            '_ROLE' => CMS_USER_ADMIN,
            '_ACCESS' => CMS_ACCESS_UPDATE_INSTALL
        ),

        'installAction' => array(
            '_ROLE' => CMS_USER_ADMIN,
            '_ACCESS' => CMS_ACCESS_UPDATE_INSTALL
        )
    );

    public function __construct()
    {
        parent::__construct();
        $this->_updDir = CMS_TMP . 'packages';
    }

    public function run()
    {
        $pkg = $marked = array();

        $l = Cms_Filemanager::dirList($this->_updDir, 0, '^([0-9a-z.]+)\.xml');

        foreach ($l as $v) {
            $nm = basename($v, '.xml');
            $inf = $this->_getPackageInfo($nm);
            if ($inf) {
                $pkg[(string)$inf->version] = $inf;
                if (Cms_Filemanager::fileExists($this->_updDir . CMS_SEP . "{$nm}.apply")) {
                    $marked[(string)$inf->version] = $nm;
                }
            }
        }

        uksort($pkg, 'version_compare');

        $this->_tpl->zfTime = filemtime(CMS_ROOT . 'lib/Zend/Version.php');
        $this->_tpl->packages = $pkg;
        $this->_tpl->marked = $marked;


        // database logs
        $db = new Cms_Db_Logs();
        $udb = new Cms_Db_Users();

        $query = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                array('l' => $db->getTableName()),
                array('user_id', 'text', 'time', 'ip' => new Zend_Db_Expr('INET_NTOA(`l`.`ip`)'))
            )
            ->joinLeft(
                array('u' => $udb->getTableName()), 'u.id = user_id', 'name'
            )
            ->where('LOCATE(?, text)', 'LOG_ADMIN_UPDATE_SUCCESS')
            ->where('module = ?', 'Cron')
            ->order('time DESC');

        // zend framework version update
        $this->_tpl->zfUpgradeAvailable = (
            Zend_Version::compareVersion(Zend_Version::getLatest()) === 1
        );

        $entries = $db->fetchAll($query);
        if (count($entries)) {
            $this->_tpl->logEntries = $entries->toArray();
        }

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));
        echo $this->_layout->render();
    }

    public function infoAction()
    {
        if (!isset($this->_input->id)) {
            Cms_Core::e404();
        }

        $this->_tpl->package = $this->_getPackageInfo($this->_input->id);

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));
        echo $this->_layout->render();
    }

    public function zfAction()
    {
        $dialog = Cms_Dialog::getInstance();

        $updateVersion = Zend_Version::getLatest();
        if (Zend_Version::compareVersion($updateVersion) !== 1) {
            $dialog->construct(
                'MSG_ADMIN_UPDATE_UPDATE_ZF_NOUPDATES', Cms_Dialog::TYPE_INFO,
                array('redirect' => '/open/admin-update/')
            );
        }

        $this->_tpl->zfNewVersion = $updateVersion;

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function zfgetAction()
    {
        $lng = Cms_Translate::getInstance();
        $version = $this->_input->version;

        if (empty($version)) {
            Cms_Core::ajaxError($lng->_('MSG_ADMIN_UPDATE_UPDATE_ZF_NOUPDATES'));
        }

        @set_time_limit(0);
        @ignore_user_abort(true);

        $zfPath = CMS_TMP . "packages/ZendFramework-{$version}-minimal.zip";
        if (Cms_Filemanager::fileExists($zfPath)) {
            $data = array('msg' => $lng->_('MSG_ADMIN_UPDATE_UPDATE_ZF_EXISTS'));
            Cms_Core::ajaxSuccess($data);
        }

        $zfUri = "http://framework.zend.com/releases/" .
            "ZendFramework-{$version}/ZendFramework-{$version}-minimal.zip";

        $client = new Zend_Http_Client();
        $client->setUri($zfUri)
            ->setMethod(Zend_Http_Client::POST)
            ->setConfig(
                array(
                    'maxredirects' => 0,
                    'timeout'      => 30,
                    'useragent'    => 'kkcms updater v' . self::UPDATER_VERSION
                )
            );

        $response = $client->request();
        if ($response->getStatus() !== 200) {
            Cms_Core::ajaxError($lng->_('MSG_ADMIN_UPDATE_UPDATE_ZF_BAD_STATUS'));
        }

        $status = Cms_Filemanager::fileWrite(
            CMS_TMP . "packages/ZendFramework-{$version}-minimal.zip",
            $response->getBody()
        );

        if ($status) {
            $data = array('msg' => $lng->_('MSG_ADMIN_UPDATE_UPDATE_ZF_DOWNLOADED'));
            Cms_Core::ajaxSuccess($data);
        }

        Cms_Core::ajaxError($lng->_('MSG_ADMIN_UPDATE_UPDATE_ZF_ERROR_WRITE'));
    }

    public function cancelAction()
    {
        if (!isset($this->_input->id)) {
            Cms_Core::e404();
        }

        $id = $this->_input->id;
        $pkg = $this->_getPackageInfo($id);

        Cms_Filemanager::fileUnlink("{$this->_updDir}/{$id}.apply");

        // loging
        Cms_Logger::log(
            array(
                'LOG_ADMIN_UPDATE_CANCEL', (string)$pkg->version
            ), __CLASS__, __FUNCTION__
        );

        // redirecting
        Cms_Core::redirect('/open/admin-update/');
    }

    public function checknowAction()
    {
        $update = Cms_Config::getInstance()->modules->update;

        $client = new Zend_Rest_Client($update->address);

        # function: checkUpdaterVersion
        $client->checkUpdaterVersion(self::UPDATER_VERSION, $update->type);

        try {
            $result = $client->get();
        } catch (Zend_Exception $e) {
            restore_error_handler(); # bug?
            Cms_Dialog::getInstance()->construct(
                'MSG_ADMIN_UPDATE_UPDATE_SERVER_ERROR', Cms_Dialog::TYPE_ERROR
            );
        }

        $rt = $result->__toString();
        if ($rt !== 'welcome') {
            Cms_Dialog::getInstance()->construct(
                'MSG_ADMIN_UPDATE_SERVER_RETURNED_' . strtoupper($rt),
                Cms_Dialog::TYPE_ERROR
            );
        }

        unset($rt);

        // listOfAvaliableUpdates
        $client->listOfAvaliableUpdates(Cms_Version::VERSION, $update->type);
        try {
            $result = $client->get();
        } catch (Zend_Rest_Client_Result_Exception $e) {
            Cms_Dialog::getInstance()->construct(
                'MSG_ADMIN_UPDATE_UPDATE_SERVER_ERROR', Cms_Dialog::TYPE_ERROR
            );
        }

        // Update time
        Cms_Filemanager::fileWrite(CMS_TMP . 'packages/update.time', time());

        # Log
        Cms_Logger::log('LOG_ADMIN_UPDATE_CHECK', __CLASS__, __FUNCTION__);

        # List of updates
        $list = $result->__toString();
        if ($list === 'false') {
            Cms_Dialog::getInstance()->construct(
                'MSG_ADMIN_UPDATE_NO_NEW_PKG_AVAILIABLE',
                Cms_Dialog::TYPE_NOTICE,
                array('redirect' => '/open/admin-update/')
            );
        }

        # downloading list of updates
        $list = explode(',', $list);

        // Timeout change and package download
        $oldTimelimit = ini_get('max_execution_time');
        set_time_limit(0);
        foreach ($list as $v) {
            $this->_downloadPackage($v, $update->address, $update->type);
        }
        set_time_limit($oldTimelimit);

        # Final redirect
        Cms_Dialog::getInstance()->construct(
            'MSG_ADMIN_UPDATE_NEW_AVAILIABLE', Cms_Dialog::TYPE_SUCCESS,
            array('redirect' => '/open/admin-update/')
        );
    }

    public function installAction()
    {
        if (!isset($this->_input->id)) {
            Cms_Core::e404();
        }

        $pkg = $this->_getPackageInfo($this->_input->id);
        $file = $pkg->version . '.zip';

        # Prev check
        $prev = (string)$pkg->previous;
        if (!version_compare($prev, Cms_Version::VERSION, 'eq')) {
            Cms_Dialog::getInstance()->construct(
                array('MSG_ADMIN_UPDATE_UPDATE_PKG_BAD_PREV', $prev, Cms_Version::VERSION),
                Cms_Dialog::TYPE_ERROR
            );
        }

        # Package download
        $cfg = Cms_Config::getInstance()->modules->update;
        if (!$this->_downloadPackage($file, $cfg->address, $cfg->type)) {
            Cms_Dialog::getInstance()->construct(
                array('MSG_ADMIN_UPDATE_PKG_DWD_ERROR', implode('<br />', $this->_errors)),
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-update/')
            );
        }

        # Readable check
        $path = "{$this->_updDir}/{$file}";
        if (!Cms_Filemanager::fileExists($path, true)) {
            Cms_Dialog::getInstance()->construct(
                array('MSG_ADMIN_UPDATE_PKG_NOT_READABLE', $file),
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-update/')
            );
        }

        # Md5 check
        if (hash_file('md5', $path) !== (string)$pkg->md5) {
            Cms_Dialog::getInstance()->construct(
                array('MSG_ADMIN_UPDATE_PKG_WRONG_MD5', $file),
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-update/')
            );
        }

        # Making apply file
        Cms_Filemanager::fileWrite(
            "{$this->_updDir}/{$pkg->version}.apply", $uid = $this->_user->id
        );

        // Logging
        Cms_Logger::log(
            array('LOG_ADMIN_UPDATE_MARK', (string)$pkg->version), __CLASS__, __FUNCTION__
        );

        // redirect
        Cms_Dialog::getInstance()->construct(
            'MSG_ADMIN_UPDATE_PKG_WAIT_MARKED', Cms_Dialog::TYPE_INFO,
            array('redirect' => '/open/admin-update/', 'redirectManual' => true)
        );
    }

    protected function _downloadPackage($path, $server, $type)
    {
        $client = new Zend_Http_Client();
        $client->setUri($server)
            ->setMethod(Zend_Http_Client::POST)
            ->setConfig(
                array(
                    'maxredirects' => 0,
                    'timeout'      => 30,
                    'useragent'    => 'kkcms updater v' . self::UPDATER_VERSION
                )
            )->setParameterPost(
                array(
                    'getFile'    => 'true',
                    'kkcms-key'  => hash('md5', hash('crc32', CMS_HOST)),
                    'kkcms-type' => $type,
                    'kkcms-file' => $path,
                    'kkcms-ver'  => Cms_Version::VERSION,
                    'kkcms-user' => $this->_user->name
                )
            );

        $response = $client->request();
        if ($response->getStatus() !== 200) {
            $this->_errors[] = $response->getMessage();
            return false;
        }

        $data = $response->getBody();
        if (!strlen($data)) {
            $this->_errors[] = 'Empty package';
            return false;
        }

        $tmpPath = "{$this->_updDir}/{$path}";
        if (Cms_Filemanager::fileExists($tmpPath, true)) {
            if (hash_file('md5', $tmpPath) === hash('md5', $data)) {
                return true;
            }
        }
        return Cms_Filemanager::fileWrite($tmpPath, $data);
    }

    protected function _getPackageInfo($id)
    {
        $file = "{$this->_updDir}/{$id}.xml";
        if (!Cms_Filemanager::fileExists($file, true)) {
            Cms_Dialog::getInstance()->construct(
                'MSG_ADMIN_UPDATE_PACKAGE_NOT_FOUND',
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-update/')
            );
        }

        libxml_use_internal_errors(true);
        $i = simplexml_load_file($file);
        return (!count(libxml_get_errors())) ? $i : false;
    }
}

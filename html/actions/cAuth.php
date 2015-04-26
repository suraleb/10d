<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Templates
 * @package  Auth
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * @see Cms_Module_Site
 */
class cAuth extends Cms_Module_Site
{
    const AUTH_STATUS_BLOCKED = 0;
    const AUTH_STATUS_SUCCESS = 1;

    /**
     * Stores auth object
     *
     * @var Cms_Auth
     */
    protected $_auth;

    /**
     * Stores session object
     *
     * @var Cms_Session
     */
    protected $_session;

    /**
     * Access list
     *
     * @var array
     */
    protected $_accessList = array(
        'run'        => array('_ROLE' => '*', '_ACCESS' => '*'),
        'formAction' => array('_ROLE' => '*', '_ACCESS' => '*')
    );

    /**
     * Event list
     *
     * @var mixed
     */
    protected $_eventList = array(
        'run' => array('OnSiteAuthFailed', 'OnSiteAuthSuccess')
    );

    /**
     * Constructor
     *
     * @param bool $render
     * @return void
     */
    public function __construct($render = null)
    {
        parent::__construct($render);

        $this->_auth = Cms_Auth::getInstance();

        // attemp counter session object
        $this->_session = Cms_Session::getInstance();
        if (!isset($this->_session->loginAttemp)) {
            $this->_session->loginAttemp = 0;
        }
    }

    /**
     * Default action
     *
     * @return void
     */
    public function run()
    {
        // logout
        if ($this->_input->logout === 'true') {
            return $this->logout();
        }

        $this->login();
    }

    /**
     * Displays auth form
     *
     * @return void
     */
    public function formAction()
    {
        if ($this->_auth->hasIdentity()) {
            Cms_Core::redirect();
        }

        $out = $this->_tpl->render($this->getTemplate(__FUNCTION__));
        if (!$this->_render) {
            return $out;
        }

        $this->_layout->content = $out;
        echo $this->_layout->render();
    }

    /**
     * Login process
     *
     * @return void
     */
    protected function login()
    {
        if (empty($this->_input->email) || empty($this->_input->password)) {
            Cms_Core::redirect('/system/admin.html');
        }

        if (!$this->_input->isPost()) {
            Cms_Core::e403();
        }

        $result = $this->_login(
            $this->_input->email, $this->_input->_('password')
        );

        if ($result != self::AUTH_STATUS_SUCCESS) {
            // event for this situation
            $this->_events->OnSiteAuthFailed
                ->addParam('email', $this->_input->email)
                ->addParam('result', $result)
                ->execute();

            // redirect to th auth page with current status
            Cms_Core::redirect("/system/admin.html?status={$result}");
        }

        // cookie for admin
        if (Cms_Core::getCookie('access_acp') !== 'granted'
            && $this->_user->hasAcl(CMS_ACCESS_ACP_VIEW)) {
            Cms_Core::setCookie('access_acp', 'granted');
        }

        // event for this situation
        $this->_events->OnSiteAuthSuccess
            ->addParam('email', $this->_input->email)
            ->addParam('result', $result)
            ->execute();

        // redirecting
        $redirect = '/open/admin-index/';
        if (!isset($_POST['cms_redirect_to_admin'])) {
            $redirect = '/';
            if (!empty($this->_input->referer)) {
                $redirect = '/' . ltrim(urldecode($this->_input->referer), '/');
            }
        }

        Cms_Core::redirect($redirect);
    }

    /**
     * Redirects after logout
     *
     * @return void
     */
    public function logout()
    {
        $this->_logout();

        // redirect check
        if (isset($_SERVER['HTTP_REFERER'])
            && strpos($_SERVER['HTTP_REFERER'], 'open/admin') === false
            && strpos($_SERVER['HTTP_REFERER'], CMS_HOST) !== false) {
            Cms_Core::redirect($_SERVER['HTTP_REFERER']);
        }
        Cms_Core::redirect();
    }

    /**
     * Clears use auth data and redirects
     *
     * @return cAuth
     */
    protected function _logout()
    {
        // session cleanup
        $this->_auth->clearIdentity();
        return $this;
    }

    /**
     * User auth check
     *
     * @param string $email
     * @param string $password
     * @return int
     */
    public function _login($email, $password)
    {
        // to be sure that we are not authorized
        if ($this->_auth->hasIdentity()) {
            return self::AUTH_STATUS_SUCCESS;
        }

        // auth status check
        if ($this->_session->getNamespace()->isLocked() || Cms_Firewall::ipWasBlocked()) {
            return self::AUTH_STATUS_BLOCKED;
        }

        // security check
        if ($this->_session->loginAttemp > 2) {
            $config = Cms_Config::getInstance()->cms->security->auth;
            switch ($config->level) {
                case 'firewall':
                    Cms_Firewall::ipBlock();
                    $this->_logout();
                    return self::AUTH_STATUS_BLOCKED;

                case 'time':
                    $time = abs(intval($config->{'lock_time'}));
                    $this->_session->getNamespace()->setExpirationSeconds($time ? $time : 120);
                    $this->_session->getNamespace()->lock();
                    return self::AUTH_STATUS_BLOCKED;

                default:
                    return self::AUTH_STATUS_BLOCKED;
            }
        }

        $result = $this->_auth->authenticate($email, $password);
        if (!$result->isValid()) {
            $this->_session->loginAttemp++;
            return $result->getCode();
        }

        $this->_session->loginAttemp = 0;

        // activity update
        $model = new Cms_Model_User();
        $model->import(Cms_User_Recognize::factory($this->_auth))
            ->setActivity(Cms_Date::now())
            ->save();

        return self::AUTH_STATUS_SUCCESS;
    }
}

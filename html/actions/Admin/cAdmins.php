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

class Admin_cAdmins extends Cms_Module_Admin
{
    const VERSION = '0.4';

    protected $_accessList = array(
        'run' => array(
            '_ACCESS' => CMS_ACCESS_ADMINS_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'newAction' => array(
            '_ACCESS' => CMS_ACCESS_ADMINS_NEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'saveAction' => array(
            '_ACCESS' => CMS_ACCESS_ADMINS_NEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'editselfAction' => array(
            '_ACCESS' => CMS_ACCESS_ADMINS_EDIT_SELF,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'editAction' => array(
            '_ACCESS' => CMS_ACCESS_ADMINS_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'updateAction' => array(
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            ),
            '_ACCESS' => array(
                CMS_ACCESS_ADMINS_EDIT, CMS_ACCESS_ADMINS_EDIT_SELF
            )
        ),

        'removeAction' => array(
            '_ACCESS' => CMS_ACCESS_ADMINS_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        )
    );

    /**
     * Default form
     *
     * @return void
     */
    public function run()
    {
        $db = new Cms_Db_Users();
        $sdb = new Cms_Db_Static();
        $gdb = new Cms_Db_Gallery();

        $query = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                array('u' => $db->getTableName()),
                array('id', 'name', 'role', 'access', 'mail', 'activity')
            )->joinLeft(
                array('s' => $sdb->getTableName()),
                's.user_id = u.id',
                new Zend_Db_Expr('COUNT(DISTINCT s.id) AS count_static')
            )->joinLeft(
                array('g' => $gdb->getTableName()),
                'g.user_id = u.id',
                new Zend_Db_Expr('COUNT(DISTINCT g.id) AS count_gallery')
            );

        if (!$this->_user->hasAcl(CMS_ACCESS_ADMINS_VIEW_OTHERS)) {
            $query->where('u.id = ?', $this->_user->id);
        } else {
            $query->group('u.id');
        }

        $this->_tpl->entries = $db->fetchAll($query);

        $this->_layout->content = $this->_tpl->render(
            $this->getTemplate(__FUNCTION__)
        );

        echo $this->_layout->render();
    }

    /**
     * New account form
     *
     * @return void
     */
    public function newAction()
    {
        $this->_tpl->access_list = $this->_accessFileToArray();

        $this->_layout->content = $this->_tpl->render(
            $this->getTemplate(__FUNCTION__)
        );

        echo $this->_layout->render();
    }

    /**
     * Self edit
     *
     * @return void
     * @see editAction()
     */
    public function editselfAction()
    {
        $this->_input->id = $this->_user->id;
        $this->editAction();
    }

    /**
     * Form for update
     *
     * @return void
     */
    public function editAction()
    {
        $id = intval($this->_input->id);

        $db = new Cms_Db_Users();

        $entry = $db->getById($id);
        if (!$entry) {
            $dialog->construct(
                'MSG_ADMIN_ADMINS_ERROR_EXISTS',
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-admins/')
            );
        }

        if ($entry->godlike && !$this->_user->godlike) {
            Cms_Dialog::getInstance()->construct(
                'MSG_ADMIN_DIALOG_HAVENT_ACCESS',
                Cms_Dialog::TYPE_NOTICE
            );
        }

        $this->_tpl->access_list = $this->_accessFileToArray();

        $this->_tpl->entry = $entry->toArray();
        $this->_tpl->entry['access'] = explode(',', $this->_tpl->entry['access']);

        $this->_layout->content = $this->_tpl->render(
            $this->getTemplate(__FUNCTION__)
        );

        echo $this->_layout->render();
    }

    /**
     * Update account
     *
     * @return void
     * @see saveAction()
     */
    public function updateAction()
    {
        $this->saveAction(true);
    }

    /**
     * Creates new account or updates existsing one
     *
     * @param bool $upd (Default: false)
     * @return void
     */
    public function saveAction($upd = false)
    {
        $dialog = Cms_Dialog::getInstance();
        if (empty($this->_input->user_name)) {
            $dialog->construct(
                'MSG_ADMIN_ADMINS_ERROR_EMPTY_NAME',
                Cms_Dialog::TYPE_ERROR
            );
        }

        $val = new Zend_Validate_EmailAddress();
        if (!$val->isValid($this->_input->user_mail)) {
            $dialog->construct(
                'MSG_ADMIN_ADMINS_ERROR_EMAIL',
                Cms_Dialog::TYPE_ERROR
            );
        }

        // user manager
        $db = new Cms_Db_Users();

        # Exist check
        if (!$upd) {
            if (count($db->getByMail($this->_input->user_mail))) {
                $dialog->construct(
                    'MSG_ADMIN_ADMINS_EXISTS',
                    Cms_Dialog::TYPE_ERROR
                );
            }
        }

        // draft for insert array
        $array = array(
            'name'  => $this->_input->user_name,
            'role'  => $this->_input->user_role,
            'mail'  => $this->_input->user_mail
        );

        // password check
        $skipPwd = false;
        if (empty($this->_input->user_password)
            || empty($this->_input->user_password_re)) {
            if (!$upd) {
                $dialog->construct(
                    'MSG_ADMIN_ADMINS_EMPTY_PWD',
                    Cms_Dialog::TYPE_ERROR
                );
            } else {
                $skipPwd = true;
            }
        }

        // password mach check
        if (!$skipPwd) {
            if ($this->_input->user_password !== $this->_input->user_password_re) {
                $dialog->construct(
                    'MSG_ADMIN_ADMINS_ERROR_PWDMATCH',
                    Cms_Dialog::TYPE_ERROR
                );
            }

            if (Cms_Functions::strlen($this->_input->user_password) < 6) {
                $dialog->construct(
                    'MSG_ADMIN_ADMINS_ERROR_PWDLEN',
                    Cms_Dialog::TYPE_ERROR
                );
            }

            // salt
            $array['password_salt'] = Cms_Functions::passwordGenerateSalt(3);

            // password
            $array['password'] = Cms_Functions::passwordEncode(
                $this->_input->user_password, $array['password_salt']
            );
        }

        if ($upd) {
            $id = intval($this->_input->id);

            // account check
            $entry = $db->getById($id);
            if (!$entry) {
                $dialog->construct(
                    'MSG_ADMIN_ADMINS_ERROR_EXISTS',
                    Cms_Dialog::TYPE_ERROR
                );
            }

            // role check and set
            if (empty($array['role']) && !empty($entry->role)) {
                $array['role'] = $entry->role;
            }

            // access set
            $array['access'] = $entry->access;
        }

        // access check
        if (!isset($id) || $id != $this->_user->id) {
            if (!is_array($this->_input->user_access)) {
                $this->_input->user_access = array();

                // checks if we are updating guest account
                if ($array['role'] != CMS_USER_GUEST) {
                    if ($upd && !$entry->godlike) {
                        $dialog->construct(
                            'MSG_ADMIN_ADMINS_ERROR_EMPTY_ACCESS',
                            Cms_Dialog::TYPE_ERROR
                        );
                    }
                }
            }

            // access build if needed
            $access = '';
            foreach ($this->_input->user_access as $v) {
                if ($this->_user->hasAcl($v)) {
                    $access .= $v . ',';
                }
            }
            $array['access'] = rtrim($access, ',');
        }

        // new account
        if (!$upd) {
            // db insert
            $db->insert($array);

            // log
            Cms_Logger::log(
                array('LOG_ADMIN_ADMINS_NEW', $array['name']),
                __CLASS__, __FUNCTION__
            );

            // redirect
            $dialog->construct(
                'MSG_ADMIN_ADMINS_ADDED',
                Cms_Dialog::TYPE_SUCCESS,
                array('redirect' => '/open/admin-admins/')
            );
        }

        // default role
        if ($entry->godlike) {
            $array['role'] = CMS_USER_ADMIN;
        }

        // update
        $entry->setFromArray($array)->save();

        // log
        Cms_Logger::log(
            array('LOG_ADMIN_ADMINS_UPDATE', $entry->name, $entry->id),
            __CLASS__, __FUNCTION__
        );

        // redirect
        $dialog->construct(
            'MSG_ADMIN_ADMINS_UPDATED',
            Cms_Dialog::TYPE_SUCCESS,
            array('redirect' => '/open/admin-admins/')
        );
    }

    /**
     * Removes member account
     *
     * @return void
     */
    public function removeAction()
    {
        $dialog = Cms_Dialog::getInstance();

        // guest account
        $id = intval($this->_input->id);
        if ($id == 0) {
            $dialog->construct(
                'MSG_ADMIN_ADMINS_REMOVE_GUEST',
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-admins/')
            );
        }

        $db = new Cms_Db_Users();

        // account exists
        $entry = $db->getById($id);
        if (!$entry) {
            $dialog->construct(
                'MSG_ADMIN_ADMINS_ERROR_EXISTS',
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-admins/')
            );
        }

        // is user god
        if ($entry->godlike) {
            $dialog->construct(
                'MSG_ADMIN_ADMINS_REMOVE_GOD',
                Cms_Dialog::TYPE_ERROR
            );
        }

        // self remove
        if ($entry->id == $this->_user->id) {
            $dialog->construct(
                'MSG_ADMIN_ADMINS_REMOVE_YOURSELF',
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-admins/')
            );
        }

        // storing username
        $un = $entry->name;

        // removing entry
        $entry->delete();

        // updating static
        $db = new Cms_Db_Static();
        $db->update(array('user_id' => 0), "user_id = {$id}");

        // updating gallery
        $db = new Cms_Db_Gallery();
        $db->update(array('user_id' => 0), "user_id = {$id}");

        // log
        Cms_Logger::log(
            array('LOG_ADMIN_ADMINS_REMOVE', $un, $id),
            __CLASS__, __FUNCTION__
        );

        // redirect
        Cms_Dialog::getInstance()->construct(
            'MSG_ADMIN_ADMINS_REMOVED',
            Cms_Dialog::TYPE_SUCCESS,
            array('redirect' => '/open/admin-admins/')
        );
    }

    /**
     * Parse access file to array
     *
     * @return array
     */
    private function _accessFileToArray()
    {
        $defined = preg_grep(
            '~CMS_ACCESS~',
            array_keys(
                get_defined_constants()
            )
        );

        $list = array();
        foreach ($defined as $v) {
            if ($v == 'CMS_ACCESS_LOADED') {
                continue;
            }
            $key = preg_replace('~^CMS_ACCESS_(.+?)(_.+)?$~', '\1', $v);
            $list[$key][] = constant($v);
        }
        return $list;
    }
}

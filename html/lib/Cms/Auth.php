<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Data
 * @package  Library
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * @see Zend_Auth
 */
class Cms_Auth extends Zend_Auth
{
    /**
     * Current adapter
     * @var Zend_Auth_Adapter_DbTable
     */
    protected $_adapter;

    /**
     * Constructor
     *
     * @return void
     */
    protected function __construct()
    {
        $udb = new Cms_Db_Users();

        $this->_adapter = new Zend_Auth_Adapter_DbTable();
        $this->_adapter->setTableName($udb->getTableName())
            ->setIdentityColumn(Cms_Db_Users::AUTH_IDENTITY_COLUMN)
            ->setCredentialColumn(Cms_Db_Users::AUTH_CREDENTIAL_COLUMN)
            ->setCredentialTreatment(Cms_Db_Users::AUTH_CREDENTIAL_TREATMENT);
    }

    /**
     * Authenticates against the supplied adapter
     *
     * @param  $login
     * @param  $password
     * @return Zend_Auth_Result
     */
    public function authenticate($login, $password)
    {
        $result = parent::authenticate(
            $this->_adapter->setIdentity($login)->setCredential($password)
        );

        // only 30 min for the unused session
        if ($result->isValid()) {
            Zend_Session::rememberMe(10800); // 3h
        }

        return $result;
    }

    /**
     * Returns own instance
     *
     * @return Cms_Auth
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}

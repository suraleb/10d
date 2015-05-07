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
 * @package  Library
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

abstract class Cms_Module_Abstract
{
    /**
     * access list for a module
     * @var array
     */
    protected $_accessList = array();

    /**
     * event list for a module
     * @var array
     */
    protected $_eventList = array();

    /**
     * account object
     * @var Cms_Template
     */
    protected $_tpl;

    /**
     * account object
     * @var Cms_Input
     */
    protected $_input;

    /**
     * account object
     * @var Cms_Layout
     */
    protected $_layout;

    /**
     * account object
     * @var Cms_Template_User
     */
    protected $_user;

    /**
     * events object
     * @var Cms_EventManager
     */
    protected $_events;

    /**
     * messages object
     * @var Cms_Message_Manager
     */
    protected $_messageManager;

    /**
     * Constructor
     * @return void
     */
    public function __construct()
    {
        // current user
        $actor = Cms_User_Recognize::factory(Cms_Auth::getInstance());
        $actor->setReadOnly(true);

        Zend_Registry::set('actor', $actor);

        unset($actor);

        // default params
        $this->_events = Cms_EventManager::getInstance();
        $this->_tpl    = Cms_Template::getInstance();
        $this->_input  = Cms_Input::getInstance();
        $this->_layout = Cms_Layout::getInstance();

        // current user and we should add user to the template
        $this->_user = $this->_tpl->user = Zend_Registry::get('actor');

        // Message manager
        $msgMgr = new Cms_Message_Manager();
        $msgMgr->addWriter(new Cms_Message_Writer_Gritter($this->_tpl))
            ->setStorage(new Cms_Message_Storage_Session());

        $this->_messageManager = $msgMgr;

        // template settings
        $this->_tpl->addHelperPath('Cms/View/Helper', 'Cms_View_Helper_');
        $this->_tpl->setScriptPath(CMS_ROOT . 'templates');
    }

    public function __destruct()
    {
        $this->_messageManager->dispatch();
    }

    /**
     * Adds events for the currect action
     *
     * @return void
     */
    protected function _applyEvents($action)
    {
        // adding events for the current action
        if (isset($this->_eventList[$action])) {
            foreach ($this->_eventList[$action] as $id) {
                if (!class_exists($id, false)) {
                    $eventPath = CMS_ROOT . "events/{$id}.php";
                    if (!Cms_Filemanager::fileExists($eventPath, true)) {
                        throw new Cms_Exception(
                            "Executable file for the '{$id}' event doesn't exist"
                        );
                    }
                    require $eventPath;
                }
                $this->_events->add(new $id);
            }
        }
    }

    /**
     * Calls function for a module
     *
     * @param string $fnc
     * @param mixed $args
     */
    abstract public function __call($fnc, $args);

    /**
     * Finds template by name
     *
     * @param string $tpl
     */
    abstract public function getTemplate($tpl);
}

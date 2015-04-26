<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category engine
 * @package  Message
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * @see Cms_Message_Writer_Abstract
 */
class Cms_Message_Writer_Gritter extends Cms_Message_Writer_Abstract
{
    /**
     * Layout instance
     * @var Zend_View
     */
    protected $_view;

    /**
     * Constructor
     *
     * @param Zend_Layout $layout
     * @return void
     */
    public function __construct(Zend_View $view)
    {
        $this->_view = $view;
    }

    /**
     * Processes the current message according own logic
     *
     * @return void
     */
    public function handle()
    {
        $this->_view->getHelper('MsgGritter')->append(
            $this->_message->getMessage(), array('status' => $this->_message->getStatus())
        );
    }
}

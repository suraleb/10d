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

class Cms_View_Helper_MsgGritter extends Zend_View_Helper_Placeholder_Container_Standalone
{
    /**
     * Registry key under which container registers itself
     * @var string
     */
    protected $_regKey = __CLASS__;

    /**
     * Return msgGritter object
     *
     * Returns msgGritter helper object; optionally, allows specifying
     *
     * @param  string $content Message contents
     * @param  string $placement Append or prepend
     * @param  array  $attributes
     * @return Cms_View_Helper_MsgGritter
     */
    public function msgGritter($content = null, $placement = 'APPEND', $attributes = array())
    {
        if ((null !== $content) && is_string($content)) {
            $action = strtolower($placement);
            $this->{$action}($content, $attributes);
        }
        return $this;
    }

    /**
     * Override prepend to enforce message creation
     *
     * @param mixed $value
     * @return void
     */
    public function prepend($value, $attributes)
    {
        return $this->getContainer()->prepend(array($value, $attributes));
    }

    /**
     * Override append to enforce message creation
     *
     * @param  mixed $value
     * @return void
     */
    public function append($value, $attributes)
    {
        return $this->getContainer()->append(array($value, $attributes));
    }

    /**
     * Create string representation of placeholder
     *
     * @return string
     */
    public function toString()
    {
        // resorting
        $this->getContainer()->ksort();

        // escaping and building the list
        $messages = array();
        foreach ($this->getContainer() as $msg) {
            $msg[0] = Cms_Functions::escapeJs($msg[0]);
            $messages[] = $msg;
        }

        // building html output
        $view = Cms_Template::getInstance();
        $view->entries = $messages;
        return $view->render('/common/msg-gritter.phtml');
    }
}

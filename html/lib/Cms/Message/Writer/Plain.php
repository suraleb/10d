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
class Cms_Message_Writer_Plain extends Cms_Message_Writer_Abstract
{
    public function handle()
    {
        echo $this->_message->getStatus() . ': ' .
             $this->_message->getMessage() . "\n";
    }
}

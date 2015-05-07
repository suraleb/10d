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

require_once 'ThirdParty/HTMLPurifier/HTMLPurifier.standalone.php';
require_once 'ThirdParty/HTMLPurifier/Filter/Pagebreak.php';

/**
 * @see Zend_Filter_Interface
 */
class Cms_Filter_Purifier implements Zend_Filter_Interface
{
    /**
     * Stores original object
     * @var HTMLPurifier
     */
    protected $_purifier = null;

    /**
     * Uses HTMLPurifier to clean a value
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        // HTMLPurifier instance
        if (null === $this->_purifier) {
            // default config
            $config = HTMLPurifier_Config::createDefault();

            // the pagebreak filter
            $config->set('Filter.Custom', array(new HTMLPurifier_Filter_Pagebreak()));

            // links should have the _blank target
            $config->set('Attr.AllowedFrameTargets', array('_blank'));

            // instance itself
            $this->_purifier = new HTMLPurifier($config);
        }

        // cleanup
        return $this->_purifier->purify($value);
    }
}

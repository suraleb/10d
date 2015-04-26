<?php
/**
 * kkCms: Content Management System
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
 * @version  $Id$
 */

interface Cms_Search_Interface
{
    /**
     * Constructor
     */
    public function __construct();

    /**
     * Adds document into index
     */
    public function docAdd();

    /**
     * Updates database index
     * @param int $id
     * @see Cms_Search_Abstract::_docUpdate()
     */
    public function docUpdate($id);

    /**
     * Sets variable map for index fields
     * @param array $data
     */
    public function setHash(array $data);
}

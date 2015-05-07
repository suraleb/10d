<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category kkcms
 * @package  Library
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * class with a version information
 */
final class Cms_Version
{
    /**
     * Current version number
     * @var string
     */
    const VERSION = '0.6.0b4';

    /**
     * Compares versions
     *
     * @param string $version
     * @return int
     */
    public static function compareVersion($version)
    {
        return version_compare($version, self::VERSION);
    }
}

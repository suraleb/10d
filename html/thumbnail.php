<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Image
 * @package  Engine
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

define('ALLOW_EXTERNAL', false);
define('FILE_CACHE_TIME_BETWEEN_CLEANS', 2629743);
define('FILE_CACHE_SUFFIX', '');
define('FILE_CACHE_DIRECTORY', dirname(__FILE__) . '/tmp/cache/thumbnails');

require dirname(__FILE__) . '/lib/ThirdParty/Timthumb/timthumb.php';

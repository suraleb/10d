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
 * @package  Bootstrap
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

require_once dirname(__FILE__) . '/lib/Cms/Common.php';

// Core init
Cms_Core::init();

// Input object
$input = Cms_Input::getInstance();

// Building class name and path
$name = Cms_Config::getInstance()->cms->default->action;
$prefix = null;
if (!empty($input->mdl)) {
    $module = preg_replace('~[^\w-]~', '', $input->mdl);
    if (!empty($module)) {
        $name = $module;
        if (($pos = strpos($module, '-')) !== false) {
            $module = str_replace('-', '_', $module);
            $prefix = ucfirst(strtolower(substr($module, 0, $pos)));
            $name = substr($module, $pos+1);
        }
        unset($pos);
    }
    unset($module);
}
$name = 'c' . ucfirst(strtolower($name));

// Loading module
$filePath  = CMS_ROOT . 'actions/' . (empty($prefix) ? '' : "{$prefix}/") . "{$name}.php";
if (!Cms_Filemanager::fileExists($filePath, true)) {
    Cms_Core::e404();
}

// Preloader
require_once CMS_ROOT . 'actions/cPreloader.php';
cPreloader::preload();

// Action
$action = 'run()';
if (!empty($input->action)) {
    if (preg_match('~^\w+$~', $input->action)) {
        $action = strtolower($input->action) . 'Action()';
    }
}

$class = empty($prefix) ? $name : "{$prefix}_{$name}";
unset($input, $prefix, $name);

// Calling class
require_once $filePath;
if (!class_exists($class, false)) {
    throw new Cms_Exception("Class '{$class}' doesn't exist");
}
call_user_func(array(new $class(!CMS_CLI), $action));

Cms_Core::shutdown();

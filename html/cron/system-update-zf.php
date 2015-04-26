#!/usr/bin/php

<?php
/**
 * @author Kanstantsin A Kamkou
 * @link kkamkou at gmail dot com
 * @version $Id$
 */

define('CMS_CLI', true);

require_once dirname(__FILE__) . '/../lib/Cms/Common.php';

$path = CMS_TMP . 'packages/';

// Zend Framework update
$list = glob("{$path}ZendFramework-*.zip");
if (!count($list)) {
    Cms_Core::log('No "Zend Framework" updates awaiting installation were found');
    exit();
}

$tmpDir = sys_get_temp_dir();
$baseName = basename($list[0], '.zip');

Cms_Core::log("Zend Framework has a new update in the '{$baseName}' file");

$tmpFreeSpace = disk_free_space($tmpDir);
if (!$tmpFreeSpace) {
    Cms_Core::log('No info about free space for the temp folder was found');
} else {
    if ($tmpFreeSpace < filesize($list[0]) * 5) {
        alert('There is no free space to unpack the "Zend Framework" archive');
    }
}

// unzip
try {
    $zip = new Zend_Filter_Decompress(
        array('adapter' => 'Zip', 'options' => array('target' => $tmpDir))
    );
    $zip->filter($list[0]);
} catch(Zend_Filter_Exception $e) {
    alert($e->getMessage());
}

$tempZendDir = CMS_ROOT . 'lib/_Zend';
if (!Cms_Filemanager::dirExists($tempZendDir)
    && !Cms_Filemanager::dirCreate($tempZendDir)) {
    alert('Can\'t create a new "Zend Framework" folder');
}

if (!Cms_Filemanager::dirCopyRecursive("{$tmpDir}/{$baseName}/library/Zend", $tempZendDir)) {
    alert('Can\'t copy content of the "Zend Framework" to the lib folder');
}

if (!rename(CMS_ROOT . 'lib/Zend', CMS_ROOT . 'lib/_Zend_' . date('dmy'))) {
    alert('Can\'t rename the current "Zend Framework" folder');
}

if (!rename($tempZendDir, CMS_ROOT . 'lib/Zend')) {
    alert('Can\'t rename the new "Zend Framework" folder');
}

if (!Cms_Filemanager::dirRemoveRecursive("{$tmpDir}/{$baseName}")) {
    alert('Can\'t cleanup temp information for the "Zend Framework" archive');
}

Cms_Filemanager::fileUnlink($list[0]);

Cms_Core::log('Zend Framework update is completed');

function alert($msg)
{
    Cms_Core::log($msg, Zend_Log::WARN);
    exit(1);
}

exit();

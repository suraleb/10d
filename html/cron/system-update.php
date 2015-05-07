#!/usr/bin/php

<?php
/**
 * @author Kanstantsin A Kamkou
 * @link kkamkou at gmail dot com
 */

define('CMS_CLI', true);

require_once dirname(__FILE__) . '/../lib/Cms/Common.php';

$path = CMS_TMP . 'packages/';

$list = glob("{$path}*.apply");
if (!count($list)) {
    Cms_Core::log('No updates awaiting installation were found');
    exit();
}

foreach ($list as &$v) {
    $v = basename($v, '.apply');
}

usort($list, 'version_compare');

$lng = Cms_Translate::getInstance();
$lng->addTranslation('en');

foreach ($list as $v) {
    $pkg = "{$path}{$v}.zip";

    // fetch user id from the file
    $userId = intval(Cms_Filemanager::fileRead("{$path}{$v}.apply"));

    // archive check
    if (!Cms_Filemanager::fileExists($pkg, true)) {
        alert(sprintf($lng->_('MSG_ADMIN_UPDATE_PKG_WRONG_ARCHIVE'), $pkg));
    }

    // unzip
    try {
        $zip = new Zend_Filter_Decompress(
            array('adapter' => 'Zip', 'options' => array('target' => CMS_ROOT))
        );
        $zip->filter($pkg);
    } catch(Zend_Filter_Exception $e) {
        alert($e->getMessage());
    }

    // Post Install
    _postUpdateProcess();

    // Cleanup
    Cms_Filemanager::fileUnlink($pkg);
    Cms_Filemanager::fileUnlink(preg_replace('~zip$~', 'xml', $pkg));
    Cms_Filemanager::fileUnlink(preg_replace('~zip$~', 'apply', $pkg));

    // log action
    Cms_Logger::setCustomUserId($userId);
    Cms_Logger::log(array('LOG_ADMIN_UPDATE_SUCCESS', $v), 'Cron', 'Update');

    // Output
    Cms_Core::log("Job with package '{$v}' is completed");
}

// After update process
function _postUpdateProcess()
{
    _executeThenRemovePhp('update-pre.php');
    _executeThenRemoveSql('update-pre.sql');

    // access file rebuild
    if (!Cms_Core::accessRebuild()) {
        notice(__FUNCTION__, __LINE__);
    }

    // makes changes in cron
    _cronResetThenRemove();

    // cache cleanup
    $cache = new Cms_Cache_File();
    $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

    // css cleanup
    foreach (glob(CMS_TMP . 'cache/css-*') as $css) {
        Cms_Filemanager::fileUnlink($css);
    }

    // js cleanup
    foreach (glob(CMS_TMP . 'cache/js-*') as $js) {
        Cms_Filemanager::fileUnlink($js);
    }

    _executeThenRemovePhp('update-post.php');
    _executeThenRemoveSql('update-post.sql');
}

// resets cron settings according os
function _cronResetThenRemove()
{
    // current os
    $isUnix = (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN');

    $path = locateSystemFile('update-cron-' . ($isUnix ? 'unix' : 'win') . '.txt');
    if (!$path) {
        return false;
    }

    // engine id
    $hash = hash('crc32', CMS_ROOT);

    // unix
    if ($isUnix) {
        $return = _cronApplyUnix(Cms_Filemanager::fileRead($path), $hash);
    } else {
        $return = _cronApplyWin(file($path), $hash);
    }

    // cleanup
    if (!Cms_Filemanager::fileUnlink($path)) {
        notice(__FUNCTION__, __LINE__);
    }

    // windows
    return $return;
}

// windows cron data
function _cronApplyWin($list, $id)
{
    foreach ($list as $entry) {
        if ($entry{0} == '#') {
            continue;
        }
        $entry = str_replace(
            array('CMS_ROOT', 'CMS_ID'), array(CMS_ROOT, $id), $entry
        );
        system("schtasks {$entry}", $retval);
        if ($retval) {
            Cms_Core::log("Invalid cron entry: {$entry}", Zend_Log::WARN);
        }
    }
    return true;
}

// unix cron data
function _cronApplyUnix($content, $id)
{
    $content = str_replace(
        array('CMS_ROOT', 'CMS_ID'), array(CMS_ROOT, $id), $content
    );

    $header = "# kkcms id: {$id}";
    $footer = "# {$id}";

    $dataUnix = shell_exec('crontab -l 2>/dev/null');
    $dataUnix = preg_replace("~{$header}(.+?){$footer}~s", '', $dataUnix);
    $dataUnix .= "{$header}\n{$content}\n{$footer}";

    system("echo \"{$dataUnix}\" | crontab 2>/dev/null", $retval);
    if ($retval) {
        Cms_Core::log("Invalid cron data: {$dataUnix}", Zend_Log::WARN);
    }
    return true;
}

// run and unlink php file
function _executeThenRemovePhp($fileName)
{
    $php = locateSystemFile($fileName);
    if (!$php) {
        return false;
    }

    $code = file_get_contents($php);
    if (Cms_Functions::substr($code, 0, 5) == '<' . '?php') {
        $code = Cms_Functions::substr($code, 5);
    }

    eval($code);

    if (!Cms_Filemanager::fileUnlink($php)) {
        notice(__FUNCTION__, __LINE__);
    }

    Cms_Core::log("Execution of the '{$fileName}' is completed");

    return true;
}

// run and unlink sql file
function _executeThenRemoveSql($fileName)
{
    global $lng;

    // sql file
    $sql = locateSystemFile($fileName);
    if (!$sql) {
        return false;
    }

    $query = file($sql, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    try {
        foreach ($query as $v) {
            if (empty($v)) {
                continue;
            }
            Cms_Db::getInstance()->query($v);
        }
    } catch (Zend_Db_Exception $e) {
        alert(sprintf($lng->_('MSG_ADMIN_UPDATE_POST_SQL_ERROR'), $e->getMessage()));
    }

    if (!Cms_Filemanager::fileUnlink($sql)) {
        notice(__FUNCTION__, __LINE__);
    }

    Cms_Core::log("Execution of the '{$fileName}' is completed");

    return true;
}

function locateSystemFile($file)
{
    $path = CMS_TMP . "system/{$file}";
    if (!Cms_Filemanager::fileExists($path, true)) {
        Cms_Core::log("There is no '{$file}' found. So, skipping...");
        return false;
    }
    Cms_Core::log("Update process requires '{$file}'");
    return $path;
}

function alert($msg)
{
    Cms_Core::log($msg, Zend_Log::INFO);
    exit(1);
}

function notice($fnc, $line)
{
    Cms_Core::log("{$fnc} at {$line} line", Zend_Log::WARN);
}

exit();

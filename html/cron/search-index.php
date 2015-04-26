#!/usr/bin/php

<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Cli
 * @package  Search
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

define('CMS_CLI', true);

require_once dirname(__FILE__) . '/../lib/Cms/Common.php';

if (!isset($argv[1])) {
    _msgout();
}

$oo = false;

if (isset($argv[2])) {
    if ($argv[2] == '--optimize-only' || $argv[2] == '-o') {
        $oo = true;
    } else {
        _msgout();
    }
}

switch ($argv[1]) {
    case 'static':
        $db = new Cms_Db_Static();
        $query = $db->fetchAll(
            array(
                'active = ?' => '1',
                'hidden = ?' => '0',
                'system = ?' => '0'
            )
        );

        $sch = new Cms_Search_Static();
        if (!$oo) {
            foreach ($query->toArray() as $v) {
                $sch->setHash($v)->docUpdate($v['id']);
            }
        }
        $sch->optimize();
        break;

    default:
        echo "[Error] Script has no information about the '{$argv[1]}' database\n";
        exit(1);
        break;
}

$log = '[' . date('m-d-y H:i:s') . "] Search index for the '{$argv[1]}' "
     . 'database successfully ' . ($oo ? 'optimized' : 'created') . "\n";

echo $log;

/* Writing to the log */
$logPath = CMS_TMP . 'logs' . CMS_SEP . 'cron.log';
Cms_Filemanager::fileWrite(
    $logPath,
    ($log = Cms_Filemanager::fileRead($logPath) . $log)
);

function _msgout()
{
    global $argv;
    echo 'Usage: ' . $argv[0] . ' dbname [options]' . "\n" .
         'Databases: "static"' . "\n" .
         'Options:' . "\n\t" .
         '-o (--optimize-only)' . "\t" . 'Do not update database indexes.' .
            ' Apply optimization only.' . "\n";
    exit();
}

exit();

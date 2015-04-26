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

class Cms_Filemanager
{
    const CHMOD_FILE         = 0644;
    const CHMOD_FILE_READ    = 0604;
    const CHMOD_FILE_WRITE   = 0666;

    const CHMOD_FOLDER       = 0755;
    const CHMOD_FOLDER_READ  = 0705;
    const CHMOD_FOLDER_WRITE = 0777;

    /**
     * Checks if file exist and readable
     *
     * @param string $path
     * @param bool $isReadable (Default: false)
     * @return bool
     */
    public static function fileExists($path, $isReadable = false)
    {
        clearstatcache();
        if ($isReadable) {
            return is_file($path) && is_readable($path);
        }
        return is_file($path);
    }

    /**
     * Checks if folder exist
     *
     * @param string $path
     * @return bool
     */
    public static function dirExists($path)
    {
        clearstatcache();
        return is_dir($path);
    }

    /**
     * Creates folder in a filesystem
     *
     * @param string $path
     * @param int $chmod
     * @return bool
     */
    public static function dirCreate($path, $chmod = self::CHMOD_FOLDER_WRITE)
    {
        if (self::dirExists($path)) {
            return null;
        }
        $oldUmask = umask(0);
        $status = mkdir($path, $chmod);
        umask($oldUmask);
        return $status;
    }

    /**
     * Removes folder from a filesystem
     *
     * @param string $path
     * @return bool
     */
    public static function dirRemove($path)
    {
        return (!self::dirExists($path)) ? null : rmdir($path);
    }

    /**
     * Removes folder from a filesystem with content
     *
     * @param string $path
     * @return bool
     */
    public static function dirRemoveRecursive($path)
    {
        if (is_file($path)) {
            return self::fileUnlink($path);
        }

        $dh = opendir($path);
        while (false !== ($file = readdir($dh))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            self::dirRemoveRecursive("{$path}/{$file}");
        }
        closedir($dh);

        return self::dirRemove($path);
    }

    /**
     * Copy not only files, but folders too. See copy() description for details
     *
     * @param string $src
     * @param string $dest
     * @return bool
     */
    public static function dirCopyRecursive($src, $dest)
    {
        if (!self::dirExists($src) && !self::fileExists($src)) {
            return false;
        }

        if (is_file($src)) {
            return copy($src, $dest);
        }

        $src = realpath($src);

        if (substr($dest, 0, strlen($src)) == $src) {
            return false;
        }

        $error = false;
        if (!self::dirExists($dest) && !self::dirCreate($dest)) {
            $error = true;
        }

        $dh = opendir($src);
        while (false !== ($f = readdir($dh))) {
            if ($f == '.' || $f == '..') {
                continue;
            }

            if (!self::dirCopyRecursive("{$src}/{$f}", "{$dest}/{$f}")) {
                $error = true;
            }
        }
        closedir($dh);

        return !$error;
    }

    /**
     * Reads file
     *
     * @param string $file
     * @return mixed
     */
    public static function fileRead($file)
    {
        if (!self::fileExists($file)) {
            return null;
        }

        if (!is_readable($file)) {
            if (!self::chmodSet($file)) {
                return false;
            }
        }

        if (filesize($file) == 0) {
            return '';
        }

        if (($f = fopen($file, 'rb')) === false) {
            return false;
        }
        flock($f, LOCK_SH);
        $data = fread($f, filesize($file));
        fclose($f);
        return $data;
    }

    public static function fileWrite($file, &$content)
    {
        if (!self::fileExists($file)) {
            if (!self::fileCreate($file, self::CHMOD_FILE_WRITE)) {
                return null;
            }
        }

        if (!is_writable($file)) {
            if (!self::chmodSet($file, self::CHMOD_FILE_WRITE)) {
                return false;
            }
        }

        if (($f = fopen($file, 'r+b')) === false) {
            return false;
        }

        flock($f, LOCK_EX);
        ftruncate($f, 0);
        $return = fwrite($f, $content);
        fclose($f);
        clearstatcache();
        return ($return !== false);
    }

    public static function fileCreate($path, $chmod = null)
    {
        if (self::fileExists($path)) {
            return null;
        }

        $oldUmask = umask(0);
        if (($file = fopen($path, 'a+b')) === false) {
            umask($oldUmask);
            return false;
        }
        fclose($file);
        umask($oldUmask);

        $fileExists = self::fileExists($path);
        if ($chmod === null) {
            return $fileExists;
        }
        return ($fileExists && self::chmodSet($path, $chmod));
    }

    public static function chmodSet($p, $chmod = self::CHMOD_FILE)
    {
        if (!self::fileExists($p) && !self::dirExists($p)) {
            return null;
        }
        $oldUmask = umask(0);
        $status = chmod($p, self::_octdec($chmod));
        umask($oldUmask);
        return $status;
    }

    public static function fileUnlink($path)
    {
        return (!self::fileExists($path)) ? null : unlink($path);
    }

    public static function fileCopy($source, $dest)
    {
        return (!self::fileExists($source)) ? null : copy($source, $dest);
    }

    public static function fileRename($source, $dest)
    {
        return (!self::fileExists($source)) ? null : rename($source, $dest);
    }

    public static function dirList($path, $depth = 0, $mask = '^(?!\.$|\.\.$).+$', $currentDepth = 0)
    {
        if (!self::dirExists($path)) {
            return null;
        }

        $list = array();

        $d = opendir($path);

        while (($file = readdir($d)) !== false) {
            if (!preg_match("~{$mask}~", $file)) {
                continue;
            }

            $newPath = $path . CMS_SEP . $file;

            if (self::dirExists($newPath)) {
                if ($depth > 0) {
                    if ($depth > $currentDepth) {
                        $list[$file] = self::dirList($newPath, $depth, $mask, ++$currentDepth);
                    }
                } else {
                    $list[$file] = self::dirList($newPath, $depth, $mask);
                }
            } else {
                $list[] = $file;
            }
        }

        closedir($d);

        return $list;
    }

    /**
     * Returns the formatted size
     *
     * @param  integer $size
     * @return string
     */
    public static function sizeToString($size)
    {
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        for ($i=0; $size >= 1024 && $i < 9; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . $sizes[$i];
    }

    /**
     * Sends file for download
     *
     * @var string $file
     * @var bool $exit (Default: true)
     * @return void
     */
    public static function fileToBrowser($file, $exit = true)
    {
        if (!self::fileExists($file, true)) {
            return null;
        }

        $fname = "{$_SERVER['HTTP_HOST']}_-_" . basename($file);
        $params = array(
            'Content-Transfer-Encoding' => 'binary',
            'Content-Disposition'       => "attachment; filename=\"{$fname}\"",
            'Content-Length'            => filesize($file),
            'Content-Description'       => 'File Transfer',
            'Pragma'                    => 'no-cache',
            'Expires'                   => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified'             => gmdate('D, d M Y H:i:s') . ' GMT'
        );

        // cache-control
        header('Cache-Control: no-cache, must-revalidate');
        header('Cache-Control: post-check=0,pre-check=0', false);
        header('Cache-Control: max-age=0', false);

        // force dialog
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');

        // headers
        foreach ($params as $k => $v) {
            header("{$k}:{$v}");
        }

        // cleanup
        ob_clean();
        flush();

        $ret = readfile($file);
        if ($exit) {
            Cms_Core::shutdown(true);
        }

        return $ret;
    }

    /**
     * Detect an octal string and return its octal value for file permission ops
     * otherwise return the non-string (assumed octal or decimal int already)
     *
     * @param string $val The potential octal in need of conversion
     * @return int
     */
    protected static function _octdec($val)
    {
        if (is_string($val) && decoct(octdec($val)) == $val) {
            return octdec($val);
        }
        return $val;
    }
}

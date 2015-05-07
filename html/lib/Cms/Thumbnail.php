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

/**
 * Image-resize functions
 */
class Cms_Thumbnail
{
    /**
     * Mime types
     * @var array
     */
    private $_mime = array(
        'image/gif'   => 'gif',
        'image/jpeg'  => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png'   => 'png'
    );

    /**
     * File path
     * @var string
     */
    private $_file;

    /**
     * Thumb options
     * @var array
     */
    private $_options = array(
        'crop'         => false,
        'nameTemplate' => '%s_%dx%d',
        'width'        => 150,
        'height'       => 150,
        'savePath'     => null,
        'qualityJpg'   => 90,
        'qualityPng'   => 0
    );

    /**
     * Constructor
     *
     * @param  string  $f File path
     * @param  integer $w Width
     * @param  integer $h Height
     * @return void
     */
    public function __construct($f, $w = null, $h = null)
    {
        $this->_file = $f;

        if ($w !== null) {
            $this->setThumbWidth($w);
        }

        if ($h !== null) {
            $this->setThumbHeight($h);
        }
    }

    /**
     * Sets default thumb width
     *
     * @param  integer       $i
     * @return Cms_Thumbnail
     */
    public function setThumbWidth($i)
    {
        $i = abs(intval($i));
        if ($i > 0) {
            $this->setOption('width', $i);
        }
        return $this;
    }

    /**
     * Sets default thumb height
     *
     * @param  integer       $i
     * @return Cms_Thumbnail
     */
    public function setThumbHeight($i)
    {
        $i = abs(intval($i));
        if ($i > 0) {
            $this->setOption('height', $i);
        }
        return $this;
    }

    /**
     * Sets option
     *
     * @param  string        $n
     * @param  string        $v
     * @return Cms_Thumbnail
     */
    public function setOption($n, $v)
    {
        if (!array_key_exists($n, $this->_options)) {
            return false;
        }
        $this->_options[$n] = $v;
        return $this;
    }

    /**
     * Saves thumb into the file
     *
     * @return bool
     */
    public function save()
    {
        return $this->proceed(true);
    }

    /**
     * Shows image with headers
     *
     * @return string
     */
    public function show()
    {
        return $this->proceed();
    }

    /**
     * Creates thumb and save or display it
     *
     * @param  bool  $save
     * @return mixed
     */
    protected function proceed($save = false)
    {
        // file exists check
        if (!Cms_Filemanager::fileExists($this->_file, true)) {
            return null;
        }

        // image information
        $info = getimagesize($this->_file);
        if (!$info || !isset($this->_mime[$info['mime']])) {
            throw new Cms_Exception(
                "Can't get image size for the '{$this->_file}' file"
            );
        }

        // thumb path
        $thFilename = $this->_thumbPath();

        // caching and checking tags
        if (!$save) {
            $fileMakeTime = filemtime($this->_file);

            if (Cms_Filemanager::fileExists($thFilename, true)
                && filemtime($thFilename) >= $fileMakeTime) {

                // 1 month
                $expireTime = 60 * 60 * 24;

                // cache tags
                $eTag = "\"{$fileMakeTime}\"";

                // send headers
                header("Etag: {$eTag}");
                header("Content-type: {$info['mime']}");
                header("Cache-Control: private, max-age={$expireTime}");
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expireTime) . ' GMT');
                header('Pragma: public');

                // tags check
                if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $eTag)
                    || (isset($_SERVER['HTTP_IF_MATCH']) && $_SERVER['HTTP_IF_MATCH'] == $eTag)) {
                    Cms_Core::sndHeaderCode(304);
                    return null;
                }
                readfile($thFilename);
                return null;
            }
        }

        // resizing process
        $origWidth  = $info[0];
        $origHeight = $info[1];
        $dstX = $dstY = 0;

        if (!$this->_options['width']) {
            $newWidth  = $this->_options['width'] = floor(
                $origWidth * $this->_options['height'] / $origHeight
            );
            $newHeight = $this->_options['height'];
        } elseif (!$this->_options['height']) {
            $newWidth  = $this->_options['width'];
            $newHeight = $this->_options['height'] = floor(
                $origHeight * $this->_options['width'] / $origWidth
            );
        } elseif ($this->_options['crop']) {
            $scaleW = $this->_options['width'] / $origWidth;
            $scaleH = $this->_options['height'] / $origHeight;
            $scale = max($scaleW, $scaleH);
            $newWidth  = floor($origWidth * $scale);
            $newHeight = floor($origHeight * $scale);
            $dstX = floor(($this->_options['width'] - $newWidth) / 2);
            $dstY = floor(($this->_options['height'] - $newHeight) / 2);
        } else {
            $scaleW = $this->_options['width'] / $origWidth;
            $scaleH = $this->_options['height'] / $origHeight;
            $scale = min($scaleW, $scaleH);
            $newWidth  = $this->_options['width'] = floor($origWidth * $scale);
            $newHeight = $this->_options['height'] = floor($origHeight * $scale);
        }

        if (!$save) {
            if ($this->_options['width'] > $origWidth
                || $this->_options['height'] > $origHeight) {
                header("Content-type: {$info['mime']}");
                readfile($this->_file);
                return null;
            }
        }

        $imgType = $this->_mime[$info['mime']];
        if ($imgType == 'jpg') {
            $imgType = 'jpeg';
        }

        if (!function_exists("imagecreatefrom{$imgType}")) {
            throw new Cms_Exception("System hasn't function: 'imagecreatefrom{$imgType}'");
            return false;
        }

        // sometimes we have memory-limit exception
        try {
            $origImg = call_user_func("imagecreatefrom{$imgType}", $this->_file);
        } catch(Exception $e) {
            return false;
        }

        $tmpImg = imagecreatetruecolor($this->_options['width'], $this->_options['height']);

        imagecopyresampled(
            $tmpImg, $origImg, $dstX, $dstY, 0, 0, $newWidth,
            $newHeight, $origWidth, $origHeight
        );
        imagedestroy($origImg);

        if (!$save) {
            header("Content-type: {$info['mime']}");
        }

        if (isset($this->_options['quality' . ucfirst($imgType)])) {
            call_user_func(
                "image{$imgType}", $tmpImg, $thFilename,
                $this->_options['quality' . ucfirst($imgType)]
            );
        } else {
            call_user_func("image{$imgType}", $tmpImg, $thFilename);
        }

        if (!$save) {
            call_user_func("image{$imgType}", $tmpImg);
        }

        return imagedestroy($tmpImg);
    }

    /**
     * Creates thumb path
     *
     * @return string
     */
    protected function _thumbPath()
    {
        // save path
        $name = CMS_UPL . 'thumbs';
        if (isset($this->_options['savePath'])) {
            if (Cms_Filemanager::dirExists($this->_options['savePath'])) {
                $name = $this->_options['savePath'];
            }
        }

        // new name
        $pinfo = pathinfo($this->_file);
        return "{$name}/" . sprintf(
            $this->_options['nameTemplate'], $pinfo['filename'],
            $this->_options['width'], $this->_options['height']
        ) . ".{$pinfo['extension']}";
    }
}

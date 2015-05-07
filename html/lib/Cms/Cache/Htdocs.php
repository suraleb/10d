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

class Cms_Cache_Htdocs
{
    /**
     * List of files
     * @var array
     */
    protected $_list;

    /**
     * Type of file (css or js)
     * @var string
     */
    protected $_type;

    /**
     * Path to cached file
     * @var string
     */
    protected $_cachePath;

    /**
     * Headers to send to browser
     * @var array
     */
    protected $_headers;

    /**
     * Is our browser can gzip
     * @var bool
     */
    protected $_gziped = false;

    /**
     * Is our browser can deflate
     * @var bool
     */
    protected $_deflated = false;

    /**
     * Constructor
     *
     * @param string $list
     * @param string $type
     * @return void
     */
    public function __construct($list, $type)
    {
        $type = strtolower($type);
        if ($type != 'css' && $type != 'js') {
            Cms_Core::e404();
        }
        $this->_type = $type;

        $this->_list = explode(',', preg_replace('~[^\w.,-]~', '', $list));
        $listCount = count($this->_list);
        if (!$listCount) {
            Cms_Core::e404();
        }
        if ($listCount > 1) {
            $this->_list = array_unique($this->_list);
        }

        $_enc = '';
        if (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $list = array('gzip', 'x-gzip', 'deflate', 'x-deflate');
            foreach ($list as $v) {
                if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'], $v) !== false) {
                    $_enc = $v;
                }
            }
        }

        if (!empty($_enc)) {
            $this->_headers[] = array('Content-Encoding', $_enc);
            $this->_gziped = (stripos($_enc, 'gzip') !== false);
            $this->_deflated = (stripos($_enc, 'deflate') !== false);
        }

        if (!$this->_cached()) {
            $this->_buildCache();
        }

        $this->_headers[] = array('Content-Length', filesize($this->_cachePath));
    }

    /**
     * Gets content of cached file
     *
     * @return string/null
     */
    public function getContent()
    {
        if (!$this->_sendHeaders()) {
            return null;
        }
        return Cms_Filemanager::fileRead($this->_cachePath);
    }

    /**
     * Sends headers to a browser
     *
     * @return bool
     */
    protected function _sendHeaders()
    {
        $headers = array(
            'css' => 'text/css',
            'js'  => 'text/javascript'
        );

        foreach ($this->_headers as $v) {
            header("{$v[0]}: {$v[1]}");
        }

        $expireTime = 60 * 60 * 24 * 365; # 1 year

        $eTag = '"' . filemtime($this->_cachePath);
        if ($this->_gziped || $this->_deflated) {
            $eTag .= '-' . ($this->_deflated ? 'deflate' : 'gzip');
        }
        $eTag .= '"';

        header("Etag: {$eTag}");
        header("Content-type: {$headers[$this->_type]}; charset=utf-8");
        header("Cache-Control: private, max-age={$expireTime}");
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expireTime) . ' GMT');
        header('Pragma: public');

        if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $eTag)
            || (isset($_SERVER['HTTP_IF_MATCH']) && $_SERVER['HTTP_IF_MATCH'] == $eTag)) {
            Cms_Core::sndHeaderCode(304);
            header('Content-Length: 0');
            return false;
        }
        return true;
    }

    /**
     * If file already cached
     *
     * @return bool
     */
    protected function _cached()
    {
        $sum = null;
        foreach ($this->_list as $k => &$v) {
            // replacing "j." to "jquery." and "ju." to "jquery.ui-"
            $v = str_replace(
                array('j.', 'jui.'), array('jquery.', 'jquery.ui-'), $v
            );

            if (!preg_match("~\.{$this->_type}$~", $v)) {
                $v .= ".{$this->_type}";
            }

            $v = CMS_HTDOCS . ($this->_type == 'js' ? 'scripts' : 'styles') . "/{$v}";
            if (!Cms_Filemanager::fileExists($v)) {
                unset($this->_list[$k]);
                continue;
            }
            $sum .= hash_file('crc32', $v);
        }

        $this->_cachePath = CMS_TMP . "cache/{$this->_type}-" . hash('crc32', $sum);
        if ($this->_gziped || $this->_deflated) {
            $this->_cachePath .= '.' . ($this->_deflated ? 'df' : 'gz');
        }
        return Cms_Filemanager::fileExists($this->_cachePath, true);
    }

    /**
     * Creates cache for a file
     *
     * @return integer
     */
    protected function _buildCache()
    {
        $content = '';
        foreach ($this->_list as $v) {
            $content .= $this->{'_pack' . ucfirst($this->_type)}($v);
            $content .= "\n\n";
        }

        # Gzip
        if ($this->_gziped || $this->_deflated) {
            if (!empty($_SERVER['HTTP_USER_AGENT'])
                && (stripos($_SERVER['HTTP_USER_AGENT'], 'Opera') === false)) {
                if (
                    preg_match(
                        '~^Mozilla/4\.0 \(compatible; MSIE ([0-9]\.[0-9])~i',
                        $_SERVER["HTTP_USER_AGENT"],
                        $matches
                    )
                ) {
                    if (floatval($matches[1]) < 7) {
                        $content = str_repeat(' ', 2048) . "\r\n{$content}";
                    }
                }
            }
            $content = ($this->_deflated) ?
                gzdeflate($content, 7) : gzencode($content, 7, FORCE_GZIP);
        }
        return Cms_Filemanager::fileWrite($this->_cachePath, $content);
    }

    /**
     * Packs javascript
     *
     * @param string $file
     * @return string
     */
    protected function _packJs($file)
    {
        $baseName = basename($file);

        $js = Cms_Filemanager::fileRead($file);
        if ($baseName == 'jquery.js'
            || Cms_Config::getInstance()->cms->cache->disabled) {
            return $js;
        }

        $out = Cms_Minify_Uglify::minify($js, $baseName);
        if ($out === false) {
            $out = Cms_Minify_Js::minify($js);
        }
        return $out;
    }

    /**
     * Packs css
     *
     * @param string $file
     * @return string
     */
    protected function _packCss($f)
    {
        $out = Cms_Minify_Css::process(Cms_Filemanager::fileRead($f));
        $out = preg_replace_callback(
            '~url\(["\'](.+?)["\']\)~i',
            create_function(
                '$p',
                'if ($p[1]{0} !== "/" && $p[1]{0} !== ".") {
                    $p[1] = Cms_Template::getInstance()->imgUrl($p[1]);
                 } return \'url("\' . $p[1]  . \'")\';'
            ),
            $out
        );
        return $out;
    }
}

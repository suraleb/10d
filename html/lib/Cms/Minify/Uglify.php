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

class Cms_Minify_Uglify
{
    /**
     * Uglify URI
     * @var string
     */
    const UGLIFY_URL = 'http://marijnhaverbeke.nl/uglifyjs';

    /**
     * Packs javascript with Uglify
     *
     * @param  string &$code
     * @param  string $fileName (Default: null)
     * @return string/false
     */
    public static function minify(&$code, $fileName = null)
    {
        $config = Cms_Config::getInstance()->cms->uglify;
        if ($config->disabled) {
            return false;
        }

        $client = new Zend_Http_Client();
        $client->setMethod(Zend_Http_Client::POST)
            ->setUri(self::UGLIFY_URL)
            ->setConfig(array('maxredirects' => 0, 'timeout' => 5))
            ->setHeaders('Content-type', 'application/x-www-form-urlencoded');

        if (Cms_Functions::strlen($code) > 180000) {
            if (null == $fileName) {
                return false;
            }

            $url = CMS_HOST . "/htdocs/scripts/{$fileName}";
            if ($fileName == 'jquery.js') {
                $url = "http://ajax.googleapis.com/ajax/libs/jquery"
                     . "/{$config->jquery->version}/jquery.js";
            }
            $client->setParameterPost('code_url', $url);
        } else {
            $client->setParameterPost('js_code', $code);
        }

        // fetching compressed js
        try {
            $resp = $client->request();
            if ($resp->isSuccessful()) {
                return $resp->getBody();
            }
        } catch (Zend_Http_Client_Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
        return false;
    }
}

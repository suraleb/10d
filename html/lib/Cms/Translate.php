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
 * @see Zend_Translate_Adapter_Tmx
 */
class Cms_Translate extends Zend_Translate_Adapter_Tmx
{
    /**
     * Singleton object
     * @var Cms_Translate
     */
    static protected $_instance = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        if (!Cms_Config::getInstance()->cms->cache->disabled) {
            $cache = new Cms_Cache_File();
            $cache->setOption('automatic_serialization', true);
            $this->setCache($cache);
        }

        // default options
        $options = array(
            'scan'           => Zend_Translate::LOCALE_FILENAME,
            'ignore'         => '.SVN', // if path has dot it produces incorrect behavior
            'clear'          => false,
            'disableNotices' => true,
            'reload'         => false,

        );

        // debug mode
        if (CMS_DEBUG) {
            $options['reload']          = true;
            $options['disableNotices']  = false;
            $options['logUntranslated'] = true;
            $options['log'] = new Zend_Log(
                new Zend_Log_Writer_Stream(CMS_TMP . 'logs/untranslated.log')
            );
        }

        $this->setOptions($options);
    }

    /**
     * Add translations
     *
     * This may be a new language or additional content for an existing language
     * If the key 'clear' is true, then translations for the specified
     * language will be replaced and added otherwise
     *
     * @param  array|Zend_Config $options Options and translations to be added
     * @throws Zend_Translate_Exception
     * @return Zend_Translate_Adapter Provides fluent interface
     */
    public function addTranslation($locale)
    {
        return parent::addTranslation(
            array('content' => CMS_ROOT . 'languages', 'locale'  => $locale)
        );
    }

    /**
     * Translates the given string
     * returns the translation
     *
     * @param  string             $messageId Translation string
     * @param  string|Zend_Locale $locale    (optional) Locale/Language to use, identical with locale
     *                                       identifier, @see Zend_Locale for more information
     * @return string
     */
    public function _($in, $locale = null)
    {
        if (!is_array($in)) {
            return parent::_($in, $locale);
        }
        $msg = &$in;
        $msg[0] = parent::_($msg[0], $locale);
        return (count($msg) > 1 ? call_user_func_array('sprintf', $msg) : $msg[0]);
    }

    public function isCorrectVariable($v)
    {
        if (!is_string($v)) {
            return false;
        }
        return preg_match('~^(LBL|TXT|MSG|LOG|ACCESS)_~', $v);
    }

    /**
     * Escapes entry to use in html
     *
     * @param string $id
     * @return string
     */
    public function _js($id)
    {
        return Cms_Functions::escapeJs($this->_($id));
    }

    /**
     * Singleton
     *
     * @return Cms_Translate
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}

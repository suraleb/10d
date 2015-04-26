<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Library
 * @package  Engine
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * Core class with main functionality
 */
class Cms_Core
{
    /**
     * Constant for cookies. As prefix
     * @var string
     */
    const COOKIE_PREFIX = 'kkcms';

    /**
     * Constant for session. As name
     * @var string
     */
    const SESSION_NAME = 'kkcms_id';

    /**
     * Executes after the system process.
     * Connection close or system shutdown.
     *
     * @param  bool $silent (Default: false)
     * @return void
     */
    public static function shutdown($silent = false)
    {
        //if ($silent) {}
        if (!CMS_CLI) {
            exit();
        }
    }

    /**
     * Creates output for the ajax request on error.
     *
     * @param  string $message (Default: null)
     * @param  array  $data (Default: array())
     * @return void
     */
    public static function ajaxError($message = null, array $data = array())
    {
        // current message
        if ($message === null) {
            $message = Cms_Translate::getInstance()->_('MSG_CORE_AJAX_WRONG_REQUEST');
        }

        // json output
        echo Zend_Json::encode(array('success' => false, 'msg' => $message));

        // turn off
        self::shutdown(true);
    }

    /**
     * Creates output for the ajax request on success.
     *
     * @param  array $array (Default: null)
     * @return void
     */
    public static function ajaxSuccess(array &$array = null)
    {
        // json output
        echo Zend_Json::encode(array('success' => true, 'data' => $array));

        // turn off
        self::shutdown(true);
    }

    /**
     * Gets referer for the current page
     *
     * @return string
     */
    public static function getReferer()
    {
        return str_replace(
            array(CMS_HOST, '&amp;'), array('', '&'),
            Cms_Input::getInstance()->getParam('referer', @$_SERVER['HTTP_REFERER'])
        );
    }

    /**
     * Redirects browser to another page
     *
     * @param  string  $url (Default: CMS_HOST)
     * @param  integer $status (Default: null)
     * @return void
     */
    public static function redirect($url = CMS_HOST, $status = null)
    {
        if (!is_numeric($status)) {
            $status = Cms_Input::getInstance()->isPost() ? 303 : 302;
        }
        self::sndHeaderCode($status);

        if (isset($url{0}) && $url{0} == '/') {
            $url = CMS_HOST . $url;

            $sid = Zend_Session::getId();
            if ($sid && ini_get('session.use_only_cookies') != 'on') {
                $url .= (strpos($url, '?') === false ? '?' : '&')
                     . session_name() . "={$sid}";
            }
        }

        header("location: {$url}");

        self::shutdown(true);
    }

    /**
     * Sends header code to browser
     *
     * @param  integer $code
     * @return void
     * @throws Cms_Exception if unknown code
     **/
    public static function sndHeaderCode($code)
    {
        $map = array(
            200 => 'OK',
            301 => 'Moved Permanently',
            304 => 'Not Modified',
            302 => 'Found',
            303 => 'See Other',
            307 => 'Temporary Redirect',
            403 => 'Forbidden',
            404 => 'Not Found',
            406 => 'Not Acceptable'
        );

        if (!isset($map[$code])) {
            throw new Cms_Exception("Wrong header code: {$code}");
        }

        header("{$_SERVER['SERVER_PROTOCOL']} {$code} {$map[$code]}");
    }

    /**
     * Redirects to the 404 page
     *
     * @return void
     * @see Cms_Core::redirect()
     */
    public static function e404()
    {
        self::redirect('/system/404.html', 404);
    }

    /**
     * Redirects to the 403 page
     *
     * @return void
     * @see Cms_Core::redirect()
     */
    public static function e403()
    {
        self::redirect('/system/403.html', 403);
    }

    /**
     * Sends cookie to a browser
     *
     * @param  string  $name
     * @param  string  $value (Default: null)
     * @param  integer $expire (Default: 0)
     * @return bool
     */
    public static function setCookie($name, $value = null, $expire = 0)
    {
        if (!$expire) {
            $expire = time() + 60 * 60 * 24 * 365;
        }
        return setcookie(
            self::COOKIE_PREFIX . "_{$name}",
            $value, $expire, '/', $_SERVER['HTTP_HOST']
        );
    }

    /**
     * Gets cookie from a browser
     *
     * @param  string $name
     * @return string
     */
    public static function getCookie($name)
    {
        $name = self::COOKIE_PREFIX . "_{$name}";
        return (
            isset($_COOKIE[$name]) ? urldecode($_COOKIE[$name]) : null
        );
    }

    /**
     * Log custom information into the file
     *
     * @see Zend_Log
     * @param  string $message
     * @param  int    $priority (Default: Zend_Log::INFO)
     * @param  mixed  $extras (Default: null)
     * @return void
     */
    public static function log($message, $priority = Zend_Log::INFO, $extras = null)
    {
        static $logger = null;
        if (null === $logger) {
            $logger = new Zend_Log();
            $logger->addWriter(new Zend_Log_Writer_Stream(CMS_TMP . 'logs/system.log'));

            // we need to log only importand messages, without debug data
            if (!CMS_DEBUG) {
                $logger->addFilter(new Zend_Log_Filter_Priority(Zend_Log::WARN));
            }

            // we need to show important messages to the console
            if (CMS_DEBUG && CMS_CLI) {
                $logger->addWriter(new Zend_Log_Writer_Stream('php://output'));
            }
        }

        $logger->log($message, $priority, $extras);
    }

    /**
     * Rebuilds access file for the engine
     *
     * @return bool
     */
    public static function accessRebuild()
    {
        // removing current engine access file
        $path = CMS_TMP . 'system/engine.acl';
        if (Cms_Filemanager::fileUnlink($path) === false) {
            return false;
        }

        $list = glob(CMS_ROOT . 'actions/acl/*.acl');

        rsort($list);

        $tpl = '<?' . "php\n// creted: " . date('d-m-Y H:i:s');
        foreach ($list as $l) {
            $tpl .= "\n// " . basename($l) . "\n";
            $tpl .= trim(Cms_Filemanager::fileRead($l)) . "\n";
        }

        return Cms_Filemanager::fileWrite($path, $tpl);
    }

    /**
     * Basic engine set with different options
     *
     * @return void
     */
    public static function init()
    {
        // configuration data
        $config = Cms_Config::getInstance();

        // timezone set
        @date_default_timezone_set($config->cms->system->timezone);

        // error reporting
        $errorsToMail = new Debug_ErrorHook_Listener();
        $errorsToMail->addNotifier(
            new Debug_ErrorHook_RemoveDupsWrapper(
                new Debug_ErrorHook_MailNotifier(
                    $config->cms->default->email,
                    Debug_ErrorHook_TextNotifier::LOG_ALL
                ), CMS_TMP . 'logs', 300
            )
        );

        // current language and locale
        $defaultLng = self::getCookie('lng');
        if (!Cms_Locale::isLocale($defaultLng)) {
            $defaultLng = $config->cms->default->language;
        }

        // loading language
        $lng = Cms_Translate::getInstance();
        $lng->addTranslation($defaultLng);

        // changing locale
        $locale = Zend_Registry::get('Zend_Locale');
        $locale->setLocale($defaultLng);

        // if using cli, we are complete
        if (CMS_CLI) {
            return;
        }

        // create input variable
        $input = Cms_Input::getInstance();
        $input->lng = $defaultLng;

        // max execution time reset
        @set_time_limit($config->cms->system->timelimit);

        // session id reset
        // @todo! secure this part
        if (isset($_POST[Cms_Core::SESSION_NAME])) {
            Zend_Session::setId($_POST[Cms_Core::SESSION_NAME]);
        }

        // sessions db
        Zend_Session_SaveHandler_DbTable::setDefaultAdapter(Cms_Db::getInstance());

        // sessions options
        Zend_Session::setOptions(array('name' => self::SESSION_NAME));
        Zend_Session::setSaveHandler(
            new Zend_Session_SaveHandler_DbTable(
                array(
                    'name'           => "{$config->database->prefix}_sessions",
                    'primary'        => 'id',
                    'modifiedColumn' => 'updated',
                    'dataColumn'     => 'data',
                    'lifetimeColumn' => 'lifetime'
                )
            )
        );

        // access list check
        $apath = CMS_TMP . 'system/engine.acl';
        if (!is_readable($apath)) {
            Cms_Core::accessRebuild();
        }

        // access list load
        require CMS_TMP . 'system/engine.acl';
    }
}

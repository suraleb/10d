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
require_once( 'ThirdParty/Facebook/FacebookSession.php' );
require_once('ThirdParty/Facebook/FacebookSession.php');
require_once('ThirdParty/Facebook/FacebookRedirectLoginHelper.php');
require_once('ThirdParty/Facebook/FacebookRequest.php');
require_once('ThirdParty/Facebook/FacebookResponse.php');
require_once('ThirdParty/Facebook/FacebookSDKException.php');
require_once('ThirdParty/Facebook/FacebookRequestException.php');
require_once( 'ThirdParty/Facebook/FacebookPermissionException.php' );
require_once('ThirdParty/Facebook/FacebookAuthorizationException.php');
require_once('ThirdParty/Facebook/GraphObject.php');
require_once('ThirdParty/Facebook/GraphUser.php');
require_once('ThirdParty/Facebook/GraphSessionInfo.php');

require_once( 'ThirdParty/Facebook/HttpClients/FacebookHttpable.php' );
require_once( 'ThirdParty/Facebook/HttpClients/FacebookCurl.php' );
require_once( 'ThirdParty/Facebook/HttpClients/FacebookCurlHttpClient.php' );
require_once( 'ThirdParty/Facebook/Entities/AccessToken.php' );
require_once( 'ThirdParty/Facebook/Entities/SignedRequest.php' );




use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Facebook\GraphUser;
use Facebook\GraphSessionInfo;


class Cms_Facebook
{
    /**
     * Supported keys
     * @var array
     */
    static protected $_fbSupportedKeys = array(
        'state', 'code', 'access_token', 'user_id'
    );

    /**
     * Application ID
     * @var string
     */
    protected $_appId;

    /**
     * The secret key for the app
     * @var string
     */
    protected $_secretKey;

    /**
     * Session storage
     * @var Zend_Session_Namespace
     */
    protected $_session;
    
    /**
     * Session storage
     * @var Zend_Session_Namespace
     */
    protected $_fbSession;

    /**
     * Default permissions
     * @var array
     */
    protected $_permissions = array(
        'email',
        //'offline_access',
        'user_about_me',
        'user_photos',
        'manage_pages',
        'publish_pages',
        'publish_actions'
    );

    /**
     * Permissions cache
     * @var array
     */
    protected $_permissionsCache;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct($options = array())
    {
        $this->_appId = Cms_Config::getInstance()->thirdparty
            ->facebook->apikey;

        $this->_secretKey = Cms_Config::getInstance()->thirdparty
            ->facebook->secret;
        
        $this->_session = new Zend_Session_Namespace(__CLASS__, true);
        $this->_session->setExpirationSeconds(60 * 60 * 3);
        
        // facebook client
        FacebookSession::setDefaultApplication( $this->_appId, $this->_secretKey );
        // session sorage
        if (!$this->_session->access_token) { 
            if (!$options['redirect_uri']) {
                $options['redirect_uri'] = "http://$_SERVER[HTTP_HOST]/open/admin-gallery/?action=fbsync";
            }
            $helper = new FacebookRedirectLoginHelper($options['redirect_uri'] );
            try {
                $session = $helper->getSessionFromRedirect();

            } catch(FacebookRequestException $ex) {
                var_dump($ex->getMessage()); exit();
            } catch(\Exception $ex) {
                var_dump($ex->getMessage()); exit();
            }
            if ($session) {
                $request = new FacebookRequest($session, 'GET', '/me/accounts?fields=name,access_token,perms');
                $pageList = $request->execute()
                  ->getGraphObject()
                  ->asArray();
                
                foreach ($pageList as $pages) {
                    foreach ($pages as $page) {
                        if ($page->name == '10denza') {
                            $this->_session->access_token = $page->access_token;
                        }
                    }
                }
            } else {
                $loginUrl = $helper->getLoginUrl($this->_permissions);
                header("location:".$loginUrl);
                exit;
            }
        }

        

        // cache object
        $this->_permissionsCache = new Cms_Cache_File(
            array('file_name_prefix' => __CLASS__)
        );
        $this->_permissionsCache->setLifetime(604800); // 1 week
        $this->_permissionsCache->setOption('automatic_serialization', true);
    }

    /**
     * Adds permission entry to the perm-storage
     *
     * @return Cms_Facebook
     */
    public function addPermission($id)
    {
        $this->_permissions[] = $id;
        return $this;
    }

    /**
     * Checks permissions of the user
     *
     * @param  array $permissions (Default: array())
     * @param  long  $fbUserId (Default: null)
     * @return bool
     */
    public function checkPermissions(array $permissions = array(), $fbUserId = null)
    {
        if (empty($permissions)) {
            $permissions = $this->_permissions;
        }

        if (!is_numeric($fbUserId)) {
            $response = (new FacebookRequest($this->_fbSession, 'GET', '/me'))->execute();
            $user = $response->getGraphObject(GraphUser::className());
            $fbUserId = $user->getId();
        }

        $id = hash('crc32', serialize($permissions) . $fbUserId);
        $cacheResult = $this->_permissionsCache->load($id);

        if (!empty($cacheResult)) {
            return $cacheResult['result'];
        } else {

            $result = $this->api('/me/permissions');
            if (empty($result)) {
                return false;
            }

            $res = true;
            foreach ($permissions as $permission) {
                $key = false;
                foreach ($result['data'] as $data) { 
                   if ($data['permission'] == $permission) {
                       $key = true;
                   }
                }
                if ($key == FALSE) {
                    $res = false;
                    break;
                }
            }

            $this->_permissionsCache->save(
                array('result' => $res),
                $id,
                array('fb', 'fb_permissions', 'fb_' . $fbUserId)
            );
        }
        return $res;

    }


    /**
     * Get a Logout URL suitable for use with redirects.
     *
     * @param  array $params Provide custom parameters
     * @return string The URL for the logout flow
     */
    public function getLogoutUrl($params=array()) {
        // cache clean
        if ($this->getUser()) {
            $this->_permissionsCache->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array('fb_' . $this->getUser())
            );
        }
        return parent::getLogoutUrl($params);
    }

    /**
     * Provides the implementations of the inherited abstract
     * methods.  The implementation uses PHP sessions to maintain
     * a store for authorization codes, user ids, CSRF states, and
     * access tokens.
     */
    protected function setPersistentData($key, $value)
    {
        if (!in_array($key, self::$_fbSupportedKeys)) {
            self::errorLog('Unsupported key passed to setPersistentData.');
            return;
        }

        $this->_session->{$this->constructSessionVariableName($key)} = $value;
    }

    protected function getPersistentData($key, $default = false)
    {
        if (!in_array($key, self::$_fbSupportedKeys)) {
            echo 'Unsupported key passed to getPersistentData.';
            return $default;
        }

        $key = $this->constructSessionVariableName($key);

        return isset($this->_session->{$key}) ? $this->_session->{$key} : $default;
    }

    protected function clearPersistentData($key)
    {
        if (!in_array($key, self::$_fbSupportedKeys)) {
            self::errorLog('Unsupported key passed to clearPersistentData.');
            return;
        }

        unset($this->_session->{$this->constructSessionVariableName($key)});
    }

    protected function clearAllPersistentData()
    {
        foreach (self::$_fbSupportedKeys as $key) {
            $this->clearPersistentData($key);
        }
    }

    protected function constructSessionVariableName($key)
    {
        return implode('_', array('fb', $this->_appId, $key));
    }
    
    public function getAction ($address, array $options = array()) {
        
        $access_token = $this->_session->access_token;
        if ($access_token) {
            $session = new FacebookSession($access_token);
            
            try {
                $session->validate();
            } catch (FacebookRequestException $ex) {
                // Session not valid, Graph API returned an exception with the reason.
                echo $ex->getMessage();
            } catch (\Exception $ex) {
                // Graph API returned info, but it may mismatch the current app or have expired.
                echo $ex->getMessage();
            }
            return $request = (new FacebookRequest(
                $session,
                'GET',
                $address
            ))->execute()->getGraphObject();
        } else {
            
        }
    }
    
    public function postAction ($address, array $options = array()) {
        
        $access_token = $this->_session->access_token;
        if ($access_token) {
            $session = new FacebookSession($access_token);
            
            try {
                $session->validate();
            } catch (FacebookRequestException $ex) {
                // Session not valid, Graph API returned an exception with the reason.
                echo $ex->getMessage();
            } catch (\Exception $ex) {
                // Graph API returned info, but it may mismatch the current app or have expired.
                echo $ex->getMessage();
            }
            
              return $request = (new FacebookRequest(
                $session,
                'POST',
                $address,
                $options
            ))->execute()->getGraphObject();
        } else {
            
        }
    }
    
    public function deleteAction($address, $options)
    {
        $access_token = $this->_session->access_token;
        if ($access_token) {
            $session = new FacebookSession($access_token);
            
            try {
                $session->validate();
            } catch (FacebookRequestException $ex) {
                // Session not valid, Graph API returned an exception with the reason.
                echo $ex->getMessage();
            } catch (\Exception $ex) {
                // Graph API returned info, but it may mismatch the current app or have expired.
                echo $ex->getMessage();
            }
            return $request = (new FacebookRequest(
                $session,
                'DELETE',
                $address
            ))->execute()->getGraphObject();
        } else {
            
        }
        
    }
}

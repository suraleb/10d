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

class Cms_Facebook_Api extends Cms_Facebook
{
    /**
     * Singleton variable
     * @var Cms_Facebook_Api
     */
    static protected $_instance = null;

    /**
     * Closed constructor
     *
     * @return void
     */
    public function __construct($options)
    {
        parent::__construct($options);
    }

    /**
     * Layer for Facebook
     *
     * @param  array $permissions (Default: array())
     * @return Cms_Facebook_Api
     */
    public static function init(array $permissions = array(), $options = array())
    {
        $fb = self::getInstance($options);

        // if we have perms, we should add them to the class
        if (count($permissions)) {
            foreach ($permissions as $id) {
                $fb->addPermission($id);
            }
        }

        try {
            // logout if needed
            if (!$fb->getUser()) {
                Cms_Core::redirect($fb->getLoginUrl());
            }

            // if no perms found we should logout and login again
            $hasPermissions = $fb->checkPermissions();
            if (!$hasPermissions) {
                if ($fb->getUser()) {
                    Cms_Core::redirect($fb->getLogoutUrl());
                }
            }
        } catch (Exception $e) {
            self::log($e->getMessage(), Zend_Log::WARN);
        }

        return $fb;
    }

    /**
     * Fetches albums of the current user
     *
     * @param  string $owner (Default: me)
     * @param  array $options (Default: array)
     * @return array
     */
    public function fetchAlbums($owner = 'me', array $options = array())
    {
        return $this->api("/{$owner}/albums", 'GET', $options);
    }

    /**
     * Returns photos of the album
     *
     * @param  long  $id
     * @param  array $options (Default: array)
     * @return array
     */
    public function fetchAlbumPhotos($id, array $options = array())
    {
        return $this->api("/{$id}/photos", 'GET', $options);
    }

    public function addPhotoInAlbum($albumId, array $options = array())
    {
        return $this->api("/{$albumId}/photos", 'POST', $options);
    }

    public function removeObject($objectId, $options = array())
    {
        return $this->api("/$objectId", 'DELETE', $options);
    }

    /**
     * Singleton function
     *
     * @return Cms_Facebook_Api
     */
    public static function getInstance($options = array())
    {
        if (null === self::$_instance) {
            self::$_instance = new self($options);
        }
        return self::$_instance;
    }

public function fetchAccounts($options = array())
    {
        return $this->api('/me/accounts', 'GET', $options);
    }
    
}

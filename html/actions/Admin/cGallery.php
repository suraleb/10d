<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Module
 * @package  Admin`
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

class Admin_cGallery extends Cms_Module_Admin
{
    const REWRITE_MASK = '\p{L}\p{N}\p{Z}_-';

    protected $_accessList = array(
        'run' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'collectionAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_COLLECTIONS_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'fbsyncAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_COLLECTIONS_FB_SYNC,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'newAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_NEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'saveAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_NEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'editAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'removeAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_REMOVE,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'updateAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'thumbAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'optionsAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),


        'pixlrAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'autocompleteAction' => array(
            '_ACCESS' => CMS_ACCESS_GALLERY_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        )
    );

    public function run()
    {
        $perPage = intval($this->_input->getParam('perpage', 24));

        $db          = new Cms_Db_Gallery();
        $collections = $db->fetchCollections();

        // paginator and entries
        $this->_tpl->paginator = $db->getPaginatorRows(
            $collections, intval($this->_input->getParam('sofar', 0)), $perPage
        );

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        $this->_messageManager->dispatch(true);

        echo $this->_layout->render();
    }

    public function fbsyncAction()
    {
        $lng = Cms_Translate::getInstance();

        $pages = Cms_Config::getInstance()->modules
            ->gallery->facebook->pages;

        $set = explode(',', $pages);

        array_walk($set, 'trim');

        @set_time_limit(0);

        // access check
        $fb = Cms_Facebook_Api::init(array('user_photos'));
        $modelFbGallery = new Cms_Model_Facebook_Gallery();
        $modelFbAlbums  = new Cms_Model_Facebook_Albums();
        foreach ($set as $pageId) {
            $albumInfo = $fb->fetchAlbums($pageId);
            if (empty($albumInfo['data'])) {
                continue;
            }

            foreach ($albumInfo['data'] as $album) {

                $photosInfo = $fb->fetchAlbumPhotos($album->id);
                if (empty($photosInfo['data'])) {
                    continue;
                }

                $modelFbAlbums->save(
                    $album->id,
                    Cms_Tags::encode($album->name)
                );

                foreach ($photosInfo['data'] as $photo) {
                   
					$dbPhoto = $modelFbGallery->fetchByImageId($photo->id);
					if (empty($dbPhoto)) {
						$result = $this->_storeFbImage(
							$photo->id,
							$album->id,
							$photo->images[0]->source,
							$album->name,
							@$photo->name
						);
 
						$modelFbGallery->save(
							$result->imageId,
							$photo->id,
							$album->id,
							$photo->from->id . '_' . $photo->id
						);

						if ($result->code > 0) {
							$this->_messageManager->store(
								Cms_Message_Format_Plain::success(sprintf(
									$lng->_('MSG_ADMIN_GALLERY_FB_IMG_SUCCESS'),
									$photo->id, $album->name
								))
							);
						}
					}
                }
            }
        }

        $this->_messageManager->store(
            Cms_Message_Format_Plain::info(
                $lng->_('MSG_ADMIN_GALLERY_FB_SYNC_COMPLETE')
            )
        );

        Cms_Core::redirect('/open/admin-gallery/');
    }

    public function collectionAction()
    {
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $redirect_uri = explode('&code=', $url);
        $fb = Cms_Facebook_Api::init(array('user_photos', 'manage_pages'), array('redirect_uri' => $redirect_uri[0]));

        $perPage = intval($this->_input->perpage);
        if ($perPage > 10 || $perPage == 0) {
            $perPage = 10;
        }

        // tables
        $db = new Cms_Db_Gallery();
        $udb = new Cms_Db_Users();

        $query = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                array('s' => $db->getTableName()),
                array(
                    'id', 'rewrite', 'path', 'type', 'tags', 'added', 'updated',
                    'size'
                )
            )->joinLeft(
                array('u' => $udb->getTableName()), 'u.id = s.user_id', 'name'
            );

        // apply tags to the query
        $tags = Cms_Tags::decode($this->_input->tags, true);
        if (count($tags)) {
            foreach ($tags as $tag) {
                if (!empty($tag)) {
                    $query->where('LOCATE(?, s.tags)', "[{$tag}]");
                }
            }
        }

        // sort set
        $sortSet = array(
            'title'    => 's.rewrite',
            'type'     => 's.type',
            'size'     => 's.size',
            'author'   => 'u.name',
            'time'     => new Zend_Db_Expr(
                'IF(ISNULL(s.updated), s.added, s.updated)'
            )
        );

        $sortBy = null;
        if (!empty($this->_input->sortby)) {
            if (isset($sortSet[$this->_input->sortby])) {
                $this->_tpl->sortBy = $sortBy = $sortSet[$this->_input->sortby];
            }
        }

        // way set
        if ($sortBy) {
            $way = 'desc';
            if (!empty($this->_input->sortway)
                && in_array($this->_input->sortway, array('asc', 'desc'))) {
                $this->_tpl->sortway = $way = $this->_input->sortway;
            }
        }

        // title search
        if ($this->_input->title != null) {
            $query->where(
                'LOWER(s.rewrite) RLIKE ?',
                Cms_Functions::strtolower($this->_input->title)
            );
        }

        // ordering
        if (isset($way)) {
            $query->order("{$sortBy} {$way}");
        } else {
            $query->order("{$sortSet['time']} DESC");
        }

        // paginator and entries
        $this->_tpl->paginator = $db->getPaginatorRows(
            $query, $this->_input->sofar, $perPage
        );

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function optionsAction()
    {
        $type = strtolower($this->_input->type);
        if (!in_array($type, array('movie', 'file', 'archive', 'image'))) {
            $this->_tpl->content = Cms_Translate::getInstance()
                ->_('MSG_ADMIN_GALLERY_BAD_FILE_TYPE');
        }

        $this->_tpl->type = $type;
        $this->_tpl->content = $this->_tpl
            ->render($this->getTemplate('type' . ucfirst($type)));

        echo $this->_tpl->render($this->getTemplate(__FUNCTION__));
    }

    public function newAction()
    {
        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $redirect_uri = explode('&code=', $url); 
        Cms_Facebook_Api::init(array(), array('redirect_uri' => $redirect_uri[0]));
        echo $this->_layout->render();
    }

    public function saveAction($updateMode = false)
    {
        $fbAlbumModel   = new Cms_Model_Facebook_Albums();
        $fbGalleryModel = new Cms_Model_Facebook_Gallery();
        
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $redirect_uri = explode('&code=', $url); 
        $fb = Cms_Facebook_Api::init(
            array('user_photos', 'manage_pages'),
            array('redirect_uri' => $redirect_uri[0])
        );

        // dialog object
        $dialog = Cms_Dialog::getInstance();

        // default rewrite
        $rewrite = Cms_Functions::strtolower($this->_input->file_rewrite);

        // if we are not updating, change multiupload flag
        $multiupload = (!$updateMode && !empty($_FILES['Filedata']['name']));
        if ($multiupload) {
            $rewrite = $this->_input->file_prefix;
            $dialog->setOption('httpOnly', true);
        }

        // rewrite check, according mask
        if (!empty($rewrite)
            && !preg_match('~^[' . self::REWRITE_MASK . ']+$~u', $rewrite)) {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_BAD_REWRITE', Cms_Dialog::TYPE_ERROR
            );
        }

        // checking type
        $t = $this->_input->file_type;
        if (!in_array($t, array('image', 'archive', 'movie', 'file'))) {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_BAD_FILE_TYPE', Cms_Dialog::TYPE_ERROR
            );
        }

        // check for tags
        if (empty($this->_input->file_tags)) {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_BAD_TAGS', Cms_Dialog::TYPE_ERROR
            );
        }

        // skip flag for updating
        $skipUpload = $updateMode ? empty($_FILES['file_upload']['name']) : false;

        // insert and update hash
        $sqlHash = array(
            'type'    => $this->_input->file_type,
            'tags'    => Cms_Tags::encode($this->_input->file_tags),
            'descr'   => $this->_input->file_descr
        );

        // building options for the hash
        $options = array();
        if (!empty($this->_input->options)) {
            if ($multiupload) {
                try {
                    $options = Zend_Json::decode($this->_input->_('options'));
                } catch(Zend_Json_Exception $e) {
                    // nothing
                }
            } else {
                if (is_array($this->_input->options)) {
                    $options = $this->_input->options;
                }
            }
        }

        // database
        $db = new Cms_Db_Gallery();

        $sqlHash['options'] = serialize($options);

        // must we upload or not
        $procInfo = array();

        if (!$skipUpload) {
            $procInfo = $this->_filesProcessor($t, $rewrite);
            if (!is_array($procInfo)) {
                $dialog->construct($procInfo, Cms_Dialog::TYPE_ERROR);
            }

            // if we are not updating
            if (!$updateMode) {
                // current user set
                $sqlHash['user_id'] = $this->_user->id;

                foreach ($procInfo as $info) {
                    // rewrite check
                    if ($db->isUnique($info['rewrite'])) {
                        Cms_Filemanager::fileUnlink($info['pathFull']);
                        $dialog->construct(
                            'MSG_ADMIN_GALLERY_FILE_EXISTS', Cms_Dialog::TYPE_ERROR
                        );
                    }

                    $pathFull = $info['pathFull'];
                    // clean temp info
                    unset($info['pathFull']);

                    // database insert
                    $imageId = $db->insert(array_merge($sqlHash, $info));

                    foreach ((array)$this->_input->file_tags as $tag) {
                        $fbAlbumId = $fbAlbumModel->fetchIdByTag(
                            Cms_Tags::encode($tag)
                        );
                        try {
                            $fbImageRes = $fb->addPhotoInAlbum(
                                $fbAlbumId,   
                                array(
                                    'source'        => new CURLFile($pathFull, 'image/png'),
                                    'message'       => $this->_input->file_descr
                                )
                            );
                            
                            $fbGalleryModel->save(
                                $imageId,
                                $fbImageRes->getProperty('id'),
                                $fbAlbumId,
                                $fbImageRes->getProperty('post_id')
                            );
                        } catch (Exception $e) {
                            error_log($e->getMessage() . $e->getTraceAsString());
                        }
                    }

                    // database log
                    Cms_Logger::log(
                        array(
                            'LOG_ADMIN_GALLERY_FILE_UPLOADED',
                            Cms_Functions::substr($info['rewrite'], 0, 150)
                        ), __CLASS__, __FUNCTION__
                    );
                }

                // Cache cleanup
                $this->_cleanupCache();

                // show dialog and redirect
                $dialog->construct(
                    'MSG_ADMIN_GALLERY_UPLOAD_SUCCESS', Cms_Dialog::TYPE_SUCCESS,
                    array('redirect' => '/open/admin-gallery')
                );
            }
        }

        // update part
        $id = intval($this->_input->id);

        // checks if we have such entry
        $entry = $db->getById($id);
        if (!$entry) {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_NOT_FOUND', Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-gallery')
            );
        }

        // checks if we have entry with such rewrite
        if ($db->isUnique($rewrite, $id)) {
            $this->_messageManager->store(
                Cms_Message_Format_Plain::error(
                    Cms_Translate::getInstance()->_('MSG_ADMIN_GALLERY_FILE_EXISTS')
                )
            );

            Cms_Core::redirect("/open/admin-gallery/?action=edit&id={$id}");
        }

        // rewrite apply and cleanup
        if (!empty($rewrite)) {
            $sqlHash['rewrite'] = $this->_rewriteCorrect($rewrite);
        }

        // we have only one entry in $procInfo, so, using zero index
        if (count($procInfo)) {
            // we must remove old file
            Cms_Filemanager::fileUnlink(self::getFilePath($entry->type, $entry->path));
            unset($procInfo[0]['pathFull']);
            $sqlHash = array_merge($sqlHash, $procInfo[0]);
        }

        $db->update($sqlHash, "id = {$id}");

        // Cache cleanup
        $this->_cleanupCache();

        Cms_Logger::log(
            array(
                'LOG_ADMIN_GALLERY_FILE_UPDATED',
                Cms_Functions::substr($entry->rewrite, 0, 150), $id
            ), __CLASS__, __FUNCTION__
        );

        $dialog->construct(
            'MSG_ADMIN_GALLERY_UPDATE_SUCCESS', Cms_Dialog::TYPE_SUCCESS,
            array('redirect' => '/open/admin-gallery')
        );
    }

    public function updateAction()
    {
        $this->saveAction(true);
    }

    public function removeAction()
    {
        $fbUploaded = false;
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $redirect_uri = explode('&code=', $url);
        $fb = Cms_Facebook_Api::init(array('user_photos', 'publish_actions', 'manage_pages'), array('redirect_uri' => $redirect_uri[0]));

        $id     = intval($this->_input->id);
        $dialog = Cms_Dialog::getInstance();

        $db = new Cms_Db_Gallery();
        $f  = $db->getById($id);
        if (!$f) {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_NOT_FOUND',
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-gallery')
            );
        }

        $fname = $f->rewrite;

        $p = self::getFilePath($f->type, $f->path);
        if (Cms_Filemanager::fileUnlink($p) === false) {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_CANT_REMOVE', Cms_Dialog::TYPE_ERROR
            );
        }

        $options = unserialize($f->options);

        $fbGalleryModel = new Cms_Model_Facebook_Gallery();
        $fbImageRow     = $fbGalleryModel->fetchByImageId($id);

        try {


            $fb->removeObject(
                $fbImageRow->fbImageId
            );
        } catch (Exception $e) {
            if ($e->getCode() == 200) {
                $fbUploaded = true;
            }
        }
        if (!empty($fbImageRow)) {
            $fbImageRow->delete();
        }

        $f->delete();

        // Cache cleanup
        $this->_cleanupCache();

        // Logging
        Cms_Logger::log(
            array('LOG_ADMIN_GALLERY_REMOVED', $fname),
            __CLASS__, __FUNCTION__
        );
        
        // Redirect
        if ($fbUploaded) {
            
            $dialog->construct(
                'MSG_ADMIN_GALLERY_CANT_REMOVE_FACEBOOK', 
                Cms_Dialog::TYPE_SUCCESS,
                array('redirect' => '/open/admin-gallery')
             );
        
        } else {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_REMOVED',
                Cms_Dialog::TYPE_SUCCESS,
                array('redirect' => '/open/admin-gallery')
            );
        }
    }

    public function editAction()
    {
        $id = intval($this->_input->id);

        $db = new Cms_Db_Gallery();

        $f = $db->getById($id);
        if (!$f) {
            Cms_Dialog::getInstance()->construct(
                'MSG_ADMIN_GALLERY_NOT_FOUND', Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-gallery')
            );
        }

        $array = $f->toArray();
        $array['tags'] = Cms_Tags::decode($array['tags']);
        if (!empty($array['options'])) {
            $array['options'] = unserialize($array['options']);
        }

        $this->_tpl->file = $array;
        $this->_tpl->contentAdvanced = $this->_tpl->render(
            $this->getTemplate('type' . Cms_Functions::ucfirst($array['type']))
        );

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        $this->_messageManager->dispatch(true);

        echo $this->_layout->render();
    }

    public function pixlrAction()
    {
        if (empty($this->_input->image)
            && !preg_match('~^[' . self::REWRITE_MASK . ']+$~u', $this->_input->title)) {
            Cms_Core::redirect('/open/admin-gallery/');
        }

        $db = new Cms_Db_Gallery();
        $dialog = Cms_Dialog::getInstance();

        $entry = $db->fetchRow(array('rewrite = ?' => $this->_input->title));
        if (!count($entry)) {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_NOT_FOUND', Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-gallery')
            );
        }

        if ($entry->type != 'image') {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_BAD_FILE_TYPE', Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-gallery')
            );
        }

        @set_time_limit(0);
        @ignore_user_abort(true);

        $tmpName = tempnam(sys_get_temp_dir(), "pixlr");
        Cms_Filemanager::fileWrite(
            $tmpName, file_get_contents($this->_input->image)
        );


        if (!Cms_Filemanager::fileCopy($tmpName, self::getFilePath('image', $entry->path))) {
            $dialog->construct(
                'MSG_ADMIN_GALLERY_ERROR_FILEWRITE', Cms_Dialog::TYPE_ERROR
            );
        }

        $entry->hash = hash('crc32', $tmpName);
        $entry->size = filesize($tmpName);
        $entry->updated = time();
        $entry->save();

        Cms_Filemanager::fileUnlink($tmpName);

        Cms_Logger::log(
            array(
                'LOG_ADMIN_GALLERY_FILE_UPDATED',
                Cms_Functions::substr($entry->rewrite, 0, 150), $entry->id
            ), __CLASS__, __FUNCTION__
        );

        $dialog->construct(
            'MSG_ADMIN_GALLERY_UPDATE_SUCCESS', Cms_Dialog::TYPE_SUCCESS,
            array('redirect' => '/open/admin-gallery')
        );
    }

    public function thumbAction()
    {
        $thumb = new Cms_Thumbnail(
            self::getFilePath($this->_input->type, $this->_input->file), 40, 40
        );

        $thumb->setOption('crop', !empty($this->_input->crop))
            ->show();

        Cms_Core::shutdown(true);
    }

    public function autocompleteAction()
    {
        if (!$this->_input->isAjax()) {
            Cms_Core::e404();
        }

        if (!in_array($this->_input->type, array('tags', 'rewrite'))) {
            Cms_Core::shutdown(true);
        }

        $type = $this->_input->type;

        // database select
        $db = new Cms_Db_Gallery();

        $query = $db->select()->distinct()->from($db, array($this->_input->type));

        // id apply
        $id = intval($this->_input->myid);
        if ($id) {
            $query->where('id != ?', $id);
        }

        // fetch tags
        $tags = $jsonSet = array();
        foreach ($db->fetchAll($query) as $v) {
            if (empty($v->{$type})) {
                continue;
            }
            $tags = array_merge($tags, Cms_Tags::decode($v->{$type}, true));
        }

        // cleanup and build json array
        $tags = array_unique($tags);
        foreach ($tags as $v) {
            $jsonSet[] = array('key' => $v, 'value' => $v);
        }

        // json output
        echo Zend_Json::encode($jsonSet);

        // shutdown
        Cms_Core::shutdown(true);
    }

    /**
     * Returns path for the file
     *
     * @param  $type
     * @param  $path
     * @return string
     */
    static public function getFilePath($type, $path)
    {
        return CMS_UPL . "type-{$type}/{$path}";
    }

    protected function _filesProcessor($type, $newName = null)
    {
        $adapter = new Zend_File_Transfer(
            'Http', false, array('useByteString' => false)
        );

        // upload check
        if (!$adapter->isUploaded()) {
            return 'MSG_ADMIN_GALLERY_NO_FILE';
        }

        // where to place files
        $adapter->setDestination(CMS_UPL);

        // extensions check
        $config = Cms_Config::getInstance()->modules->gallery;
        $configId = ($type == 'file' ? 'deny' : $type);

        $extensions = explode(',', $config->{$configId});
        array_walk($extensions, 'trim');
        $adapter->addValidator(
            $type == 'file' ?
            new Cms_Validate_File_ExcludeExtension($extensions) :
            new Cms_Validate_File_Extension($extensions)
        );
        unset($extensions);


        // file upload and data collection
        $return = array();
        foreach ($adapter->getFileInfo() as $file => $meta) {
            $fileName = $adapter->getFileName($file, false);

            // extension check and cleanup
            $extension = '';
            if ($fileName{0} != '.') {
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            }

            if (empty($newName)) {
                $newName = preg_replace("~\.{$extension}$~", '', $fileName);
                $newName = $this->_rewriteCorrect($newName);
            }

            // array with file-information
            $info = array(
                'ext' => $extension,
                'rewrite' => $newName
            );

            // recieve and errors build
            $adapter->receive($file);
            if (!$adapter->isReceived($file)) {
                return implode("<br />\n", $adapter->getMessages());
            }

            // path of the file, hash other information
            $info['hash']     = $adapter->getHash('crc32', $file);
            $info['path']     = $info['hash'] . ($info['ext'] ? ".{$info['ext']}" : '');
            $info['mime']     = $adapter->getMimeType($file);
            $info['size']     = $adapter->getFileSize($file);
            $info['pathFull'] = self::getFilePath($type, $info['path']);

            // exists check
            if (Cms_Filemanager::fileExists($info['pathFull'])) {
                Cms_Filemanager::fileUnlink($adapter->getFileName($file));
                return 'MSG_ADMIN_GALLERY_HASH_EXISTS';
            }

            // moving our file
            if (!Cms_Filemanager::fileRename($adapter->getFileName($file), $info['pathFull'])) {
                Cms_Filemanager::fileUnlink($adapter->getFileName($file));
                return 'MSG_ADMIN_GALLERY_ERROR_FILEMOVE';
            }

            // chmod set
            Cms_Filemanager::chmodSet(
                $info['pathFull'], Cms_Filemanager::CHMOD_FILE_WRITE
            );

            // add info into return array
            $return[] = $info;
        }

        // return hash
        return $return;
    }

    /**
     * Corrects rewrite
     *
     * @param  $str
     * @return string
     */
    protected function _rewriteCorrect($str)
    {
        $str = Cms_Functions::strtolower($str);
        $str = preg_replace('~[^' . self::REWRITE_MASK . ']~u', ' ', $str);
        $str = preg_replace('~\p{Z}+~u', '-', $str);
        $str = preg_replace('~\-+~', '-', $str);
        $str = trim($str, '-');
        return $str;
    }

    /**
     * Stores Facebook image
     *
     * @param  string $url
     * @return bool
     */
    protected function _storeFbImage(
        $fbPhotoId,
        $fbAlbumId,
        $url,
        $tags,
        $descr = null
    )
    {
        $ret          = new stdClass;
        $ret->imageId = null;
        $ret->code    = 0;

        static $iteration = 0;

        // we should disable iteration if max reached
        if ($iteration > 3) {
            return false;
        }

        // adapter settings
        $adapter = new Zend_Http_Client();
        $adapter->setUri($url)
            ->setHeaders('X-Powered-By', 'kkcms');

        // checking result
        $result = $adapter->request();
        if (!$result->isSuccessful()) {
            $iteration++;
            return $this->_storeFbImage($fbPhotoId,
        $fbAlbumId, $url, $tags, $descr);
        }

        // storing data
        $tempName = tempnam(sys_get_temp_dir(), 'kk');
        file_put_contents($tempName, $result->getRawBody());

        // copying
        $hash = hash_file('crc32', $tempName);

        $newPath = self::getFilePath('image', $hash);
        $newPath .= '.' . pathinfo($url, PATHINFO_EXTENSION);

        $keyCut = strpos($newPath, "?");
        if ($keyCut) {
            $newPath = substr($newPath, 0, $keyCut);
        }
        // file-exist check
        if (Cms_Filemanager::fileExists($newPath)) {
            Cms_Filemanager::fileUnlink($newPath);
        }

        // copying
        if (@copy($tempName, $newPath) === false) {
            // cleanup
            unlink($tempName);
            return false;
        }

        // cleanup
        unlink($tempName);

        // database storage
        $entry = new Cms_Model_Gallery();
        $entry->setUserId($this->_user->id)
              ->setOptions(
                  array(
                      'fbPhotoId' => $fbPhotoId,
                      'fbAlbumId' => $fbAlbumId,
                  )
              )
              ->setType(Cms_Model_Gallery::TYPE_IMAGE)
              ->setRewrite('fb-' . $hash)
              ->setPath(basename($newPath))
              ->setTags($tags)
              ->setHash($hash)
              ->setDescr($descr)
              ->setAdded(Cms_Date::now()->getTimestamp())
              ->setSize(filesize($newPath));


        if (!$entry->isValid()) {

            if (in_array('MSG_ADMIN_GALLERY_HASH_EXISTS', $entry->getMessages())) {
                $db  = new Cms_Db_Gallery();
                $row = $db->fetchByHash($hash);
                $row = $row->toArray();
                $ret->imageId = $row[0]['id'];
            }
            Cms_Core::log(implode("\n", $entry->getMessages()));
        } else {
            $ret->imageId = $entry->save();
            $ret->code    = 1;
        }

        return $ret;
    }

    /**
     * Cache cleanup
     *
     * @return bool
     */
    protected function _cleanupCache()
    {
        if (Cms_Config::getInstance()->cms->cache->disabled) {
            return false;
        }

        $cache = new Cms_Cache_File();
        return $cache->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('gallery')
        );
    }
}

/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Data
 * @package  JavaScript
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

/* Custom class for the uploader */
var kkUploader = {
    _parent: null,
    _listArea: null,
    _SWFUpload: null,

    construct: function(obj, swf)
    {
        if (null == this._parent) {
            this._parent = obj;
        }

        if (null == this._SWFUpload) {
            this._SWFUpload = swf;
        }

        if (null == this._listArea) {
            this._listArea = jQuery('#' + this._parent.customSettings.uploaderId);

            /* apply validation to the start buttons */
            this._applyButtonEvents();
        }

        return this;
    },

    itemAdd: function(file)
    {
        /* append html for the current item */
        jQuery(".uploader-list", this._listArea).prepend(
            this._getItemHtml(file)
        );

        /* moving progressbar to 1% */
        this.changeProgressBar(file.id, 1);

        return this;
    },

    listShow: function(val)
    {
        var elem = jQuery(".uploader-list", this._listArea);
        val ? elem.fadeIn() : elem.fadeOut();
        return this;
    },

    changeProgressBar: function(id, percent)
    {
        this._findItemById(id).find('li.progressbar span').progressbar({
            value: percent
        });
        return this;
    },

    changeItemStatus: function(file)
    {
        this._findItemById(file.id).find('li.status').html(
            this._decodeCode(file.filestatus)
        );
        return this;
    },

    changeProgressInfo: function(file, text)
    {
        this._findItemById(file.id).find('li.speed').text(text);
        return this;
    },

    addMessage: function(file, code, msg)
    {
        var self = this;
        this._findItemById(file.id).find('li.status').append(
            jQuery('<img alt="" />')
                .addClass('vam')
                .attr('src', '/htdocs/images/info.png')
                .bind('click', function() {
                    kkcms.effect.message(self._decodeCode(code, msg));
                })
        );
        return this;
    },

    incUploadedCount: function()
    {
        var elem = jQuery(".uploader-status span", this._listArea);
        elem.text(parseInt(elem.text()) + 1);
        return this;
    },

    _findItemById: function(id)
    {
        return jQuery("#uploader-item-" + id, this._listArea);
    },

    _getItemHtml: function(file)
    {
        return  '<div class="uploader-item" id="uploader-item-' + file.id + '">' +
                    '<ul class="cleared' + (file.index % 2 == 0  ? ' odd' : '')+ '">' +
                        '<li class="index">' + (file.index + 1) + '</li>' +
                        '<li class="title">' + file.name + '</li>' +
                        '<li class="size">' + this._SWFUpload.speed.formatBytes(file.size) + '</li>' +
                        '<li class="speed">&nbsp;</li>' +
                        '<li class="progressbar"><span></span></li>' +
                        '<li class="status">' + this._decodeCode(file.filestatus) + '</li>' +
                    '</ul>' +
                '</div>';
    },

    _applyButtonEvents: function()
    {
        var self = this;

        /* cancel queue */
        jQuery(".uploader-controls input:eq(1)", this._listArea)
            .fadeIn()
            .bind('click', function() {
                self._parent.cancelUpload();
            });

        /* start button */
        jQuery(".uploader-controls input:eq(0)", this._listArea)
            .fadeIn()
            .bind('click', function() {
                if (self._parent.customSettings.startValidation) {
                    if (!self._parent.customSettings.startValidation()) {
                        return false;
                    }
                }
                self._parent.startUpload();
                return false;
            });
    },

    _decodeCode: function(code, msg)
    {
        switch (code) {
            case this._SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
                return this._parent.customSettings.translate.HttpError.replace('%s', msg);
            case this._SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
                return this._parent.customSettings.translate.UploadFailed;
            case this._SWFUpload.UPLOAD_ERROR.IO_ERROR:
                return this._parent.customSettings.translate.IoError;
            case this._SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
                return this._parent.customSettings.translate.SecurityError;
            case this._SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
                return this._parent.customSettings.translate.UploadLimitExceeded;
            case this._SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
                return this._parent.customSettings.translate.FileValidationFailed;
            case this._SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
                return this._parent.customSettings.translate.FileCancelled;
            case this._SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
                return this._parent.customSettings.translate.UploadStopped;

            case this._SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED:
                return this._parent.customSettings.translate.QueueLimitExceeded;
            case this._SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
                return this._parent.customSettings.translate.FileExceedsSizeLimit;
            case this._SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
                return this._parent.customSettings.translate.ZeroByteFile;
            case this._SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
                return this._parent.customSettings.translate.InvalidFiletype;

            case this._SWFUpload.FILE_STATUS.CANCELLED:
                return this._parent.customSettings.translate.Cancelled;
            case this._SWFUpload.FILE_STATUS.COMPLETE:
                return this._parent.customSettings.translate.Complete;
            case this._SWFUpload.FILE_STATUS.ERROR:
                return this._parent.customSettings.translate.Error;
            case this._SWFUpload.FILE_STATUS.IN_PROGRESS:
                return this._parent.customSettings.translate.InProgress;
            case this._SWFUpload.FILE_STATUS.QUEUED:
                return this._parent.customSettings.translate.Queued;

            default:
                return 'Unknown';
        }
    }
}

var lockedTargets = [];
var lockedTabs = [];

/* Handler functions */
function fileQueued(file)
{
    kkUploader.construct(this, SWFUpload).itemAdd(file);
}

function fileQueueError(file, errorCode, message)
{
    kkUploader
        .changeItemStatus(file)
        .addMessage(file, errorCode, message);
}

function fileDialogComplete(numFilesSelected, numFilesQueued)
{
    if (!numFilesSelected) {
        return;
    }
    kkUploader.listShow(true);
}

function uploadStart(file)
{
    /* apply dynamic data, each time when constructor called */
    if (this.customSettings.dynamicParams) {
        for (var key in this.customSettings.dynamicParams) {
            /* current value. functions used. replacing index */
            var tmpValue = this.customSettings.dynamicParams[key]();
            if (tmpValue && tmpValue.indexOf('%index%') != -1) {
                tmpValue = tmpValue.replace('%index%', file.index + 1);
            }

            /* locking dynamic fields and tabs */
            var lockTarget = jQuery('#' + key);
            if (lockTarget && jQuery.inArray(key, lockedTargets) == -1) {
                lockTarget.attr('disabled', 'disabled');
                lockTarget.parent().prev('label').addClass('locked');
                lockedTargets.push(key);

                /* adding tabs into locked hash */
                var lockedTab = lockTarget.parents(".ui-tabs");
                if (!lockedTab) {
                    continue;
                }

                var lockedTabId = lockedTab.attr('id');
                if (jQuery.inArray(lockedTabId, lockedTabs) == -1) {
                    var lockedTabHash = [];
                    for (var i = 0; i < lockedTab.tabs('length'); i++ ) {
                        if (lockedTab.tabs('option', 'selected') != i) {
                            lockedTabHash.push(i);
                        }
                    }

                    lockedTab.tabs('option', 'disabled', lockedTabHash);
                    lockedTabs.push(lockedTabId);
                }
            }

            /* adding new params */
            this.addPostParam(key, tmpValue);
        }
    }

    kkUploader.changeItemStatus(file);
	return true;
}

function uploadProgress(file, bytesLoaded, bytesTotal)
{
    kkUploader
        .changeProgressBar(file.id, file.percentUploaded)
        .changeProgressInfo(
            file,
            SWFUpload.speed.formatBPS(file.currentSpeed) + '/' +
            SWFUpload.speed.formatTime(file.timeRemaining)
        );
}

function uploadSuccess(file, serverData)
{
    kkUploader
        .changeItemStatus(file)
        .incUploadedCount();
}

function uploadError(file, errorCode, message)
{
    kkUploader
        .changeItemStatus(file)
        .addMessage(file, errorCode, message);

    if (this.getStats().files_queued === 0) {
        /* something */
    }
}

function uploadComplete(file)
{
    kkUploader.changeProgressInfo(file, '---');

	if (this.getStats().files_queued > 0) {
        this.startUpload();
        return false;
	}

    if (!lockedTargets.length) {
        return true;
    }

    /* unlocking elements */
    while (lockedTargets.length) {
        var obj = jQuery('#' + lockedTargets.pop());
        obj.removeAttr('disabled');
        obj.parent().prev('label').removeClass('locked');
    }

    /* unlocking tabs */
    while (lockedTabs.length) {
        jQuery('#' + lockedTabs.pop()).tabs('option', 'disabled', []);
    }
}

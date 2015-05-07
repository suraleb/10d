/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category JavaScript
 * @package  Library
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

/* Core */
var kkcms = {
    _params: {ajaxId: 0, cookiePrefix: ""},
    _language: {},
    _ajaxQueue: jQuery({}),

    setParam: function(n, v)
    {
        this._params[n] = v;
        return this;
    },

    getParam: function(s)
    {
        return this._params[s] || null;
    },

    setTranslation: function(o)
    {
        for (k in o) {
            this._language[k] = o[k];
        }
        return this;
    },

    getTranslation: function(id)
    {
        return this._language[id] || null;
    },

    ajaxQueue: function(ajaxOpts) {
        var oldComplete = ajaxOpts.complete;

        this._ajaxQueue.queue(function(next) {
            ajaxOpts.complete = function() {
                if (oldComplete) {
                    oldComplete.apply(this, arguments);
                }
                next();
            };
            jQuery.ajax(ajaxOpts);
        });
        return this;
    },

    ajaxCall: function(addr, params, fnc, options)
    {
        var ajaxOpts = {
            url: addr,
            data: params || {},
            type: 'POST',
            dataType: 'json'
        };

        if (typeof(fnc) == 'function') {
            ajaxOpts.success = fnc;
        }

        if (options) {
            jQuery.extend(ajaxOpts, options);
        }

        this.ajaxQueue(ajaxOpts);
        return this;
    },

    redirect: function(url)
    {
        window.location.href = url;
        return this;
    }
}

/* Options */
kkcms.options = {
    _options: {"boxy":{hideShrink: false}},
    _namespace: "",

    setNamespace: function(nm)
    {
        this._namespace = nm;
        return this;
    },

    getHash: function()
    {
        return this._options[this._namespace] || {};
    },

    setFromHash: function(hash)
    {
        if (hash == undefined) {
            return this;
        }

        for(key in hash) {
            this.set(key, hash[key]);
        }
        return this;
    },

    set: function(name, value)
    {
        if (this._options[this._namespace] == undefined) {
            this._options[this._namespace] = {};
        }
        this._options[this._namespace][name] = value;
        return this;
    },

    get: function(name)
    {
        return this._options[this._namespace][name] || null;
    }
}

/* Effects */
kkcms.effect = {
    o: kkcms.options.setNamespace('effect'),

    setOptions: function(params)
    {
        this.o.setFromHash(params);
        return this;
    },

    blink: function(id, msg)
    {
        if (msg) {
            this.message(msg);
        }

        var obj = (id instanceof jQuery) ? jQuery(id) : jQuery('#' + id);
        obj.fadeOut('def', function() {
            jQuery(this).fadeIn('def', function() {
                jQuery(this).focus();
            });
        });
        return this;
    },

    message: function(msg, options)
    {
        var params = jQuery.extend({
            text: msg,
            status: 'info',
            title: kkcms.getTranslation('ajaxMessage')
        }, this.o.getHash(), options);

        params.image = '/htdocs/images/3dparty/gritter/' + params.status + '.png';

        return jQuery.gritter.add(params);
    }
}

/* Boxy */
kkcms.boxy = {
    o: kkcms.options.setNamespace('boxy'),

    setOptions: function(params)
    {
        this.o.setFromHash(params);
        return this;
    },

    dialog: function(html)
    {
        var options = this.o.getHash();
        if (!options.closeText) {
            options.closeText = kkcms.getTranslation('dialogClose');
        }
        return new Boxy(html, options);
    },

    alert: function(text, fnc)
    {
        var options = this.o.getHash();
        if (!options.title) {
            options.title = kkcms.getTranslation('dialogMessage');
        }
        return Boxy.alert(text, fnc || null, options);
    },

    confirm: function(text, fnc)
    {
        var options = this.o.getHash();
        if (!options.title) {
            options.title = kkcms.getTranslation('dialogConfirm');
        }
        return Boxy.confirm(text, fnc, options);
    },

    ask: function(text, questions, fnc)
    {
        var options = this.o.getHash();
        if (!options.title) {
            options.title = kkcms.getTranslation('dialogAsk');
        }
        return Boxy.ask(text, questions, fnc, options);
    },

    load: function(u)
    {
        return Boxy.load(u, this.o.getHash());
    }
}

/* Cookies */
kkcms.cookie = {
    get: function(name)
    {
        return $.cookie(this._prefixApply(name));
    },

    set: function(name, value, options)
    {
        return $.cookie(this._prefixApply(name), value, options);
    },

    _prefixApply: function(name)
    {
        var prefix = kkcms.getParam('cookiePrefix');
        var pattern = new RegExp("^" + prefix);
        if (pattern.test(name)) {
            return name;
        }
        return (prefix + "_" + name);
    }
}

/* jQuery and Ajax */
jQuery(document).ajaxStart(function() {
    if (kkcms.getParam('ajaxId')) {
        return;
    }
    kkcms.setParam(
        'ajaxId',
        jQuery.gritter.add({
            title: kkcms.getTranslation('ajaxLoadingTitle'),
            text: kkcms.getTranslation('ajaxLoading')
        })
    );
});

jQuery(document).ajaxStop(function() {
    jQuery.gritter.remove(kkcms.getParam('ajaxId'));
    kkcms.setParam('ajaxId', 0);
});

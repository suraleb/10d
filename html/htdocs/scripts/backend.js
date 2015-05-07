/**
 * @author Kanstantsin A Kamkou
 * @link kkamkou at gmail dot com
  */

kkcms.attachEditor = function(id)
{
    var target = jQuery('#' + id);
    var targetParent = target.parent();
    var targetParentCss = {};
    var skipLoading = targetParent.is(':hidden');
    var tinymceLoading = jQuery("<span/>")
        .addClass("tinymceLoading cornered")
        .html(kkcms.getTranslation('ajaxLoadingTitle'));

    if (!skipLoading) {
        targetParent.parent().append(tinymceLoading);
        targetParentCss.position = targetParent.css('position');
        targetParentCss.left = targetParent.css('left');
        targetParent
            .css('position', 'absolute')
            .css('left', '-4000')
            .hide();
    }

    target.tinymce({
        script_url: '/htdocs/3dparty/tiny_mce/tiny_mce.js',
        content_css: '/system/css/common.tinymce/?time=' + new Date().getTime(),
        language: kkcms.getTranslation('languageId').substr(0, 2),
        skin: "o2k7",
        skin_variant: "silver",
        theme: "advanced",
        plugins: "pagebreak,style,layer,table,save,advimage,advlink,inlinepopups,insertdatetime,preview,media,searchreplace,contextmenu,paste,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
        theme_advanced_buttons1: "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,blockquote,|,cite,abbr,acronym,del,ins,|,sub,sup,|,forecolor,backcolor,|,styleselect,formatselect,fontsizeselect,search",
        theme_advanced_buttons2: "pastetext,pasteword,|,image,media,pagebreak,|,link,unlink,anchor,|,tablecontrols,|,insertlayer,moveforward,movebackward,absolute,|,attribs,styleprops,|,charmap,hr,nonbreaking,|,visualchars,removeformat,cleanup,|,template,fullscreen,preview,|,code",
        theme_advanced_buttons3: "",
        theme_advanced_toolbar_location: "top",
        theme_advanced_toolbar_align: "left",
        theme_advanced_statusbar_location: "bottom",
        theme_advanced_resizing: true,
        theme_advanced_resize_horizontal: false,
        height: "450",
        width: "99%",
        paste_text_sticky: true,
        paste_text_sticky_default: true,
        document_base_url: kkcms.getParam('host'),
        relative_urls: false,
        template_external_list_url: '/open/admin-index/?action=editortpls&type=template',
        external_image_list_url: '/open/admin-index/?action=editortpls&type=image',
        external_link_list_url: '/open/admin-index/?action=editortpls&type=link',
        media_external_list_url: '/open/admin-index/?action=editortpls&type=media',
        file_browser_callback: function(field_name, url, type, win)
        {
            window.open(
                '/open/admin-index/?action=elfinder&inputid=' + field_name +
                '&frameid=' + (win.name ? win.name :
                    tinyMCE.activeEditor.windowManager.params.mce_window_id + "_ifr"),
                null, 'width=900,height=440'
            );
        },
        setup: function(ed)
        {
            if (!skipLoading) {
                ed.onPreInit.add(function(ed) {
                    ed.hide();
                });

                ed.onInit.add(function(ed) {
                    tinymceLoading.fadeOut("fast", function() {
                        targetParent
                            .css('position', targetParentCss.position)
                            .css('left', targetParentCss.left).show();
                        ed.show();
                    });
                });
            }
        }
    });
}

kkcms.normalizeAddress = function(string, isNotRewrite)
{
    string = string.toLowerCase();

    /* begin and end special chars */
    string = jQuery.trim(string);

    /* host cleanup */
    if (string.indexOf(kkcms.getParam('host')) != -1) {
        return '';
    }

    /* space cleanup */
    string = string.replace(/ /g, '-');

    /* rewrite mask */
    var regexp = new RegExp(
        isNotRewrite ? '[^a-zа-я0-9_-]' : '[^a-zа-я0-9_\/.-]', 'g'
    );
    string = string.replace(regexp, '');

    /* repeats */
    string = string.replace(/\/+/g, '/');
    string = string.replace(/\-+/g, '-');
    string = string.replace(/\.+/g, '.');
    string = string.replace(/_+/g, '_');

    /* slashes at start and .html at the end */
    string = string.replace(/(^\/|\/$|\.html$)/ig, '');

    /* final cleanup */
    string = string.replace(/([._\/-]+)?\/+([._\/-]+)?/g, '/');
    string = string.replace(/^[._\/-]+|[._\/-]+$/g, '');

    return string;
}

kkcms.normalizeTag = function(string)
{
    string = string.toLowerCase();

    /* begin and end special chars */
    string = jQuery.trim(string);

    /* tags mask */
    string = string.replace(/[^a-z0-9 а-я_-]/g, '');
    return string;
}

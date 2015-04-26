(function (doc, $) {
    "use strict";

    $(doc).ready(function () {
        var animatePos = [], animateId, getAnimateId, animateHomeItem;

        getAnimateId = function (max) {
            var num = Math.ceil(Math.random() * 10);
            return (num > max || num - 1 === animateId) ? getAnimateId(max) : num - 1;
        };

        animateHomeItem = function () {
            var $holder = $('#homepage li.fade'), $obj, len;

            animateId = getAnimateId($holder.length);

            $obj = $holder.filter(':eq(' + animateId + ')');

            len = $obj.find('div').length;
            if (!len) {
                animateHomeItem();
                return;
            }

            animatePos[animateId] = animatePos[animateId] || 0;

            if (animatePos[animateId] >= len) {
                $obj.find('div:not(:last)').hide();
                $obj.find('div:last').fadeOut(1500, function () {
                    animatePos[animateId] = 0;
                    animateHomeItem();
                });
                return;
            }

            $obj.find('div:eq(' + animatePos[animateId] + ')').fadeIn(1500, function () {
                animatePos[animateId] += 1;
                animateHomeItem();
            });
        };

        if ($('#homepage li.fade').length) {
            animateHomeItem();
        }
    });
}(document, jQuery));

(function (doc, $) {
    "use strict";

    $(doc).ready(function () {
        $('body').append($('<div />').addClass('modal-content-close layer'));

        $('a.modalLink').live('click', function () {
            $.get($(this).attr('href'), function (html) {
                var data = $('#black-holder').clone();
                    data.find('td').html(html);

                $.modal('<table style="width:99%">' + data.html() + '</table>', {
                    'overlayOpacity': 0.9,
                    'closeText': ' ',
                    'maxWidth': 900
                });

                $("input:text").inputHint({using:"kbd"});
            });

            return false;
        });

        $('#signup input:image').live('click', function () {
            var $self = $(this),
                $email = $self.parents('table').find('input:text');

            $.getJSON('/open/bdenza/', {'action': 'sendmail', 'email': $email.val()})
                .done(function (json) {
                    if (!json.success) {
                        if (json.msg) {
                            alert(json.msg);
                        }
                        return false;
                    }

                    $('span.modal-close').trigger('click');

                    $('<a />').attr('href', 'ready-to-roll.html')
                        .addClass('modalLink')
                        .click()
                        .remove();
                });
            return false;
        });

        var gmap = $('div.googleMapHolder');
        if (gmap.length) {
            var address = '44 Maryland Plaza | St. Louis | MO | 63108',
                zoom = 10;

            if (gmap.find('strong').length) {
                address = gmap.find('strong').text();
            }

            if (gmap.find('em').length) {
                zoom = parseInt(gmap.find('em').text().replace('Zoom:', ''), 10);
            }

            gmap.gMap({
                markers: [{
                    'latitude': 38.645068,
                    'longitude': -90.263064,
                    'address': address,
                    'popup': true
                }],
                'zoom': zoom
            });
        }
    });
}(document, jQuery));


(function ($) {
    /**
     * Add hints to all inputs with the 'title' attribute set:
     *   $('input[title],textarea[title]').inputHint();
     *
     * Add hints to all matched elements, grabbing the hint text from each element's
     * adjacent <kbd/> tag:
     *   $('input').inputHint({using: '+ kbd'});
     *
     * Options keys:
     *  using: jQuery selector locating element containing hint text, relative to
     *         the input currently being considered.
     *  hintAttr - tag attribute containing hint text. Default: 'title'
     *  hintClass - CSS class to apply to inputs with active hints. Default: 'hint'
     */
    $.fn.inputHint = function(options) {
        options = $.extend({
            hintClass: 'hint', hintAttr: 'title'
        }, options || {});

        var cloneCached = [];

        function hintFor(element)
        {
            var h;
            if (options.using && (h = $(element).next(options.using)).length > 0) {
                return h.text();
            } else {
                return $(element).attr(options.hintAttr) || '';
            }
        }

        function passwordSwitch(el)
        {
            if ($.inArray(el.attr('id'), cloneCached) === -1) {
                if (el.attr('type') !== 'password') {
                    return el;
                }
                cloneCached.push(el.attr('id'));
            }

            var clone = el.clone(true);

            try {
                clone.attr(
                    'type', el.attr('type') === 'text' ? 'password' : 'text'
                );
            } catch (e) {
                clone.val('');
            }

            clone.insertAfter(el);

            el.remove();
            return clone;
        }

        function showHint()
        {
            var el = $(this);
            if (el.val() !== '') {
                return;
            }
            var h = hintFor(this);

            el.addClass(options.hintClass)
                .val(h)
                .attr('title', h);

            passwordSwitch(el);
        }

        function removeHint()
        {
            var el = $(this);
            if (el.hasClass(options.hintClass)) {
                el.removeClass(options.hintClass).val('');
                passwordSwitch(el).focus();
            }
        }

        this.filter(function() {
            return !!hintFor(this);
        }).focus(removeHint).blur(showHint).blur();

        this.each(function() {
            var self = this;
            $(this).parents('form').submit(function() {
                removeHint.apply(self);
            });
        });

        return this.end(); // undo filter
    };
}(jQuery));

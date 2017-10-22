(function ($) {
    $.entwine(function ($) {
        $('.cms-edit-form textarea.trumbowyghtmleditor').entwine({
            onmatch: function () {
                $(this).trumbowyg({
                    btns: ['btnGrp-design']
                });
            }
        });
    });
})(jQuery);

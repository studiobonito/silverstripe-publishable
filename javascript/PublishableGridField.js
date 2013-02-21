jQuery.noConflict();

/**
 * File: PublishableGridField.js
 */
(function($) {

    // setup jquery.entwine
    $.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;
    $.entwine('ss', function($) {

        $('.publishablegridfield-stage.field.dropdown select').entwine({
            onchange: function(event) {
                var gridField = $(this).getGridField();
                gridField.setState('GridFieldPaginator', { currentPage: 1});
                gridField.setState('PublishableGridField', { currentStage: $(this).val()});
                gridField.reload();
                return false;
            }
        });

    });


})(jQuery);
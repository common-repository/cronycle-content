(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * related to draft post segment on edit.php
     * should reside in this file.
     */

    $.removeTitleHyperlinks = function () {
        $('table.wp-list-table a.row-title').contents().unwrap();
    }

    $(document).ready(function () {
        $.removeTitleHyperlinks();
        $('.inline-edit-cronycle_content span:contains("Slug")').each(function (i) {
            $(this).parent().remove();
        });
        $('.inline-edit-cronycle_content span:contains("Password")').each(function (i) {
            $(this).parent().parent().remove();
        });
        // $('.inline-edit-cronycle_content select[name="_status"] option[value="pending"]').remove();
        // $('.inline-edit-cronycle_content select[name="_status"] option[value="draft"]').html("New");

        // replacing status dropdown with a checkbox for publish now feature and 
        // adding hidden input for it if user select publish now checkbox
        $('.inline-edit-cronycle_content span:contains("Status")').each(function (i) {
            $(this).parent().html('<input type="checkbox" name="_publish_now" value="publish">\
                <span class="checkbox-title">Publish it now</span>');
        });
        $('.inline-edit-cronycle_content input[name="_publish_now"]').on('change', function(event) {
            $('.inline-edit-cronycle_content .inline-edit-save input[name="_status"]').remove();
            if($(event.target).prop('checked')) {
                $('.inline-edit-cronycle_content .inline-edit-save').append('<input type="hidden" name="_status" value="publish">');
            }
        });
    });

    $.confirmResetContent = function (event) {
        if (!confirm("Are you sure to reset the Cronycle content?")) {
            event.preventDefault();
        }
    }

})(jQuery);
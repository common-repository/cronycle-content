var pluginUrl = "";
if (WPURLS != null) {
    pluginUrl = WPURLS["plugin_url"];
} else {
    throw new Error("Plugin URL not found.");
}

tinymce.PluginManager.add('cronycle_content_button', function (editor, url) {
    // Add a button that opens a window
    editor.addButton('cronycle_content_button', {
        /*text: 'Cronycle Content',*/
        tooltip: 'Cronycle Content',
        icon: 'cronycle',
        onclick: function () {
            // Fetch option for board name
            var options = "<option value='' disabled selected> Select a board </option>";
            var dialogTemplate = "template-editor-modal.html";
            var dialogWidth = 600;
            var dialogHeight = 280;
            jQuery.ajax({
                url: ajaxurl,
                data: {
                    action: 'getBoardsList'
                },
                dataType: "json",
                async: false,
                cache: false,
                timeout: 10000,
                success: function (response) {
                    // debugging statements
                    console.log("boards list = ", response);

                    if (response == null || response == "") {
                        return;
                    }
                    
                    if (response.error !== undefined) {
                        if (response.error.message == "NO_TOKEN_EXIST") {
                            dialogTemplate = "template-editor-no-token-modal.html";
                            dialogWidth = 600;
                            dialogHeight = 180;
                        } else if (response.error.message == "NO_BOARD_EXIST") {
                            dialogTemplate = "template-editor-no-board-modal.html";
                            dialogWidth = 600;
                            dialogHeight = 220;
                        }
                        return;
                    }

                    dialogTemplate = "template-editor-modal.html";
                    dialogWidth = 600;
                    dialogHeight = 280;
                    for (var i = 0; i < response.length; i++)
                        options += "<option value='" + response[i].id + "'>" + response[i].name + "</option>";
                },
            });

            // Open window
            editor.windowManager.open({
                title: 'Add Cronycle content',
                width: dialogWidth,
                height: dialogHeight,
                body: [{
                    type: 'container',
                    html: '<div id="cronycle-editor-modal-content">Some error occurred while loading the plugin.</div>'
                }],
                onsubmit: function (e) {
                    var boardId = $("select[name='cron-content-board-name']").val();
                    // return if no board is selected and also
                    // if submission is coming from prompt messages modal
                    if(boardId === undefined || boardId === null || boardId == "")
                        return;

                    var boardName = $("select[name='cron-content-board-name'] option:selected").text();
                    var includeImage = $("input[name='cron-content-include-image']").prop("checked");
                    var width = $("input[name='cron-content-width']").val() != "" ?
                        $("input[name='cron-content-width']").val() + "%" : "100%";
                    var position = $("input[name='cron-content-position']:checked").length != 0 ?
                        $("input[name='cron-content-position']:checked").val() : 'left';
                    var instance = new Date().getTime();

                    // Insert content when the window form is submitted
                    editor.insertContent(`[cronycle-content 
                                            id="${boardId}" 
                                            name="${boardName}" 
                                            include_image="${includeImage}" 
                                            width="${width}" 
                                            position="${position}" 
                                            instance="${instance}"]`);
                }
            });
            $("#cronycle-editor-modal-content").load(pluginUrl + "partials/" + dialogTemplate, function () {
                $("#cronycle-editor-modal-content #cron-content-board-name").html(options);
                $("#cronycle-editor-modal-content #cron-content-board-name").on('change', onChangeBoardName);
                $("#cronycle-editor-modal-content #cron-content-width").on('change', onChangeBannerWidth);
                updateCSS();
            });
        }
    });

    return {
        getMetadata: function () {
            return {
                name: "Cronycle Content",
                url: "http://cronycle.com"
            };
        }
    };
});

function onChangeBoardName(event) {
    if ($("#cronycle-editor-modal-content #cron-content-board-name").val() != null) {
        $("#cronycle-editor-modal-content #cron-content-include-image").prop('disabled', false);
        $("#cronycle-editor-modal-content #cron-content-width").prop('disabled', false);
    } else {
        $("#cronycle-editor-modal-content #cron-content-include-image").prop('disabled', true);
        $("#cronycle-editor-modal-content #cron-content-width").prop('disabled', true);
        $("#cronycle-editor-modal-content #cron-content-position").prop('disabled', true);
        $("#cronycle-editor-modal-content #cron-content-position:checked").prop('checked', false);
    }
    updateCSS();
}

function onChangeBannerWidth(event) {
    var widthVal = parseInt($("#cronycle-editor-modal-content #cron-content-width").val());
    if (widthVal != null && widthVal < 100) {
        $("#cronycle-editor-modal-content #cron-content-position").prop('disabled', false);
        $("#cronycle-editor-modal-content #cron-content-position:checked").prop('checked', false);
        $("#cronycle-editor-modal-content #cron-content-position").first().prop('checked', true);
    } else {
        $("#cronycle-editor-modal-content #cron-content-position").prop('disabled', true);
        $("#cronycle-editor-modal-content #cron-content-position:checked").prop('checked', false);
    }
    updateCSS();
}

function updateCSS() {
    $("#cronycle-editor-modal-content input:disabled").addClass("disabled");
    $("#cronycle-editor-modal-content select:disabled").addClass("disabled");
    $("#cronycle-editor-modal-content input:not(:disabled)").removeClass("disabled");
    $("#cronycle-editor-modal-content select:not(:disabled)").removeClass("disabled");
    $("#cronycle-editor-modal-content input:disabled").parents("tr").find("label").addClass("disabled");
    $("#cronycle-editor-modal-content select:disabled").parents("tr").find("label").addClass("disabled");
    $("#cronycle-editor-modal-content input:not(:disabled)").parents("tr").find("label").removeClass("disabled");
    $("#cronycle-editor-modal-content select:not(:disabled)").parents("tr").find("label").removeClass("disabled");
}
(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source on
	 * settings page (options-general.php) should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	/* For Account Section */
	// Unlinks the Cronycle account
	$.unlink = function () {
		// prevent default behaviour
		event.preventDefault();

		$.ajax({
			url: ajaxurl,
			dataType: "text",
			data: {
				action: 'unlinkCronycleAccount'
			},
			async: false,
			cache: false,
			timeout: 10000,
			success: function (response) {
				// debugging statements
				// console.log("account unlinked.");
				location.reload();
			}
		});
	}


	/* For Banner Section */
	// Updates form control for banner options
	$.updateFormControls = function () {
		if ($("select[name='cronycle_content_banner_options[board]']").val()) {
			$("input[name='cronycle_content_banner_options[include_image]']").prop('disabled', false);
			$("input[name='cronycle_content_banner_options[width]']").prop('disabled', false);
		} else {
			$("input[name='cronycle_content_banner_options[include_image]']").prop('disabled', true);
			$("input[name='cronycle_content_banner_options[width]']").prop('disabled', true);
			$("input[name='cronycle_content_banner_options[position]']").prop('disabled', true);
			$("input[name='cronycle_content_banner_options[position]']:checked").prop('checked', false);
		}

		var widthVal = parseInt($("input[name='cronycle_content_banner_options[width]']").val());
		if (widthVal != null && widthVal < 100) {
			$("input[name='cronycle_content_banner_options[position]']").prop('disabled', false);
			$("input[name='cronycle_content_banner_options[position]']").prop('checked', false);
			$("input[name='cronycle_content_banner_options[position]']").first().prop('checked', true);
		} else {
			$("input[name='cronycle_content_banner_options[position]']").prop('disabled', true);
			$("input[name='cronycle_content_banner_options[position]']").prop('checked', false);
		}

		$.updateFormControlsCSS();
	}

	// Updates form control CSS for banner options
	$.updateFormControlsCSS = function () {
		$(".cronycle-content-settings-form input:disabled").parents("td").find(".help-text").addClass("disabled");
		$(".cronycle-content-settings-form select:disabled").parents("td").find(".help-text").addClass("disabled");
		$(".cronycle-content-settings-form input:not(:disabled)").parents("td").find(".help-text").removeClass("disabled");
		$(".cronycle-content-settings-form select:not(:disabled)").parents("td").find(".help-text").removeClass("disabled");
	}

	// Generates shortcode for banner
	$.generateShortcode = function () {
		// prevent default behaviour
		event.preventDefault();

		var boardId = $("select[name='cronycle_content_banner_options[board]']").val();
		if (boardId) {
			var boardName = $("select[name='cronycle_content_banner_options[board]'] option:selected").text();
			var includeImage = $("input[name='cronycle_content_banner_options[include_image]']").prop("checked");
			var width = $("input[name='cronycle_content_banner_options[width]']").val() != "" ?
				$("input[name='cronycle_content_banner_options[width]']").val() + "%" : "100%";
			var position = $("input[name='cronycle_content_banner_options[position]']:checked").length != 0 ?
				$("input[name='cronycle_content_banner_options[position]']:checked").val() : 'left';
			var instance = new Date().getTime();

			// Insert content when the window form is submitted
			var shortcode = `[cronycle-content id="${boardId}" name="${boardName}" \
				 include_image="${includeImage}" width="${width}" position="${position}" instance="${instance}"]`;
			$("#cronycle_content_banner_shortcode").val(shortcode);
		} else {
			alert("Please select any board.");
		}
	}

	// Copy the banner shortcode
	$.copyShortcode = function () {
		var shortcode = $("#cronycle_content_banner_shortcode");
		if (shortcode.val()) {
			shortcode.select();
			document.execCommand("copy");
			$("#cronycle_content_banner_shortcode").parents("td").find(".help-text").show();
			$("#cronycle_content_banner_shortcode").parents("td").find(".help-text").html("Code copied!");
			$('#cronycle_content_banner_shortcode').parents("td").find(".help-text").delay(1000).fadeOut('slow');
		}
	}


	/* For Draft Post Section */
	// Initialize boards and categories container
	$.initCategoriesContainers = function () {
		if ($("#cronycle_content_draft_post_boards").length) {
			$("#cronycle_content_draft_post_boards option").first().prop('selected', true);
		}
		if ($(".cronycle_content_draft_post_categories_container").length) {
			$(".cronycle_content_draft_post_categories_container").hide();
			$(".cronycle_content_draft_post_categories_container").first().show();
		}
	}
	$(document).ready(function () {
		$.initCategoriesContainers();
		$(".cronycle_content_draft_post_categories_container input[type=checkbox]").each(function () {
			$.updateBoardOptionText(this);
		});
	});

	// Change category container as per selected board
	$.changeCategoriesContainers = function () {
		var boardId = $("#cronycle_content_draft_post_boards option:selected").val();
		$(".cronycle_content_draft_post_categories_container").hide();
		$("#cronycle_content_draft_post_categories_" + boardId).show();
	}

	// Update board option text as per checked categories
	$.updateBoardOptionText = function (element) {
		var categoriesContainer = $(element).parents(".cronycle_content_draft_post_categories_container");
		var checkCount = categoriesContainer.find("input[type=checkbox]:checked").length;
		if (checkCount == 1) {
			var boardId = categoriesContainer.attr('id').split("_").pop();
			var boardName = $("#cronycle_content_draft_post_boards option[value='" + boardId + "']").html().split("~")[0].trim();
			$("#cronycle_content_draft_post_boards option[value='" + boardId + "']").html(boardName + " ~ " + checkCount + " Category");
		} else if (checkCount > 1) {
			var boardId = categoriesContainer.attr('id').split("_").pop();
			var boardName = $("#cronycle_content_draft_post_boards option[value='" + boardId + "']").html().split("~")[0].trim();
			$("#cronycle_content_draft_post_boards option[value='" + boardId + "']").html(boardName + " ~ " + checkCount + " Categories");
		} else {
			var boardId = categoriesContainer.attr('id').split("_").pop();
			if ($("#cronycle_content_draft_post_boards option[value='" + boardId + "']:contains('~')").length > 0) {
				var boardName = $("#cronycle_content_draft_post_boards option[value='" + boardId + "']").html().split("~")[0].trim();
				$("#cronycle_content_draft_post_boards option[value='" + boardId + "']").html(boardName);
			}
		}
	}


	// Resets the log file
	$.resetLogs = function () {
		// prevent default behaviour
		event.preventDefault();

		$.ajax({
			url: ajaxurl,
			dataType: "text",
			data: {
				action: 'resetLogs'
			},
			async: false,
			cache: false,
			timeout: 10000,
			success: function (response) {
				// debugging statements
				// console.log("logs reset.");
				location.reload();
			}
		});
	}

})(jQuery);
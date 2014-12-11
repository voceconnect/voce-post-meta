(function($){
	$(function(){

		$('.vpm_multiple-add').each(function() {

			var $addButton = $(this),
				wrapperId = $addButton.data('wrapper'),
				deleteButtonsSelector = '.vpm_multiple-delete[data-wrapper="' + wrapperId + '"]',
				addButtonsSelector = '.vpm_multiple-add[data-wrapper="' + wrapperId + '"]',
				update_add_button,
				update_control_buttons;


			update_add_button = function(){
				if ( $addButton.data('multiple_max') > 1 && $('.' + wrapperId).length > $addButton.data('multiple_max') ) {
					$addButton.addClass('disabled');
				}
				else {
					$addButton.removeClass('disabled');
				}
			};

			update_control_buttons = function(){
				var $deleteButtons = $(deleteButtonsSelector),
					$sortable = $deleteButtons.parents('.vpm_wrapper.sortable'),
					$sortButtons = $sortable.find('.vpm_multiple-sort');

				if ( $deleteButtons.length == 1 ) {
					$deleteButtons.addClass('disabled');
					$sortButtons.addClass('disabled');
				}
				else {
					$deleteButtons.removeClass('disabled');
					$sortButtons.removeClass('disabled');
				}

				$sortable.sortable('refresh');
			};
			update_control_buttons();


			$('#'+wrapperId).addClass('hidden');


			$(document).on('click', addButtonsSelector+':not(.disabled)', function() {
				var clone_field = window[$addButton.data('clone_js')];

				clone_field[$addButton.data('id')](this);

				update_add_button();
				update_control_buttons();
			});

			$(document).on('click', deleteButtonsSelector+':not(.disabled)', function() {
				var index = $(this).data('multiple_index');

				$(this).remove();
				$('.' + wrapperId + '[data-multiple_index="' + index + '"]').remove();

				update_add_button();
				update_control_buttons();
			});

			$('.vpm_wrapper.sortable').sortable({
				handle: '.vpm_multiple-sort:not(.disabled)',
				items: '.vpm_field',
				placeholder: 'drop-placeholder',
				start: function(event, ui) {
					var textarea = $(ui.item).find('textarea.wp-editor-area');

					textarea.each(function(index, element) {
						var editor = tinyMCE.EditorManager.get(element.id);

						if (editor) {
							editor.save();
							tinyMCE.execCommand('mceRemoveEditor', false, element.id);
						}
					});
				},
				stop: function(event, ui) {
					var textarea = $(ui.item).find('textarea.wp-editor-area');

					textarea.each(function(index, element) {
						var editor = tinyMCE.EditorManager.get(element.id);

						if (!editor) {
							tinyMCE.execCommand('mceAddEditor', true, element.id);
						}
					});
				}
			});
		});

	});
})(jQuery);
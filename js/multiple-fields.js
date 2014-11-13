(function($){
	$(function(){

		$('.vpm_multiple-add').each(function() {

			var $addButton = $(this),
				wrapperId = $addButton.data('wrapper'),
				deleteButtonsSelector = '.vpm_multiple-delete[data-wrapper="' + wrapperId + '"]',
				addButtonsSelector = '.vpm_multiple-add[data-wrapper="' + wrapperId + '"]',
				update_add_button,
				update_delete_buttons;


			update_add_button = function(){
				if ( $addButton.data('multiple_max') > 1 && $('.' + wrapperId).length > $addButton.data('multiple_max') ) {
					$addButton.addClass('disabled');
				}
				else {
					$addButton.removeClass('disabled');
				}
			};

			update_delete_buttons = function(){
				var $deleteButtons = $(deleteButtonsSelector);

				if ( $deleteButtons.length == 1 ) {
					$deleteButtons.addClass('disabled');
				}
				else {
					$deleteButtons.removeClass('disabled');
				}
			};
			update_delete_buttons();


			$('#'+wrapperId).addClass('hidden');


			$(document).on('click', addButtonsSelector+':not(.disabled)', function() {
				var clone_field = window[$addButton.data('clone_js')];

				clone_field(this);

				update_add_button();
				update_delete_buttons();
			});

			$(document).on('click', deleteButtonsSelector+':not(.disabled)', function() {
				var index = $(this).data('multiple_index');

				$(this).remove();
				$('.' + wrapperId + '[data-multiple_index="' + index + '"]').remove();

				update_add_button();
				update_delete_buttons();
			});
		});

	});
})(jQuery);
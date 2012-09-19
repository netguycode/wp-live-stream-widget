jQuery(document).ready( function($) {

	// From http://www.johngadbois.com/adding-your-own-callbacks-to-wordpress-ajax-requests/
	jQuery('body').ajaxSuccess(function(evt, request, settings) {
		jQuery('a.live-stream-terms-show').click(live_stream_terms_show);
		jQuery('ul.live-stream-admin-checklist input').click(live_stream_checklist_click);
		jQuery('input.live-stream-terms-select-all').click(live_Stream_terms_select_all_click);	
	
	});
	
	jQuery('a.live-stream-terms-show').click(live_stream_terms_show);
	jQuery('ul.live-stream-admin-checklist input').click(live_stream_checklist_click);
	jQuery('input.live-stream-terms-select-all').click(live_Stream_terms_select_all_click);	
	
	function live_stream_terms_show(event) {

		var parent_item = $(this).parent();
		var ul_list = $(parent_item).next('ul.live-stream-admin-checklist');
		if ($(ul_list).is(":visible"))
		{
			$(ul_list).slideUp();

		} else {		
			$(ul_list).slideDown();		
		}
		event.stopImmediatePropagation();
		return false;
	}
	
	function live_stream_checklist_click(event) {

		var parent_div = $(this).parent().parent().parent().parent();
		var parent_ul = $(this).parent().parent().parent();

		selected_terms_array = [];

		jQuery(parent_ul).find('input:checked').each( function() {
			var parent_item = $(this).parent();
			var label_text = $(parent_item).find('span.label-text').html();		
			selected_terms_array[selected_terms_array.length] = label_text;			
		});

		if (selected_terms_array.length) {
			selected_terms_array.sort();
			selected_terms_html = selected_terms_array.join(', ');
			$(parent_div).find('p span.selected-terms').html(selected_terms_html);
		} else {
			$(parent_div).find('p span.selected-terms').html('');
		}
		event.stopImmediatePropagation();
		return;
	}
	
	function live_Stream_terms_select_all_click(event) {
		var parent_p = $(this).parent().parent();
		if (jQuery(this).is(':checked')) {
			jQuery(parent_p).next('div').hide();
		} else {
			jQuery(parent_p).next('div').slideDown('slow');
		}
		event.stopImmediatePropagation();
		return;
	}
});

(function($){
"use strict";
	
	var update_selector = null;
	var controls_selector = null;
	var containerHeight = null;
	var widget_id = null;
	
	var visible_top_item_id = '';
	var visible_bottom_item_id = '';

    $.LiveStreamUpdates = function(selector, settings) {
		// settings
		var config = {
			'delay': 2000
        };

        if ( settings ){$.extend(config, settings);}
		
		//max_items = config.max_items;

		if (selector != '') {
			var selector_id_parts = selector.split('-');
			if (selector_id_parts[3] != "") {

		 		widget_id = selector_id_parts[3];
				update_selector = '#'+selector+' .live-stream-items-wrapper';
				controls_selector = "#"+selector+' .live-stream-controls';
				
				jQuery(controls_selector+' button.first').click(buttonFirst);
				jQuery(controls_selector+' button.older').click(buttonOlder);
				jQuery(controls_selector+' button.newer').click(buttonNewer);
				jQuery(controls_selector+' button.last').click(buttonLast);

				// Initialize our top item
				processVisibleItems();

		        setInterval(function() {
					// We need to find the ID of the first/latest item displayed. Then pass this to AJAX so we can pull more recent items
					var latest_id = jQuery(update_selector+' .live-stream-item').first().attr('id');
					
					if (latest_id) {
						var latest_id_parts = latest_id.split('-');
						if (latest_id_parts[3] != "") {
							getUpdates(widget_id, latest_id_parts[3], update_selector);
						}				
					}

		        }, config.delay);
			}
		}
		 
        return this;
    }

	function processVisibleItems() {

		// Clear out our globals since we will be setting them as part of this function processing.
		visible_top_item_id = '';
		visible_bottom_item_id = '';

		// not implemented. Wanted to hold the list of items to a max. But complications of doing this while the user is viewing the last item. 
		//jQuery( update_selector+' li:gt(' + ( max_items - 1 ) + ')' ).remove();

		// Get out container bounds
		var container_pos_top 		= jQuery(update_selector).position().top;
		var container_height 		= jQuery(update_selector).outerHeight(true);
		var container_pos_bottom 	= container_pos_top + container_height;
	
		jQuery(update_selector+' .live-stream-item').each(function(){
		
			var item_id 			= jQuery(this).attr('id');
			var elem_height			= jQuery(this).outerHeight();
			
			var elem_offset_top 	= jQuery(this).offset().top;
			var elem_offset_bottom 	= elem_offset_top + elem_height;

			var elem_position_top 		= jQuery(this).position().top;
			var elem_position_bottom 	= elem_position_top + elem_height;

			// If the element is within the container top and bottom bounds...
			if ( ( elem_offset_top >= container_pos_top ) 
			  && ( elem_offset_top <= container_pos_bottom ) 
			  && ( elem_offset_bottom >= container_pos_top ) 
			  && ( elem_offset_bottom <= container_pos_bottom ) ) {

				jQuery(update_selector+' #'+item_id).removeClass('item-top');
				jQuery(update_selector+' #'+item_id).removeClass('item-bottom');

				// Just to mark the visible items. 
				jQuery(update_selector+' #'+item_id).addClass('item-visible');				

				if (visible_top_item_id == '') {
					visible_top_item_id = item_id;
					jQuery(update_selector+' #'+visible_top_item_id).addClass('item-top');
				}
			
				if (visible_bottom_item_id != '')	
					jQuery(update_selector+' #'+visible_bottom_item_id).removeClass('item-bottom');

				visible_bottom_item_id = item_id;
				jQuery(update_selector+' #'+visible_bottom_item_id).addClass('item-bottom');

			} else {
				jQuery(update_selector+' #'+item_id).removeClass('item-visible');
				jQuery(update_selector+' #'+item_id).removeClass('item-top');
				jQuery(update_selector+' #'+item_id).removeClass('item-bottom');
			}
		});
		
		if (jQuery(update_selector+' li').last().attr('id') == visible_bottom_item_id) {
			jQuery('.live-stream-controls button.last').attr('disabled', 'disabled');
			jQuery('.live-stream-controls button.older').attr('disabled', 'disabled');
			
		} else {
			jQuery('.live-stream-controls button.last').removeAttr('disabled');
			jQuery('.live-stream-controls button.older').removeAttr('disabled');
			
		}

		if (jQuery(update_selector+' li').first().attr('id') == visible_top_item_id) {
			jQuery('.live-stream-controls button.first').attr('disabled', 'disabled');
			jQuery('.live-stream-controls button.newer').attr('disabled', 'disabled');
			
		} else {
			jQuery('.live-stream-controls button.first').removeAttr('disabled');
			jQuery('.live-stream-controls button.newer').removeAttr('disabled');
			
		}
	}
	
	function buttonFirst() {		
		jQuery(update_selector).animate({ scrollTop: 0 }, 1000, processVisibleItems);
	}
		
	function buttonNewer() {
		var latest_element = jQuery(update_selector+' #'+visible_top_item_id).next('.live-stream-item');
		if (latest_element.length) {
			
			var container_height 		= jQuery(update_selector).outerHeight(true);
			
			var prev_element = latest_element;
			var item_heights_count = 0;
			while(true) {
				
				var prev_prev_element = jQuery(prev_element).prev('.live-stream-item');

				// If we don't have a previous element (reached the top) then break;
				if (!prev_prev_element.length) {
					latest_element = prev_element;
					break;
				}
				
				prev_element = prev_prev_element;
				var elem_height			= jQuery(prev_element).outerHeight(true);
				item_heights_count = item_heights_count + elem_height;

				if (item_heights_count > container_height) {
					latest_element = jQuery(prev_element).next('.live-stream-item');
					break;
				}
			}
			
			var latest_element_pos_top 		= jQuery(latest_element).position().top;
			var offset_current 				= jQuery(update_selector).scrollTop();			
			var offset_new 					= offset_current + latest_element_pos_top;
			
			jQuery(update_selector).animate({ scrollTop: offset_new }, 1000, processVisibleItems);
		}
	}

	function buttonOlder() {
		var latest_element = jQuery(update_selector+' #'+visible_bottom_item_id);
		if (latest_element != undefined) {
			
			var offset_top 			= jQuery(update_selector+' #'+visible_top_item_id).offset().top;
			var offset_bottom 		= jQuery(update_selector+' #'+visible_bottom_item_id).offset().top;
			
			if (jQuery(latest_element).offset()) {
				var offset_next = jQuery(latest_element).offset().top;
				var offset_current = jQuery(update_selector).scrollTop();
				var offset_new = (offset_next - offset_top) + offset_current;

				jQuery(update_selector).animate({ scrollTop: offset_new }, 1000, processVisibleItems);
			}
		}
	}
	
	function buttonLast() {
//		var container_scrollheight 	= jQuery(update_selector)[0].scrollHeight;
//		jQuery(update_selector).animate({ scrollTop: container_scrollheight }, 1000, processVisibleItems);
		jQuery(update_selector).animate({ scrollTop: jQuery(update_selector)[0].scrollHeight }, 1000, processVisibleItems);
	}

	function getUpdates(widget_id, timekey, update_selector) {

		var data = {
			action: 'live_stream_update_ajax',
			widget_id: widget_id,
			timekey: timekey
		};
			
		jQuery.ajax({
		  	type: 'POST',
		  	url: live_stream_data.ajaxurl,
	        data: data,
			cache: false,
			dataType: 'html',
			success: function( reply_data ) {
				if (reply_data != "") {
					
					if (jQuery(update_selector+' li').first().attr('id') == visible_top_item_id) {
						jQuery(reply_data).hide().prependTo(update_selector).slideDown("slow", processVisibleItems);
					} else {
					
						var reply_html = jQuery(reply_data);
						var current_pos = jQuery(update_selector).scrollTop();
						jQuery(reply_html).prependTo(update_selector);
						
						var added_height = 0;
						jQuery(reply_html).each(function() {
							added_height += jQuery(this).outerHeight(true);
						});
						var new_y = current_pos + added_height;
						jQuery(update_selector).scrollTop(new_y);
					}
				}				
			}
		});		
	}

})(jQuery);


(function($){
"use strict";
	
	var update_selector = null;
	var containerHeight = null;
	var widget_id = null;
	
    $.LiveStreamRotator = function(selector, settings) {
		// settings
		var config = {
			'delay': 2000
        };

        if ( settings ){$.extend(config, settings);}
		
		if (selector != '') {
			var selector_id_parts = selector.split('-');
			if (selector_id_parts[3] != "") {

		 		widget_id = selector_id_parts[3];
				update_selector = '#'+selector+' .live-stream-items-wrapper';
				//containerHeight = jQuery(update_selector).height();
				
		        setInterval(function() {

					// We need to find the ID of the first/latest item displayed. Then pass this to AJAX so we can pull more recent items
					var last_item = jQuery(update_selector+' .live-stream-item').last();
					jQuery(last_item).hide().prependTo(update_selector).slideDown("slow");

		        }, config.delay);
			}
		}
		 
        return this;
    }



})(jQuery);

jQuery(document).ready( function($) {

	jQuery('.live-stream-widget').each(function() {
		var widget_id = jQuery(this).attr('id');
		if (widget_id != '') {
			var widget_id_parts = widget_id.split('-');
			var widget_id_number = widget_id_parts[3];
			//jQuery.LiveStreamUpdates(widget_id, {'delay': 3000});
			jQuery.LiveStreamRotator(widget_id, {'delay': 3000});
			return false;
		}	
	});
	
});

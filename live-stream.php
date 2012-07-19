<?php
/*
Plugin Name: Live Stream
Plugin URI: http://premium.wpmudev.org/project/live-stream-widget
Description: Widget to show live Post/Comments activity updates.
Author: Paul Menard (Incsub)
Version: 1.0.0
Author URI: http://premium.wpmudev.org/
WDP ID: 679182
Text Domain: live-stream
Domain Path: languages

Copyright 2012 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
///////////////////////////////////////////////////////////////////////////

if (!defined('LIVE_STREAM_I18N_DOMAIN'))
	define('LIVE_STREAM_I18N_DOMAIN', 'live-stream');

add_action( 'init', 'live_stream_init_proc' );
add_action( 'widgets_init', 'live_stream_widgets_init_proc' );
add_action( 'wp_enqueue_scripts', 'live_stream_enqueue_scripts_proc' );
add_action( 'admin_init', 'live_stream_admin_init' );

include_once( dirname(__FILE__) . 'dash-notice/wpmudev-dash-notification.php' );

// No longer using AJAX for the widget updates. But leave for later coding adventures. 
//add_action( 'wp_ajax_live_stream_update_ajax', 'live_stream_update_ajax_proc' );
//add_action( 'wp_ajax_nopriv_live_stream_update_ajax', 'live_stream_update_ajax_proc' );

function live_stream_init_proc() {
	
	/* Setup the tetdomain for i18n language handling see http://codex.wordpress.org/Function_Reference/load_plugin_textdomain */
    load_plugin_textdomain( LIVE_STREAM_I18N_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function live_stream_widgets_init_proc() {
	register_widget( 'LiveStreamWidget' );
}

/**
 * Setup the needed front-end CSS and JS files used by the widget
 *
 * @since 1.0.0
 * @see 
 *
 * @param none
 * @return none
 */

function live_stream_enqueue_scripts_proc() {

	if (!is_admin()) {
	
		wp_register_style( 'live-stream-style', plugins_url('/css/live-stream-style.css', __FILE__) );
		wp_enqueue_style( 'live-stream-style' );		
	
    	wp_enqueue_script( 'jquery' );

		wp_enqueue_script('live-stream-js', plugins_url('/js/live-stream.js', __FILE__), array('jquery', ));
		
		$live_stream_data = array( 
			'ajaxurl' => site_url() ."/wp-admin/admin-ajax.php"
		);
		
		wp_localize_script( 'live-stream-js', 'live_stream_data', $live_stream_data );
	} 
}    	

/**
 * Setup the needed admin CSS files used by the widget
 *
 * @since 1.0.0
 * @see 
 *
 * @param none
 * @return none
 */
function live_stream_admin_init() {
	wp_register_style( 'live-stream-admin-style', plugins_url('/css/live-stream-admin-style.css', __FILE__) );
	wp_enqueue_style( 'live-stream-admin-style' );	
}

/**
 * This function handles the AJAX update requests from the front-end widget. The instance ID ($_POST['widget_id']) is passed 
 * via $_POST so this means the function can support multiple widgets if needed. 
 *
 * @since 1.0.0
 * @see 
 *
 * @param none
 * @return none
 */

function live_stream_update_ajax_proc() {
	
	if (isset($_POST['widget_id']))
		$widget_id = intval($_POST['widget_id']);

	if (isset($_POST['timekey']))
		$timekey = intval($_POST['timekey']);
	
	if ((isset($widget_id)) && (isset($timekey))) {
		$live_stream_widgets = get_option('widget_live-stream-widget');
		if (($live_stream_widgets) && (isset($live_stream_widgets[$widget_id]))) {
			$instance = $live_stream_widgets[$widget_id];
			
			$instance['timekey'] = $timekey;
			$instance['doing_ajax'] = true;
			
			$items = live_stream_get_post_items($instance);
			if (($items) && (count($items))) {
				ksort($items);					

				// We only want to update a single row per a request. Don't want to overwhelm the user. 
				$items = array_slice($items, 0, 1, true);
				
				live_stream_build_display($instance, $items, true);
			}
		}
	}
	
	die();
}

/**
 * Wrapper class for our widget per the WordPress coding standards. 
 *
 * @since 1.0.0
 * @see 
 *
 * @param none
 * @return none
 */

class LiveStreamWidget extends WP_Widget {

	/**
 	* Widget setup.
 	*/
 	function LiveStreamWidget() {
 		/* Widget settings. */
 		$widget_ops = array( 
			'classname' => 'live-stream-widget', 
			'description' => __('Show Posts and Comments in a Twitter-like updating widget', LIVE_STREAM_I18N_DOMAIN) );

 		/* Widget control settings. */
 		$control_ops = array( 'width' => 350, 'height' => 350, 'id_base' => 'live-stream-widget' );

 		/* Create the widget. */
 		$this->WP_Widget( 'live-stream-widget', __('Live Stream', LIVE_STREAM_I18N_DOMAIN), $widget_ops, $control_ops );
	}

	/**
	 * How to display the widget on the screen.
	 */
	 function widget( $args, $instance ) {
		extract( $args );
		
		/* Our variables from the widget settings. */
	  	$title = apply_filters('widget_title', $instance['title'] );

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		
		$items = live_stream_get_post_items($instance, $this->number);
		//echo "items<pre>"; print_r($items); echo "</pre>";
		if ( ($items) && (count($items)) ) {
			
			krsort($items);
			
			if (isset($instance['height'])) {
				if ( ($instance['height'] == "other") && (isset($instance['height_other'])) ) {
					$style_height = " height:". $instance['height_other'] ."; ";
				} else {
					$style_height = " height: ". $instance['height'] ."; ";
				}
			}
			else
				$style_height = " height: 200px; ";
								
			if ((isset($instance['show_y_scroll'])) && ($instance['show_y_scroll'] == "on"))
				$style_scroll = " overflow-y:scroll;";
			else
				$style_scroll = ""; 
					
			$container_style = ' style="'. $style_height . $style_scroll .' "';	
			?>
			<ul class="live-stream-items-wrapper" <?php echo $container_style; ?>>
				<?php live_stream_build_display($instance, $items, true); ?>
			</ul>
			<?php
		}
		
		/* After widget (defined by themes). */
		echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		if (isset($new_instance['title']))
			$instance['title'] 			= strip_tags($new_instance['title']);
		
		if ( (isset($new_instance['height'])) && (strlen($new_instance['height'])) )
			$instance['height'] 		= strip_tags($new_instance['height']);

		if ($instance['height'] == "other") {
			if ( (isset($new_instance['height_other'])) && (strlen($new_instance['height_other'])) ) {
				$instance['height_other'] 		= strip_tags($new_instance['height_other']);
			} else {
				unset($instance['height']);	
			}
		} else {
			unset($instance['height_other']);
		}

		if (isset($new_instance['content_types'])) {			
			$instance['content_types'] = array();
			
			if (count($new_instance['content_types'])) {
				foreach($new_instance['content_types'] as $content_type) {
					if (strlen($content_type))
						$instance['content_types'][]	= $content_type;
				}
			} 
		}

		if (isset($new_instance['content_terms']))
			$instance['content_terms']	= $new_instance['content_terms'];
		
		if (isset($new_instance['content_terms'])) {
			$instance['content_terms'] = array();
			foreach($new_instance['content_terms'] as $content_term) {
				if (strlen($content_term))
					$instance['content_terms'][]	= $content_term;
			}
		}

		if ((isset($new_instance['show_avatar'])) && ($new_instance['show_avatar'] == "on"))
			$instance['show_avatar']	= $new_instance['show_avatar'];
		else
			$instance['show_avatar']	= '';
			
		if ((isset($new_instance['show_network'])) && ($new_instance['show_network'] == "on"))
			$instance['show_network']	= $new_instance['show_network'];
		else
			$instance['show_network']	= '';

		if (isset($new_instance['items_number']))
			$instance['items_number']	= $new_instance['items_number'];

		if ((isset($new_instance['show_y_scroll'])) && ($new_instance['show_y_scroll'] == "on"))
			$instance['show_y_scroll']	= $new_instance['show_y_scroll'];
		else
			$instance['show_y_scroll']	= '';

	    return $instance;
	}


	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {
			
		/* Default widget settings. */
		$defaults = array( 
			'title' 				=> 	'',
			'show_avatar'			=>	'on',
			'show_network'			=>	'on',
			'height'				=>	'200px',
			'height_other'			=>	'',
			'items_number'			=>	'25',
			'show_y_scroll'			=>	'',
			'content_types'			=>	array('post'),
			'content_terms'			=>	array()
		);

		$instance = wp_parse_args( (array) $instance, $defaults ); 
		?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Widget Title:', LIVE_STREAM_I18N_DOMAIN); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" 
				value="<?php echo $instance['title']; ?>" class="widefat" type="text" />
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_avatar'], 'on' ); ?> 
				id="<?php echo $this->get_field_id( 'show_avatar' ); ?>" name="<?php echo $this->get_field_name( 'show_avatar' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_avatar' ); ?>"><?php _e('Show Author Avatar?', LIVE_STREAM_I18N_DOMAIN); ?></label>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_network'], 'on' ); ?> 
				id="<?php echo $this->get_field_id( 'show_network' ); ?>" name="<?php echo $this->get_field_name( 'show_network' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_network' ); ?>"><?php 
				_e('Show content for all Blogs? <em>Unchecked = show current blog</em>', LIVE_STREAM_I18N_DOMAIN); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php 
				_e('Widget Height', LIVE_STREAM_I18N_DOMAIN); ?></label>
				
			<select id="<?php echo $this->get_field_id( 'height' ); ?>" 
				name="<?php echo $this->get_field_name( 'height'); ?>" class="widefat" style="width:100%;">
				<option value="200px" <?php if ($instance['height'] == "200px") { echo ' selected="selected" '; }?>><?php 
					_e('200px - approx. 2-3 items', LIVE_STREAM_I18N_DOMAIN); ?></option>
				<option value="350px" <?php if ($instance['height'] == "350px") { echo ' selected="selected" '; }?>><?php 
					_e('350px - approx. 4-5 items', LIVE_STREAM_I18N_DOMAIN); ?></option>
				<option value="500px" <?php if ($instance['height'] == "500px") { echo ' selected="selected" '; }?>><?php 
					_e('500px - approx. 6-8 items', LIVE_STREAM_I18N_DOMAIN); ?></option>
				<option value="other" <?php if ($instance['height'] == "other") { echo ' selected="selected" '; }?>><?php 
						_e('other - provide your own height', LIVE_STREAM_I18N_DOMAIN); ?></option>
			</select>
			<div id="<?php echo $this->get_field_id( 'height_other' ); ?>-wrapper" 
				<?php if ($instance['height'] != "other") { echo ' style="display:none;" '; } ?> >
				<label for="<?php echo $this->get_field_id( 'height_other' ); ?>"><?php 
					_e('Specify Widget Height: <em>include px, em, etc. qualifies i.e. 300px</em>', LIVE_STREAM_I18N_DOMAIN); ?></label>
				<input id="<?php echo $this->get_field_id( 'height_other' ); ?>" name="<?php echo $this->get_field_name( 'height_other' ); ?>" 
					value="<?php echo $instance['height_other']; ?>" style="width:97%;" />
			</div>
			<script type="text/javascript">
				jQuery('select#<?php echo $this->get_field_id( 'height' ); ?>').change(function() {
					if (jQuery(this).val() == "other") {
						jQuery('#<?php echo $this->get_field_id( 'height_other' ); ?>-wrapper').show();
					} else {
						jQuery('#<?php echo $this->get_field_id( 'height_other' ); ?>-wrapper').hide();							
					}
				});
			</script>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'items_number' ); ?>"><?php 
				_e('Total items. The items will loop continuously.', LIVE_STREAM_I18N_DOMAIN); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'items_number' ); ?>" 
				id="<?php echo $this->get_field_id( 'items_number' ); ?>" 
				value="<?php echo $instance['items_number']; ?>" class="widefat" />
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_y_scroll'], 'on' ); ?> 
				id="<?php echo $this->get_field_id( 'show_y_scroll' ); ?>" name="<?php echo $this->get_field_name( 'show_y_scroll' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_y_scroll' ); ?>"><?php _e('Show Vertical Scollbar?', LIVE_STREAM_I18N_DOMAIN); ?></label>
		</p>
		
		<?php
			$content_types = live_stream_get_content_types();
			//echo "content_types<pre>"; print_r($content_types);
			if (($content_types) && (count($content_types))) {
				sort($content_types);
				?>
				<p>
					<label for="<?php echo $this->get_field_id( 'content_types' ); ?>"><?php 
						/* _e('Types: <em>Shift+click to select multiple</em>', LIVE_STREAM_I18N_DOMAIN); */ ?></label> 
					<select id="<?php echo $this->get_field_id( 'content_types' ); ?>" <?php /* multiple="multiple" */ ?>
						class="widget-live-stream-content-types"						
						size="1 <?php /* if (count($content_types) < 5) { echo count($content_types)+1; } else { echo "5"; } */ ?>"
						name="<?php echo $this->get_field_name( 'content_types' ); ?>[]" class="widefat" style="width:100%;">
						<option value="" <?php if (!count($instance['content_types'])) { echo ' selected="selected" '; } ?>><?php 
							_e('All Types', LIVE_STREAM_I18N_DOMAIN); ?></option>
						<?php
							foreach($content_types as $content_type) {
								?><option value="<?php echo $content_type; ?>" <?php 
								if (array_search($content_type, $instance['content_types']) !== false) 
								{ echo ' selected="selected" '; } ?>><?php echo $content_type; ?></option><?php						
							}
						?>
					</select>
				</p>
				<?php
			}

			$content_terms = live_stream_get_content_terms();
			if (($content_terms) && (count($content_terms))) {
				ksort($content_terms);

				$term_count = count($content_terms) +1 ;
				foreach($content_terms as $term_group => $terms) {
					$term_count += count($terms);
				}
				?>
				<p>
					<label for="<?php echo $this->get_field_id( 'content_terms' ); ?>"><?php 
						/* _e('Terms: <em>Shift+click to select multiple</em>', LIVE_STREAM_I18N_DOMAIN); */ ?></label> 
					<select id="<?php echo $this->get_field_id( 'content_terms' ); ?>" <?php /* multiple="multiple" */ ?>
						class="widget-live-stream-content-terms"
						size="1<?php /* if ($term_count < 15) { echo $term_count; } else { echo "15"; } */ ?>"
						name="<?php echo $this->get_field_name( 'content_terms'); ?>[]" class="widefat" style="width:100%;">
						<option value="" <?php if (!count($instance['content_terms'])) { echo ' selected="selected" '; } ?>><?php
							_e('All Terms', LIVE_STREAM_I18N_DOMAIN); ?></option>

						<?php
							foreach($content_terms as $term_group => $terms) {
								if (!count($terms)) continue;
								asort($terms);
								
								?><optgroup label="<?php echo $term_group; ?>"><?php

								foreach($terms as $term) {
									?><option value="<?php echo $term->term_id; ?>" <?php 
									if (array_search($term->term_id, $instance['content_terms']) !== false) 
									{ echo ' selected="selected" '; } ?>><?php echo $term->name; ?></option><?php						
								}
							
								?></optgroup><?php
							}
						?>
					</select>
				</p>
				<?php				
			}
	}
}


/**
 * This function queries the wp_site_posts and wp_site_comments tables setup by PostIndexer and CommentIndexer plugins
 * The result is an array of post_types (post, page, book) and merged into this another item 'comment' if matches are found
 * based on the blog_id, user_id, etc.
 *
 * @since 1.0.0
 * @see 
 *
 * @param none
 * @return array of post_types
 */

function live_stream_get_content_types() {

	global $wpdb;

	$content_types = array();

	if ( $content_types = get_transient( 'live_stream_widget_content_types' ) ) {
		return $content_types;
	}

	/* First query the post_types from the wp_site_posts table */
	$select_query_str = "SELECT post_type FROM ". $wpdb->base_prefix . "site_posts ";

	//$where_query_str = 'WHERE 1';
	$where_query_str = 'WHERE post_type="post" ';	// We want to initially only allow Posts and exclude all other post types. 


	$groupby_query_str = " GROUP BY post_type";

	$query_str = $select_query_str . $where_query_str . $groupby_query_str;
	$posts_types = $wpdb->get_col($query_str);

	if (($posts_types) && (count($posts_types))) {
		$content_types = array_merge($content_types, $posts_types);
	}

	/* Next, query the wp_site_commencts table to check if any items match the criteria */
	$select_query_str = "SELECT site_comment_id FROM ". $wpdb->base_prefix . "site_comments ";
	$where_query_str = 'WHERE 1';

	$query_str = $select_query_str . $where_query_str;
	$comment_types = $wpdb->get_col($query_str);

	if (($comment_types) && (count($comment_types))) {
		/* since the wp_site_comments table does not have an actual post_type fields we create a 
			dummy array item to merge the all_types array */
		$comment_array = array('comment');
		$content_types = array_merge($content_types, $comment_array);
	}

	/* finally sort the array so they show in alpha order */
	if ((isset($content_types)) && (count($content_types)))
		sort($content_types);

	set_transient( 'live_stream_widget_content_types', $content_types, 300 );
	
	return $content_types;
}

/**
 * This function queries the wp_site_terms table setup by PostIndexer plugins
 * The result will be a multi-level array. The top node will be the term_group to represent the taxonomy. The second node is the terms for the taxonomy
 * based on the blog_id, user_id, etc.
 *
 * @since 1.0.0
 * @see 
 *
 * @param none
 * @return array of post_terms
 */

function live_stream_get_content_terms() {
	
	global $wpdb;

	$all_terms = array();

	if ( $all_terms = get_transient( 'live_stream_widget_content_terms' ) ) {
		return $all_terms;
	}

	$select_query_str = "SELECT * FROM ". $wpdb->base_prefix . "site_terms ";
	$where_query_str = "WHERE type IN ('category', 'post_tag') ";
	
	$query_str = $select_query_str . $where_query_str;
	//echo "query_str=[". $query_str ."]<br />";
	$post_terms = $wpdb->get_results($query_str);

	if (($post_terms) && (count($post_terms))) {
		//echo "post_terms<pre>"; print_r($post_terms); echo "</pre>";

		foreach($post_terms as $post_term) {
			if ($post_term->type == "post_tag")
				$post_term->type = __("Tags", LIVE_STREAM_I18N_DOMAIN);
			if ($post_term->type == "category")
				$post_term->type = __("Categories", LIVE_STREAM_I18N_DOMAIN);
				
			if (!isset($all_terms[$post_term->type]))
				$all_terms[$post_term->type] = array();
			
			$all_terms[$post_term->type][$post_term->name] = $post_term;
		}
	}
	set_transient( 'live_stream_widget_content_terms', $all_terms, 300 );

	return $all_terms;
}

/**
 * Get the user_id of users for current blog. This will be used to filter the displayed items. 
 *
 * @since 1.0.0
 * @see 
 *
 * @param none
 * @return array of post_terms
 */

function live_stream_get_site_user_ids() {
	global $wpdb;
	
	if ( $user_ids = get_site_transient( 'live_stream_widget_user_ids' ) ) {
		return $user_ids;
	}

	$user_args = array(
		'number' 	=> 	0,
		'blog_id'	=> 	$wpdb->blogid,
		'fields'	=>	array('ID')
	);
		
	$wp_user_search = new WP_User_Query( $user_args );
	$users_tmp = $wp_user_search->get_results();
	if ($users_tmp) {
		$user_ids = array();
		foreach($users_tmp as $user) {
			$user_ids[] = $user->ID;
		}
		set_site_transient( 'live_stream_widget_user_ids', $user_ids, 300);

		return $user_ids;
	}
}
/**
 * This function queries the site post and comments tables populated by the PostIndexer and CommentIndexer plugins
 * The queries result is used to display the front-end items to the users. 
 *
 * @since 1.0.0
 * @see 
 *
 * @param $instance Widget instance
 * @return array of post_terms
 */

function live_stream_get_post_items($instance, $widget_id=0) {
	
	global $wpdb;
		
	$post_items = array();
	$comment_items = array();
	$post_ids = array();
	$terms_query_str = '';
	$all_items = array();
	
	if ( $all_items = get_site_transient( 'live_stream_widget_content_item_'. $widget_id ) ) {
		return $all_items;
	}

	$user_ids = live_stream_get_site_user_ids();

	if ( (isset($instance['content_terms'])) && (count($instance['content_terms'])) ) {
	
		foreach($instance['content_terms'] as $term_id) {
			if (strlen($terms_query_str)) $terms_query_str .= " OR ";
			$terms_query_str .= " p.post_terms like '%|". $term_id ."|%' ";
		}
	}
	
	// Some defaults for us. 
	if (!count($instance['content_types'])) {
		$instance['content_types'] = array('post', 'comment');
	}
	
	if ((!isset($instance['items_number'])) || (intval($instance['items_number']) < 2))
		$instance['items_number'] = 2;
	
	if ( (isset($instance['content_types'])) && (array_search('post', $instance['content_types']) !== false) ) {
	
		$select_query_str 	= "SELECT p.blog_id as blog_id, p.site_id as site_id, p.post_id as post_id, p.post_author as post_author_id, p.post_type as post_type, p.post_title as post_title, p.post_permalink as post_permalink, p.post_published_stamp as post_published_stamp FROM ". $wpdb->base_prefix . "site_posts p";		
		$where_query_str 	= "WHERE 1";

		if ((isset($user_ids)) && (count($user_ids))) {
			$where_query_str .= " AND p.post_author IN (". implode(',', $user_ids) .") ";			
		}

		if (strlen($terms_query_str)) {
			$where_query_str .= " AND (". $terms_query_str .") ";
		}
			
		if ($instance['show_network'] != "on")
			$where_query_str .= " AND p.blog_id=". $wpdb->blogid ." ";

		$content_types_str = '';
		if ((isset($instance['content_types'])) && (count($instance['content_types']))) {
			foreach($instance['content_types'] as $type) {
				if (strlen($content_types_str)) $content_types_str .= ",";
				$content_types_str .= "'". $type ."'";
			}
			if (strlen($content_types_str))
				$where_query_str .= " AND p.post_type IN (". $content_types_str .") ";
		}

		if (isset($instance['timekey'])) {
			if (strlen($content_types_str))
				$where_query_str .= " AND ";
			$where_query_str .= " p.post_published_stamp > ". $instance['timekey'];		
		}

		$orderby_query_str 	= " ORDER BY p.post_published_stamp DESC";
		$limit_query_str = " LIMIT ". $instance['items_number'];
			
		$query_str = $select_query_str ." ". $where_query_str ." ". $orderby_query_str ." ". $limit_query_str;
		//echo "query_str=[". $query_str ."]<br /><br />";
		$post_items = $wpdb->get_results($query_str);
		if ((isset($post_items)) && (count($post_items))) {
			foreach($post_items as $item) {
				$post_ids[] = $item->post_id;			
				$all_items[$item->post_published_stamp] = $item;
			}
		}
	}

	/* Get the comments */
	if ( (isset($instance['content_types'])) && (array_search('comment', $instance['content_types']) !== false) ) {
		
		$select_query_str = "SELECT c.blog_id as blog_id, c.site_id as site_id, c.comment_post_id as post_id, c.comment_author_user_id as post_author_id, c.comment_author as post_author_name, c.comment_author_email as post_author_email, p.post_title as post_title, c.comment_post_permalink as post_permalink, c.comment_date_stamp as post_published_stamp, c.comment_id as comment_id FROM ". $wpdb->base_prefix . "site_comments c INNER JOIN ". $wpdb->base_prefix . "site_posts p ON c.comment_post_id=p.post_id"; 
					
		$where_query_str = 'WHERE 1';

		if ((isset($user_ids)) && (count($user_ids))) {
			$where_query_str .= " AND c.comment_author_user_id IN (". implode(',', $user_ids) .") ";			
		}
	
		if ($instance['show_network'] != "on")
			$where_query_str .= " AND c.blog_id=". $wpdb->blogid ." ";

		if (isset($instance['timekey'])) {
			if (strlen($content_types_str))
				$where_query_str .= " AND ";
			$where_query_str .= " c.comment_date_stamp > ". $instance['timekey'];
		}

		if ( (isset($terms_query_str)) && (strlen($terms_query_str)) ) {
			$where_query_str .= " AND (". $terms_query_str .") ";
		}
		
		$orderby_query_str = ' ORDER BY c.comment_date_stamp DESC';
		$limit_query_str = " LIMIT ". $instance['items_number'];
		
		$query_str = $select_query_str ." ". $where_query_str ." ". $orderby_query_str ." ". $limit_query_str;
		//echo "query_str=[". $query_str ."]<br />";
		$comment_items = $wpdb->get_results($query_str);

		if ($comment_items) {
			foreach($comment_items as $item) {

				$item->post_type 							= "comment";
				$all_items[$item->post_published_stamp]		= $item;
			}
		}
	}
	if (($all_items) && (count($all_items)))
		krsort($all_items);
	
	set_site_transient( 'live_stream_widget_content_item_'. $widget_id, $all_items, 30 );
	
	return $all_items;	
}

/**
 * This function is given an array of items in which will be build the output list items for display
 *
 * @since 1.0.0
 * @see 
 *
 * @param object $instance This is the Widget instance. 
 * @param array $items The items result from the live_stream_get_post_items(); return;
 * @param bool $echo true to echo the output or false to return the output
 * @return string $items_output returned IF $echo is false.
 */

function live_stream_build_display($instance, $items, $echo = true) {
	
	if ( (!$items) || (!is_array($items)) || (!count($items)) ) return;
	
	krsort($items);
	$items_output = '';
	
	//echo "instance<pre>"; print_r($instance); echo "</pre>";
	
	//$blogs = live_stream_get_blogs();	
	$blogs = array();
		
	foreach($items as $key => $item) {
		//echo "item<pre>"; print_r($item); echo "</pre>";
		
		if ((isset($item->blog_id)) && (intval($item->blog_id))) {
			
			if (isset($blogs[intval($item->blog_id)])) {
				$blog = $blogs[intval($item->blog_id)];
			} else {
				$blog = get_blog_details($item->blog_id);
				if ($blog) {
					$blogs[intval($item->blog_id)] = $blog;
				} else {
					unset($blog);
				}
			}
		}
		
		if ((isset($instance['show_avatar'])) && ($instance['show_avatar'] == "on")) {
			$wrapper_class = " live-stream-text-has-avatar";
		} else {
			$wrapper_class = "";
		}

		if ((isset($instance['doing_ajax'])) && ($instance['doing_ajax'] == "true")) {
			//$wrapper_style = ' style="display: none;" ';
			$wrapper_style = "";
		} else {
			$wrapper_style = "";
		}
		
		$item_output = '<li id="live-stream-item-'. $key .'" class="live-stream-item '. $wrapper_class .'" '. $wrapper_style .'>';
		
		if (intval($item->post_author_id) )
			$user_data = get_userdata( intval($item->post_author_id) );
		else { 
			$user_data->email 			= '';
			$user_data->display_name 	= '';
			
			if ((isset($item->post_author_email)) && (strlen($item->post_author_email))) {
				$user_data->email = $item->post_author_email;
			}
			if ((isset($item->post_author_name)) && (strlen($item->post_author_name))) {
				$user_data->display_name = $item->post_author_name;
			}
		}
		
		/* Build an anchor wrapper for the author which is used in multiple places */
		if ((isset($blog->siteurl)) && (intval($item->post_author_id) )) {
			$author_anchor_begin 	= '<a href="'. $blog->siteurl .'?author='
				. $item->post_author_id .'">';
			$author_anchor_end 		= '</a>';
			
		} else {
			if ($item->post_type == "comment") {
				$author_anchor_begin 	= '<a href="'. $item->post_permalink .'#comment-'. $item->comment_id .'">';
				$author_anchor_end 		= '</a>';
			} else {			
				$author_anchor_begin 	= '';
				$author_anchor_end 		= '';
			}
		}
		
		/* User Avatar */
		if ((isset($instance['show_avatar'])) && ($instance['show_avatar'] == "on")) {
			$avatar = get_avatar($user_data->email, 30, null, $user_data->display_name);
			$item_output .= '<div class="live-stream-avatar">';
			$item_output .= $author_anchor_begin . $avatar . $author_anchor_end;
			$item_output .= '</div>';	
		}
		
		/* Begine text container wrapper */
		$item_output .= '<div class="live-stream-text">';

			/* Show the User Name */						
			$item_output .= $author_anchor_begin . $user_data->display_name . $author_anchor_end ." ";

			if ($item->post_type == "comment") {
				$item_output .= __(" commented on ", LIVE_STREAM_I18N_DOMAIN); ;
				
				/* Show the Post Title */
				if (isset($blogs[$item->blog_id])) {
					$post_anchor_begin 	= '<a href="'. $item->post_permalink .'#comment-'. $item->comment_id .'">';
					$post_anchor_end 	= '</a>';

				} else {
					$post_anchor_begin 	= '';
					$post_anchor_end 	= '';
				}
				
				$item_output .= $post_anchor_begin . $item->post_title . $post_anchor_end ." ";
				
			} else {
				$item_output .= " ". __('published', LIVE_STREAM_I18N_DOMAIN) ." ";

				/* Show the Post Title */
				$item_output .= '<a href="'. $item->post_permalink .'">'. $item->post_title ."</a> ";
			}
		
		
			/* Show the Blog domain */
			if ((isset($instance['show_network'])) && ($instance['show_network'] == "on")) {
				if (isset($blog->siteurl)) {
					$site_anchor_begin = '<a href="'. $blog->siteurl .'">';
					$site_anchor_end	= '</a>';
					$item_output .= "on ". $site_anchor_begin . $blog->blogname . $site_anchor_end ." ";
				}
			}
		
		
			/* Show the Post/Comment human time */
			$item_output .= '<div class="live-stream-text-footer">';
			$item_output .= '<span class="live-stream-text-footer-date">'. sprintf( __( '%s ago ', LIVE_STREAM_I18N_DOMAIN ), 
				human_time_diff( $item->post_published_stamp ) ) .'</span>';
			$item_output .= ' &middot; ';
			$item_output .= '<a class="live-stream-text-footer-date" href="'. $item->post_permalink .'">'. __('visit', LIVE_STREAM_I18N_DOMAIN) .'</a>';
			$item_output .= '</div>';


			
		/* Closing the item text wrapper */
		$item_output .= '</div>';

		$item_output .= '</li>';
			
		$items_output .= $item_output;
	}
	
	if (strlen($items_output)) {
		if ($echo == true)
			echo $items_output;
		else
			return $items_output;
	}		
}


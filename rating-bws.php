<?php
/*
Plugin Name: Rating by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/rating/
Description: Add rating plugin to your WordPress website to receive feedback from your customers.
Author: BestWebSoft
Text Domain: rating-bws
Domain Path: /languages
Version: 0.2
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2017  BestWebSoft  ( https://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful, 
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Add BWS menu */
if ( ! function_exists( 'rtng_add_pages' ) ) {
	function rtng_add_pages() {
		bws_general_menu();
		$settings = add_submenu_page( 'bws_panel', __( 'Rating Settings', 'rating-bws' ), 'Rating', 'manage_options', 'rating.php', 'rtng_settings_page' );
		add_action( 'load-' . $settings, 'rtng_add_tabs' );
	}
}
/* Internationalization*/
if ( ! function_exists( 'rtng_plugins_loaded' ) ) {
	function rtng_plugins_loaded() {
		/* Internationalization, first( ! ) */
		load_plugin_textdomain( 'rating-bws', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/* Initialization */
if ( ! function_exists( 'rtng_init' ) ) {
	function rtng_init() {
		global $rtng_plugin_info, $rtng_options;
		if ( empty( $rtng_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$rtng_plugin_info = get_plugin_data( __FILE__ );
		}

		/* add general functions */
		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		
		/* check compatible with current WP version */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $rtng_plugin_info, '3.8' );

		/* Get/Register and check settings for plugin */	
		if ( ! is_admin() || ( isset( $_GET['page'] ) && 'rating.php' ==  $_GET['page'] ) ) {
			rtng_settings();
		}

		if ( ! is_admin() ) {
			if ( in_array( 'in_comment', $rtng_options['rate_position'] ) ) {				
				/* comment_post is an action triggered immediately after a comment is inserted into the database. */
				add_action( 'comment_post', 'rtng_add_rating_db_comment', 10, 2 );
				add_action( 'comment_form_logged_in_after', 'rtng_show_rating_form' );
				/* comment_text - Displays the text of a comment. */
				add_action( 'comment_text', 'rtng_show_comment_rating' );
			}

			if ( ! empty( $rtng_options['average_position'] ) || 
				in_array( 'before', $rtng_options['rate_position'] ) ||
				in_array( 'after', $rtng_options['rate_position'] ) )
				add_filter( 'the_content', 'rtng_add_rating_to_content' );
		}
	}
}

/* Function for admin_init */
if ( ! function_exists( 'rtng_admin_init' ) ) {
	function rtng_admin_init() {
		/* Add variable for bws_menu */
		global $bws_plugin_info, $rtng_plugin_info, $bws_shortcode_list;		
		/* Function for bws menu */
		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array( 'id'  => '630', 'version'  => $rtng_plugin_info["Version"] );
		}
		/* add Plugin to global $bws_shortcode_list */
		$bws_shortcode_list['rtng'] = array( 'name'  => 'Rating' );
	}
}

/* Register settings function*/
if ( ! function_exists( 'rtng_settings' ) ) {
	function rtng_settings() {
		global $rtng_options, $rtng_plugin_info;
		$db_version = '1.0';
		/* Install the option defaults */
		if ( ! get_option( 'rtng_options' ) ) {
			$options_default = rtng_get_default_options();		
			add_option( 'rtng_options', $options_default );
		}

		/* Get options from the database */
		if ( empty( $rtng_options ) )
			$rtng_options = get_option( 'rtng_options' );

		if ( ! isset( $rtng_options['plugin_option_version'] ) || $rtng_options['plugin_option_version'] != $rtng_plugin_info["Version"] ) {
			$options_default = rtng_get_default_options();
			$rtng_options = array_merge( $options_default, $rtng_options );
			$rtng_options['plugin_option_version'] = $rtng_plugin_info["Version"];
			$update_option = true;
		}
		if ( ! isset( $rtng_options['plugin_db_version'] ) || ( isset( $rtng_options['plugin_db_version'] ) && $rtng_options['plugin_db_version'] != $db_version ) ) {
			rtng_db_create();
			$rtng_options['plugin_db_version'] = $db_version;
			$update_option = true;
		}

		if ( isset( $update_option ) ) {
			update_option( 'rtng_options', $rtng_options );
		}
	}
}

/* Register default settings function*/
if ( ! function_exists( 'rtng_get_default_options' ) ) {
	function rtng_get_default_options( $is_network_admin = false ) {
		global $rtng_plugin_info;

		$default_options = array( 
			'plugin_option_version'		=> $rtng_plugin_info["Version"], 
			'display_settings_notice'	=> 1, 
			'suggest_feature_banner'	=> 1, 
			'use_post_types'	 		=> array( 'post' ),
			'average_position'			=> array( 'after' ),
			'combined'					=> 0,
			'rate_position'				=> array( 'after' ),
			'rate_color'				=> '#ffb900',
			'rate_hover_color'			=> '#ff7f00',
			'rate_size'					=> 20,
			'text_color'				=> '#777777',
			'text_size'					=> '18',
			'result_title'				=>	__( 'Average Rating', 'rating-bws' ),
			'vote_title'				=>	__( 'My Rating', 'rating-bws' ) . ':',
			'total_message'				=>	sprintf( __( '%s out of 5 stars. %s votes.', 'rating-bws' ), '{total_rate}', '{total_count}' ),			
			'non_login_message'			=> esc_html( sprintf( __( 'You must %s to submit a review.', 'rating-bws' ), '{login_link="' . __( 'log in', 'rating-bws' ) . '"}' ) ),
			'thankyou_message'			=> __( 'Thank you!', 'rating-bws' ),
		 );

		return $default_options;
	}
}


/* Performed at activation */
if ( ! function_exists( 'rtng_db_create' ) ) {
	function rtng_db_create() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		/**
		 * Contains templates for pdf-documents
		 * object_id        user ID  or comment ID
		 * post_id      	post ID
		 * rating        	rating
		 * object_type     	type-post or comment
		 * datetime       	date of creation
		 */
		$sql_query  = 
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bws_rating` ( 
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
			`object_id` INT( 10 ) NOT NULL, 
			`post_id` INT( 10 ) NOT NULL, 
			`rating` INT( 2 ) NOT NULL DEFAULT '0', 
			`datetime` DATETIME NOT NULL, 
			`object_type` ENUM( 'post', 'comment' ), 
			PRIMARY KEY ( `id` )
			 ) ENGINE = InnoDB DEFAULT CHARSET = utf8;";
		dbDelta( $sql_query );
	}
}

/* Function formed content of the plugin's admin page. */
if ( ! function_exists( 'rtng_settings_page' ) ) {
	function rtng_settings_page() {
		global $rtng_options, $rtng_plugin_info;
		$message = $error = "";
		$plugin_basename = plugin_basename( __FILE__ );
		$all_post_types = get_post_types( array( 'public'  => 1, 'show_ui'  => 1 ), 'objects' );

		if ( isset( $_REQUEST['rtng_form_submit'] ) && check_admin_referer( $plugin_basename, 'rtng_nonce_name' ) ) {

			if ( ! isset( $_GET['action'] ) ) {
				$rtng_options['use_post_types'] = isset( $_REQUEST['rtng_use_post_types'] ) ? $_REQUEST['rtng_use_post_types'] : array();
				foreach ( (array)$rtng_options['use_post_types'] as $key => $post_type ) {
					if ( ! array_key_exists( $post_type, $all_post_types ) )
						unset( $rtng_options['use_post_types'][ $key ] );
				}
				$rtng_options['average_position'] = isset( $_REQUEST['rtng_average_position'] ) ? $_REQUEST['rtng_average_position'] : array();
				foreach ( (array)$rtng_options['average_position'] as $key => $position ) {
					if ( ! in_array( $position , array( 'before', 'after' ) ) )
						unset( $rtng_options['average_position'][ $key ] );
				}
				$rtng_options['combined'] = isset( $_REQUEST['rtng_combined'] ) ? 1 : 0;				
				$rtng_options['rate_position'] = isset( $_REQUEST['rtng_rate_position'] ) ? $_REQUEST['rtng_rate_position'] : array();
				if ( in_array( 'in_comment', (array)$rtng_options['rate_position'] ) ) {
					$rtng_options['rate_position'] = array( 'in_comment' );
				} else {
					foreach ( (array)$rtng_options['rate_position'] as $key => $position ) {
						if ( ! in_array( $position , array( 'before', 'after' ) ) )
							unset( $rtng_options['rate_position'][ $key ] );
					}
				}
			} else {
				$rtng_options['rate_color'] = stripslashes( esc_html( $_REQUEST['rtng_rate_color'] ) );
				$rtng_options['rate_hover_color'] = stripslashes( esc_html( $_REQUEST['rtng_rate_hover_color'] ) );
				$rtng_options['rate_size'] = intval( $_REQUEST['rtng_rate_size'] );

				$rtng_options['text_color'] = stripslashes( esc_html( $_REQUEST['rtng_text_color'] ) );
				$rtng_options['text_size'] = intval( $_REQUEST['rtng_text_size'] );

				$rtng_options['result_title'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_result_title'] ) );
				$rtng_options['total_message'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_total_message'] ) );
				$rtng_options['vote_title'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_vote_title'] ) );
				$rtng_options['non_login_message'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_non_login_message'] ) );
				$rtng_options['thankyou_message'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_thankyou_message'] ) );		
			}
			$message = __( 'Settings saved.', 'rating-bws' );
			update_option( 'rtng_options', $rtng_options );
		}

		/* add restore function */
		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
			$rtng_options = rtng_get_default_options();
			update_option( 'rtng_options', $rtng_options );
			$message = __( 'All plugin settings were restored.', 'rating-bws' );
		} ?>
		<div class="wrap">
			<h1><?php _e( 'Rating Settings', 'rating-bws' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=rating.php"><?php _e( 'Settings', 'rating-bws' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'appearance' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=rating.php&amp;action=appearance"><?php _e( 'Appearance', 'rating-bws' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'custom_code' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=rating.php&amp;action=custom_code"><?php _e( 'Custom code', 'rating-bws' ); ?></a>
			</h2>
			<div class="updated fade below-h2" <?php if ( empty( $message ) || "" != $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( "" ==  $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php bws_show_settings_notice();
			if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
				bws_form_restore_default_confirm( $plugin_basename );
			} else {
				if ( ! isset( $_GET['action'] ) ) { ?>
					<br>
					<div><?php $icon_shortcode = plugins_url( 'bws_menu/images/shortcode-icon.png', __FILE__ );
					printf( 
						__( "If you would like to add rating to your page or post, please use %s button", 'rating-bws' ), 
						'<span class="bws_code"><span class="bwsicons bwsicons-shortcode"></span></span>' ); ?> 
						<div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help">
							<div class="bws_hidden_help_text" style="min-width: 180px;">
								<?php printf( 
									__( "You can add rating to your page or post by clicking on %s button in the content edit block using the Visual mode. If the button isn't displayed, please use the shortcode %s.", 'rating-bws' ), 
									'<span class="bws_code"><span class="bwsicons bwsicons-shortcode"></span></span>', 
									'<code>[bws-rating]</code>'
								 ); ?>
							</div>
						</div>
					</div>
					<form method="post" action="" enctype="multipart/form-data" class="bws_form">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Display rating', 'rating-bws' ); ?></th>
								<td>
									<fieldset>
										<?php foreach ( $all_post_types as $key => $value  ) { ?>
											<label>
												<input type="checkbox" name="rtng_use_post_types[]" value="<?php echo $key; ?>" <?php if ( in_array( $key, $rtng_options['use_post_types'] ) ) echo 'checked="checked"'; ?> /> 
												<?php echo $value->label; ?>
											</label>
											<br/>
										<?php } ?>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Average rating position', 'rating-bws' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" name="rtng_average_position[]" value="before" <?php if ( in_array( 'before', $rtng_options['average_position'] ) ) echo 'checked="checked"'; ?> /> 
												<?php _e( 'before the content', 'rating-bws' ); ?>
										</label>
										<br/>
										<label>
											<input type="checkbox" name="rtng_average_position[]" value="after" <?php if ( in_array( 'after', $rtng_options['average_position'] ) ) echo 'checked="checked"'; ?> /> 
												<?php _e( 'after the content', 'rating-bws' ); ?>
										</label>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Combine average rating with a rate option', 'rating-bws' ); ?></th>
								<td>
									<input type="checkbox" name="rtng_combined" value="1" <?php if ( 1 == $rtng_options['combined'] ) echo 'checked="checked"'; ?> />
								</td>
							</tr>
							<tr id="rtng_rate_position">
								<th><?php _e( 'Rate option position', 'rating-bws' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" name="rtng_rate_position[]" value="before" <?php if ( in_array( 'before', $rtng_options['rate_position'] ) ) echo 'checked="checked"'; ?> /> 
												<?php _e( 'before the content', 'rating-bws' ); ?>
										</label>
										<br/>
										<label>
											<input type="checkbox" name="rtng_rate_position[]" value="after" <?php if ( in_array( 'after', $rtng_options['rate_position'] ) ) echo 'checked="checked"'; ?> /> 
												<?php _e( 'after the content', 'rating-bws' ); ?>
										</label>
										<br/>
										<label>
											<input type="checkbox" name="rtng_rate_position[]" value="in_comment" <?php if ( in_array( 'in_comment', $rtng_options['rate_position'] ) ) echo 'checked="checked"'; ?> /> 
												<?php _e( 'in comments', 'rating-bws' ); ?>
										</label>
									</fieldset>
								</td>
							</tr>
						</table>
						<p class="submit">
							<input type="hidden" name="rtng_form_submit" value="submit" />
							<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'rating-bws' ); ?>" />
							<?php wp_nonce_field( $plugin_basename, 'rtng_nonce_name' ); ?>
						</p>
					</form>
					<?php bws_form_restore_default_settings( $plugin_basename );
				} elseif ( 'appearance' == $_GET['action'] ) { ?>
					<form method="post" action="" enctype="multipart/form-data" class="bws_form">
						<table class="form-table">
							<tr>
								<th><?php _e( 'Star color', 'rating-bws' ); ?></th>
								<td>
									<input type="text" class="rtng_color" value="<?php echo $rtng_options['rate_color']; ?>" name="rtng_rate_color" data-default-color="#ffb900" />
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Hover color for star', 'rating-bws' ); ?></th>
								<td>
									<input type="text" class="rtng_color" value="<?php echo $rtng_options['rate_hover_color']; ?>" name="rtng_rate_hover_color" data-default-color="#ffb900" />
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Star size', 'rating-bws' ); ?></th>
								<td>
									<input type="number" min="1" max="300" value="<?php echo $rtng_options['rate_size']; ?>" name="rtng_rate_size" /> <?php _e( 'px', 'rating-bws' ); ?>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Text color', 'rating-bws' ); ?></th>
								<td>
									<input type="text" class="rtng_color" value="<?php echo $rtng_options['text_color']; ?>" name="rtng_text_color" data-default-color="#ffb900" />
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Text font-size', 'rating-bws' ); ?></th>
								<td>
									<input type="number" min="1" max="100" value="<?php echo $rtng_options['text_size']; ?>" name="rtng_text_size" /> <?php _e( 'px', 'rating-bws' ); ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Average rating title', 'rating-bws' ); ?></th>
								<td>
									<input type="text" maxlength="250" class="regular-text" value="<?php echo $rtng_options['result_title']; ?>" name="rtng_result_title" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Total message', 'rating-bws' ); ?></th>
								<td>
									<input type="text" maxlength="250" class="regular-text" value="<?php echo $rtng_options['total_message']; ?>" name="rtng_total_message" />
									<br>
									<span class="bws_info">
										<?php printf( 
											__( "Use %s to insert current rate.", 'rating-bws' ), 
											'{total_rate}'
										 ); ?> 
										 <?php printf( 
											__( "Use %s to insert rates count.", 'rating-bws' ), 
											'{total_count}'
										 ); ?>
									</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Rate option title', 'rating-bws' ); ?></th>
								<td>
									<input type="text" maxlength="250" class="regular-text" value="<?php echo $rtng_options['vote_title']; ?>" name="rtng_vote_title" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Message for non-login users', 'rating-bws' ); ?></th>
								<td>
									<input type="text" maxlength="250" class="regular-text" value="<?php echo $rtng_options['non_login_message']; ?>" name="rtng_non_login_message" />
									<br>
									<span class="bws_info">
										<?php printf( 
											__( "Use %s to insert login link.", 'rating-bws' ), 
											'{login_link="text"}'
										 ); ?>
									</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Message after adding a rate', 'rating-bws' ); ?></th>
								<td>
									<input type="text" maxlength="250" class="regular-text" value="<?php echo $rtng_options['thankyou_message']; ?>" name="rtng_thankyou_message" />
								</td>
							</tr>																			
						</table>
						<p class="submit">
							<input type="hidden" name="rtng_form_submit" value="submit" />
							<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'rating-bws' ); ?>" />
							<?php wp_nonce_field( $plugin_basename, 'rtng_nonce_name' ); ?>
						</p>
					</form>
					<?php bws_form_restore_default_settings( $plugin_basename );
				} elseif ( 'custom_code' == $_GET['action'] ) {
					bws_custom_code_tab();
				}
			}	
			bws_plugin_reviews_block( $rtng_plugin_info['Name'], 'rating-bws' ); ?>
		</div>
	<?php }
}

/* Positioning in the page/post/comment */
if ( ! function_exists( 'rtng_add_rating_to_content' ) ) {
	function rtng_add_rating_to_content( $content ) {
		global $post, $rtng_options, $wp, $posts;

		if ( is_feed() )
			return $content;

		if ( is_page() ) {  /* pages */ 
			if ( ! in_array( 'page', $rtng_options['use_post_types'] ) )
				return $content;
		} elseif ( is_single() || is_attachment() ) { /* posts */ 
			$post_type = get_post_type( $post->ID );
			if ( ! in_array( $post_type, $rtng_options['use_post_types'] ) )
				return $content;
		}

		if ( in_array( 'before', $rtng_options['rate_position'] ) ||
			 in_array( 'after', $rtng_options['rate_position'] ) ) {
			if ( isset( $_POST['rtng_add_button'] ) )
				rtng_add_rating_db();
		}

		$before = $after = '';
		if ( in_array( 'before', $rtng_options['average_position'] ) )
			$before .= rtng_show_total_rating();
		if ( 1 != $rtng_options['combined'] && in_array( 'before', $rtng_options['rate_position'] ) )
			$before .= rtng_show_rating_form( 'post' );

		if ( in_array( 'after', $rtng_options['average_position'] ) )
			$after .= rtng_show_total_rating();
		if ( 1 != $rtng_options['combined'] && in_array( 'after', $rtng_options['rate_position'] ) )
			$after .= rtng_show_rating_form( 'post' );

		return $before . $content . $after;
	}
}

/* Function for showing rating form */
if ( ! function_exists( 'rtng_show_total_rating' ) ) {
	function rtng_show_total_rating( $post_id = false ) {
		global $post, $rtng_options, $wpdb;	
			
		$rating = 0;

		if ( ! $post_id )
			$post_id = $post->ID;

		$total = $wpdb->get_row( $wpdb->prepare( 
			"SELECT SUM( `rating` ) AS `rating`, COUNT(*) AS `count`
			FROM `" . $wpdb->prefix . "bws_rating` 
			WHERE `post_id` = %d",
		$post_id ), ARRAY_A );
	
		if ( ! empty( $total['count'] ) )
			$rating = round( ( $total['rating'] / $total['count'] ), 1 );
		if ( empty( $total['count'] ) )
			$total['count'] = 0;		
		$rating_block_text = '<span class="rtng-text rtng-total">' . str_replace( '{total_count}', $total['count'], str_replace( '{total_rate}', ( ( $rating / 100 ) * 5 ), $rtng_options['total_message'] ) ) . '</span>';

		if ( 1 == $rtng_options['combined'] ) {
			$current_user_id = get_current_user_id();
			$message_login = $current_user_rate = '';

			if ( empty( $current_user_id ) ) {
				$message_login = '<div class="rtng-need-login">' . preg_replace( "/{login_link=(\"|')([^(\"|')]*?)(\"|')}/", '<a href="' . wp_login_url() . '">$2</a>', htmlspecialchars_decode( $rtng_options['non_login_message'] ) ) . '</div>';
			} else {
				$current_user_rate = $wpdb->get_var( $wpdb->prepare( 
					"SELECT `rating` 
					FROM `" . $wpdb->prefix . "bws_rating` 
					WHERE `post_id` = %d 
						AND `object_id` = %d
						AND `object_type` = 'post'",
				$post_id, $current_user_id ) );
			}
			if ( ! empty( $current_user_rate ) || ! empty( $message_login ) ) {
				$rating_block = '<div class="rtng-rating-total" data-id="' . $post_id . '">
					<span class="rtng-text rtng-title">' . $rtng_options['result_title'] . '</span>'.
					rtng_display_stars( $rating ) .
					$rating_block_text .
					$message_login .
					'</div>';
			} else {		
				$rating_block = '<form action="" method="post" class="rtng-form rtng-rating-total" data-id="' . $post_id . '">
					<span class="rtng-text rtng-title">' . $rtng_options['result_title'] . '</span>' .
					rtng_display_stars( $rating, 'rtng-active' ) .					
					'<input type="hidden" name="rtng_object_type" value="post">
					<input type="hidden" name="rtng_object_id" value="' . $current_user_id . '">
					<input type="hidden" name="rtng_post_id" value="' . $post->ID . '">' .
					wp_nonce_field( plugin_basename( __FILE__ ), 'rtng_nonce_button', true, false ) .
					'<input type="submit" name="rtng_add_button" class="rtng-add-button" value="' . __( 'Rate', 'rating-bws' ) . '" />' .
					$rating_block_text .
					'</form>';
			}
		} else {

			$rating_block = '<div class="rtng-rating-total" data-id="' . $post_id . '">
				<span class="rtng-text rtng-title">' . $rtng_options['result_title'] . '</span>'.
				rtng_display_stars( $rating ) .
				$rating_block_text .
				'</div>';
		}

		return $rating_block;
	}	
}

if ( ! function_exists( 'rtng_show_rating_form' ) ) {
	function rtng_show_rating_form( $type = 'comment' ) {
		global $post, $rtng_options, $wpdb;	

		if ( is_array( $type ) || 'comment' == $type ) {
			if ( is_page() ) {  /* pages */ 
				if ( ! in_array( 'page', $rtng_options['use_post_types'] ) )
					return;
			} elseif ( is_single() || is_attachment() ) { /* posts */ 
				$post_type = get_post_type( $post->ID );
				if ( ! in_array( $post_type, $rtng_options['use_post_types'] ) )
					return;
			}
		}
			
		$current_user_id = get_current_user_id();
		$current_user_rate = false;

		if ( empty( $current_user_id ) ) {
			$message = preg_replace( "/{login_link=(\"|')([^(\"|')]*?)(\"|')}/", '<a href="' . wp_login_url() . '">$2</a>', htmlspecialchars_decode( $rtng_options['non_login_message'] ) );
			return ' <div class="rtng-need-login">' . $message . '</div>';
		} else {

			if ( is_array( $type ) || 'comment' == $type ) {
				$object_type = 'comment';
				if ( ! empty( $current_user_id ) ) {
					$current_user_rate = $wpdb->get_var( $wpdb->prepare( 
						"SELECT `" . $wpdb->prefix . "bws_rating`.`rating` 
						FROM `" . $wpdb->prefix . "bws_rating`, `" . $wpdb->prefix . "comments`
						WHERE `" . $wpdb->prefix . "comments`.`comment_post_ID` = %d 
							AND `" . $wpdb->prefix . "bws_rating`.`post_id` = %d 
							AND `" . $wpdb->prefix . "bws_rating`.`object_id` = `" . $wpdb->prefix . "comments`.`comment_id`
							AND `" . $wpdb->prefix . "bws_rating`.`object_type` = 'comment'
							AND `" . $wpdb->prefix . "comments`.`user_id` =  %d",
					$post->ID, $post->ID, $current_user_id ) );

					if ( $current_user_rate )
						return;
				}
			} else {				
				$object_type = 'post';
				if ( ! empty( $current_user_id ) ) {
					$current_user_rate = $wpdb->get_var( $wpdb->prepare( 
						"SELECT `rating` 
						FROM `" . $wpdb->prefix . "bws_rating` 
						WHERE `post_id` = %d 
							AND `object_id` = %d
							AND `object_type` = 'post'",
					$post->ID, $current_user_id ) );
				}
			}			

			$rating_block = '<span class="rtng-text rtng-vote-title">' . $rtng_options['vote_title'] . '</span>';
			$rating_block .= ( $current_user_rate ) ? rtng_display_stars( $current_user_rate ) : rtng_display_stars( 0, 'rtng-active' );

			if ( is_array( $type ) || 'comment' == $type ) {
				echo $rating_block;
			} else {
				if ( ! $current_user_rate ) {
					$rating_block .= '<input type="submit" name="rtng_add_button" class="rtng-add-button" value="' . __( 'Rate', 'rating-bws' ) . '" />
					<input type="hidden" name="rtng_object_type" value="' . $object_type . '">
					<input type="hidden" name="rtng_object_id" value="' . $current_user_id . '">
					<input type="hidden" name="rtng_post_id" value="' . $post->ID . '">' .
					wp_nonce_field( plugin_basename( __FILE__ ), 'rtng_nonce_button', true, false );

					return '<form action="" method="post" class="rtng-form" data-id="' . $post->ID . '">' . $rating_block . '</form>';
				} else {
					return '<div class="rtng-form">' . $rating_block . '</div>';
				}	
			}			
		}
	}	
}

if ( ! function_exists( 'rtng_display_stars' ) ) {
	function rtng_display_stars( $rating = 0, $class = '' ) {		
		$rating_block = '<div class="rtng-star-rating rtng-no-js ' . $class . '" data-rating="' . $rating . '">';

		$rating = ( $rating / 100 ) * 5;

		if ( '' != $class ) {
			$full_stars = floor( $rating );
			$half_stars = ceil( $rating - $full_stars );

			for ( $i = 0; $i < $full_stars; $i++ ) {
				$rating_block .= '
					<label class="rtng-star" data-rating="' . ( $i+1 ) . '">
						<input type="radio" name="rtng_rating" value="' . ( $i+1 ) . '" />
						<span class="dashicons dashicons-star-filled"></span>
					</label>';
			}			
			if ( $half_stars ) {
				$rating_block .= '
					<label class="rtng-star" data-rating="' . ( $i+1 ) . '">
						<input type="radio" name="rtng_rating" value="' . ( $i+1 ) . '" />
						<span class="dashicons dashicons-star-half"></span>
					</label>';
				$full_stars++;
			}			
			for ( $j = $full_stars; $j < 5; $j++ ) {
				$rating_block .= '<label class="rtng-star" data-rating="' . ( $j+1 ) . '">
					<input type="radio" name="rtng_rating" value="' . ( $j+1 ) . '" />
					<span class="dashicons dashicons-star-empty"></span>					
				</label>';
			}	
		} else {
			$full_stars = floor( $rating );
			$half_stars = ceil( $rating - $full_stars );

			for ( $i = 0; $i < $full_stars; $i++ ) {
				$rating_block .= '
					<div class="rtng-star" data-rating="' . ( $i+1 ) . '">
						<span class="dashicons dashicons-star-filled"></span>
					</div>';
			}			
			if ( $half_stars ) {
				$rating_block .= '
					<div class="rtng-star" data-rating="' . ( $i+1 ) . '">
						<span class="dashicons dashicons-star-half"></span>
					</div>';
				$full_stars++;
			}			
			for ( $j = $full_stars; $j < 5; $j++ ) {
				$rating_block .= '<div class="rtng-star" data-rating="' . ( $j+1 ) . '">
					<span class="dashicons dashicons-star-empty"></span>					
				</div>';
			}
		}	
				
		$rating_block .= '</div>';
		return $rating_block;
	}
}

/* Function for showing rating in comment */
if ( ! function_exists( 'rtng_show_comment_rating' ) ) {   
	function rtng_show_comment_rating( $comment ) {
		global $post, $wpdb, $rtng_options;

		if ( is_page() ) {  /* pages */ 
			if ( ! in_array( 'page', $rtng_options['use_post_types'] ) )
				return;
		} elseif ( is_single() || is_attachment() ) { /* posts */ 
			$post_type = get_post_type( $post->ID );
			if ( ! in_array( $post_type, $rtng_options['use_post_types'] ) )
				return;
		}

		$comment_ID = get_comment_ID();

		$rating = $wpdb->get_var( $wpdb->prepare( 
			"SELECT `" . $wpdb->prefix . "bws_rating`.`rating`
			FROM `" . $wpdb->prefix . "bws_rating` 
				JOIN `" . $wpdb->prefix . "comments` ON `" . $wpdb->prefix . "bws_rating`.`post_id` = `" . $wpdb->prefix . "comments`.`comment_post_ID`
			WHERE `" . $wpdb->prefix . "comments`.`comment_post_ID` = %d 
				AND `" . $wpdb->prefix . "bws_rating`.`object_id` = %d 
				AND `" . $wpdb->prefix . "comments`.`comment_id` = %d 
				AND `" . $wpdb->prefix . "bws_rating`.`object_type` = 'comment'",
		$post->ID, $comment_ID, $comment_ID ) );
				
		if ( $rating )
			return rtng_display_stars( $rating ) . $comment;

        return $comment;	
    } 
}

if ( ! function_exists( 'rtng_rating_shortcode' ) ) {  
	function rtng_rating_shortcode() {		
		global $rtng_options;
		if ( 1 != $rtng_options['combined'] ) {
			return rtng_show_total_rating() . rtng_show_rating_form( 'shortcode' );
		} else {
			return rtng_show_total_rating();
		}
	}
}

/* Function for adding rating from posts to db */
if ( ! function_exists( 'rtng_add_rating_db' ) ) { 
	function rtng_add_rating_db() {
		global $wpdb, $rtng_options;
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			check_ajax_referer( 'rtng_ajax_nonce', 'rtng_nonce' );
			if ( isset( $_POST['rtng_post_id'] ) && isset( $_POST['rtng_object_id'] ) && isset( $_POST['rtng_rating_val'] ) ) {

				/* Get options from the database */
				if ( empty( $rtng_options ) )
					$rtng_options = get_option( 'rtng_options' );

				/* check if rate exist */
				$percent_rating = $wpdb->get_var( $wpdb->prepare( 
					"SELECT `rating` 
					FROM `" . $wpdb->prefix . "bws_rating` 
					WHERE `post_id` = %d 
						AND `object_id` = %d
						AND `object_type` = 'post'",
					$_POST['rtng_post_id'], $_POST['rtng_object_id'] ) );

				if ( empty( $percent_rating ) ) {
					$percent_rating = ( $_POST['rtng_rating_val'] / 5 ) * 100;
					$wpdb->insert( 
						$wpdb->prefix . "bws_rating", 
						array( 
							'post_id' 		=> $_POST['rtng_post_id'],
							'object_id' 	=> $_POST['rtng_object_id'],
							'rating' 		=> $percent_rating,
							'datetime'		=> current_time( 'mysql' ),
							'object_type' 	=> 'post' ), 
						array( '%d', '%d', '%d', '%s', '%s' )
					);
				}

				echo rtng_show_total_rating( $_POST['rtng_post_id'] ) . 
					'<div class="rtng-form"><span class="rtng-text rtng-vote-title">' . $rtng_options['vote_title'] . '</span>'
						. rtng_display_stars( $percent_rating ) .
						'<span class="rtng-text rtng-thankyou">' . $rtng_options['thankyou_message'] . '</span>'
					. '</div>';
			}
			die();
		} elseif ( isset( $_REQUEST['rtng_add_button'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'rtng_nonce_button' ) ) {
			if ( ! empty( $_POST['rtng_post_id'] ) && ! empty( $_POST['rtng_object_id'] ) && ! empty( $_POST['rtng_rating'] ) ) {
				$post_id = intval( $_POST['rtng_post_id'] );
				$object_id = intval( $_POST['rtng_object_id'] );
				$percent_rating = ( intval( $_POST['rtng_rating'] ) / 5 ) * 100;
				$wpdb->insert( 
					$wpdb->prefix . "bws_rating", 
					array( 
						'post_id' 		=> $post_id,
						'object_id' 	=> $object_id,
						'rating' 		=> $percent_rating,
						'datetime'		=> current_time( 'mysql' ),
						'object_type' 	=> 'post' ), 
					array( '%d', '%d', '%d', '%s', '%s' )
				);
			}
		}	
	}
}

/* Function for adding rating from comments to db */
if ( ! function_exists( 'rtng_add_rating_db_comment' ) ) { 
	function rtng_add_rating_db_comment( $comment_ID ) {
		global $wpdb;

		if ( ! empty( $_POST['rtng_rating'] ) ) {
			$rating = intval( $_POST['rtng_rating'] );

			$post_ID = $wpdb->get_var( $wpdb->prepare( 
				"SELECT `comment_post_ID` 
				FROM `" . $wpdb->prefix . "comments` 
				WHERE `comment_ID` = %d",
			$comment_ID ) );

			if ( $post_ID && $rating ) {
				$percent_rating = ( $rating / 5 ) * 100;
				$wpdb->insert( 
					$wpdb->prefix . "bws_rating", 
					array( 
						'post_id'  		=> $post_ID,
						'object_id' 	=> $comment_ID,
						'rating' 		=> $percent_rating,
						'datetime'		=> current_time( 'mysql' ),
						'object_type' 	=> 'comment' ),
					array( '%d', '%d', '%d', '%s', '%s' )
				);
			}
		}
	}
}

if ( ! function_exists( 'rtng_pagination_callback' ) ) {
	function rtng_pagination_callback( $content ) {
		$content .= "$( '.rtng-star-rating' ).removeClass( 'rtng-no-js' );$( '.rtng-add-button' ).hide();";
		return $content;
	}
}

/* add shortcode content  */
if ( ! function_exists( 'rtng_shortcode_button_content' ) ) {
	function rtng_shortcode_button_content( $content ) {
		global $wp_version; ?>
		<div id="rtng" style="display:none;">
			<fieldset>				
				<?php _e( 'Add Rating block to your page or post', 'rating-bws' ); ?>
			</fieldset>
			<input class="bws_default_shortcode" type="hidden" name="default" value="[bws-rating]" />
			<div class="clear"></div>
		</div>
	<?php }
}

/* Function to add plugin scripts*/
if ( ! function_exists( 'rtng_admin_head' ) ) {
	function rtng_admin_head() {
		if ( isset( $_GET['page'] ) && 'rating.php' == $_GET['page'] ) {
			wp_enqueue_style( 'wp-color-picker' );
   			wp_enqueue_script( 'rtng_script', plugins_url( 'js/admin_script.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ) );		
		
			if ( isset( $_GET['action'] ) && 'custom_code' == $_GET['action'] )
				bws_plugins_include_codemirror();
		}
	}
}

if ( ! function_exists( 'rtng_wp_head' ) ) {
	function rtng_wp_head() {
		global $rtng_options;
		wp_enqueue_style( 'rtng_stylesheet', plugins_url( 'css/style.css', __FILE__ ), array( 'dashicons' ) ); ?>
		<style type="text/css">
			.rtng-star .dashicons {
				color: <?php echo $rtng_options['rate_color']; ?>;
				font-size: <?php echo $rtng_options['rate_size']; ?>px;
			}
			.rtng-star .dashicons.rtng-hovered,
			.rtng-star-rating.rtng-no-js .rtng-star input:checked + .dashicons::before {
				color: <?php echo $rtng_options['rate_hover_color']; ?>;
			}
			.rtng-text {
				color: <?php echo $rtng_options['text_color']; ?>;
				font-size: <?php echo $rtng_options['text_size']; ?>px;
			}
		</style>
	<?php }
}

/* Add necessary js scripts */
if ( ! function_exists( 'rtng_add_scripts' ) ) {
	function rtng_add_scripts() {
		global $rtng_options;
		wp_enqueue_script( 'rtng_scripts', plugins_url( 'js/script.js' , __FILE__ ), array( 'jquery' ), false, true );
		/* Enqueued script with localized data.*/
		$args = array( 
			'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
			'nonce'				=> wp_create_nonce( 'rtng_ajax_nonce' )
		);
		wp_localize_script( 'rtng_scripts', 'rtng_vars', $args );
	}
}

/* Functions creates other links on plugins page. */
if ( ! function_exists( 'rtng_action_links' ) ) {
	function rtng_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename( __FILE__ );
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=rating.php">' . __( 'Settings', 'rating-bws' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

if ( ! function_exists ( 'rtng_links' ) ) {
	function rtng_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[] = 	'<a href="admin.php?page=rating.php">' . __( 'Settings', 'rating-bws' ) . '</a>';
			$links[] = 	'<a href="https://support.bestwebsoft.com/hc/en-us/sections/203319926" target="_blank">' . __( 'FAQ', 'rating-bws' ) . '</a>';
			$links[] = 	'<a href="https://support.bestwebsoft.com">' . __( 'Support', 'rating-bws' ) . '</a>';
		}
		return $links;
	}
}

/* add help tab  */
if ( ! function_exists( 'rtng_add_tabs' ) ) {
	function rtng_add_tabs() {
		$screen = get_current_screen();
		$args = array( 
			'id' 			 => 'rtng', 
			'section' 		 => '203319926'
		 );
		bws_help_tab( $screen, $args );
	}
}

if ( ! function_exists ( 'rtng_plugin_banner' ) ) {
	function rtng_plugin_banner() {
		global $hook_suffix, $rtng_plugin_info;
		if ( 'plugins.php' == $hook_suffix ) {
			if ( ! is_network_admin() ) {
				bws_plugin_banner_to_settings( $rtng_plugin_info, 'rtng_options', 'rating-bws', 'admin.php?page=rating.php' );
			}
		}
		if ( isset( $_REQUEST['page'] ) && 'rating.php' == $_REQUEST['page'] ) {
			bws_plugin_suggest_feature_banner( $rtng_plugin_info, 'rtng_options', 'rating-bws' );
		}
	}
}

/* Function for delete options */
if ( ! function_exists( 'rtng_delete_options' ) ) {
	function rtng_delete_options() {
		global $wpdb;

		if ( is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				delete_option( 'rtng_options' );
				$wpdb->query( "DROP TABLE `" . $wpdb->prefix . "bws_rating`;" );
			}
			switch_to_blog( $old_blog );
		} else {
			delete_option( 'rtng_options' );
			$wpdb->query( "DROP TABLE `" . $wpdb->prefix . "bws_rating`;" );
		}	

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

/* Calling a function add administrative menu. */
add_action( 'admin_menu', 'rtng_add_pages' );
add_action( 'plugins_loaded', 'rtng_plugins_loaded' );
add_action( 'init', 'rtng_init' );
add_action( 'admin_init', 'rtng_admin_init' );
/* Adding stylesheets */
add_action( 'admin_enqueue_scripts', 'rtng_admin_head' );
add_action( 'wp_enqueue_scripts', 'rtng_wp_head' );
add_action( 'wp_footer', 'rtng_add_scripts' );

add_shortcode( 'bws-rating', 'rtng_rating_shortcode' );
add_action( 'wp_ajax_rtng_add_rating_db', 'rtng_add_rating_db' );

add_filter( 'pgntn_callback', 'rtng_pagination_callback' );

/* custom filter for bws button in tinyMCE */
add_filter( 'bws_shortcode_button_content', 'rtng_shortcode_button_content' );
/*## Additional links on the plugin page */
add_filter( 'plugin_action_links', 'rtng_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'rtng_links', 10, 2 );
/* Adding banner */
add_action( 'admin_notices', 'rtng_plugin_banner' ); 
/* Plugin uninstall function */
register_uninstall_hook( __FILE__, 'rtng_delete_options' );
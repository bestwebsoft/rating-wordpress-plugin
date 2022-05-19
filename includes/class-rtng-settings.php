<?php
/**
 * Displays the content on the plugin settings page
 */

if ( ! class_exists( 'Rtng_Settings_Tabs' ) ) {
	class Rtng_Settings_Tabs extends Bws_Settings_Tabs {
		public $is_general_settings = true;

		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename
		 */
		public function __construct( $plugin_basename ) {
			global $rtng_options, $rtng_plugin_info;

			$this->is_general_settings = ( isset( $_GET['page'] ) && 'rating.php' === $_GET['page'] );

			if ( $this->is_general_settings ) {
				$tabs = array(
					'settings'    => array( 'label' => __( 'Settings', 'rating-bws' ) ),
					'appearance'  => array( 'label' => __( 'Appearance', 'rating-bws' ) ),
					'misc'        => array( 'label' => __( 'Misc', 'rating-bws' ) ),
					'custom_code' => array( 'label' => __( 'Custom Code', 'rating-bws' ) ),
					'license'     => array( 'label' => __( 'Licence Key', 'rating-bws' ) ),
				);
			}

			if ( $this->is_multisite && ! $this->is_network_options ) {
				$network_options = get_site_option( 'rtng_options' );
				if ( ! empty( $network_options ) ) {
					if ( 'all' === $network_options['network_apply'] && 0 === intval( $network_options['network_change'] ) ) {
						$this->change_permission_attr = ' readonly="readonly" disabled="disabled"';
					}
					if ( 'all' === $network_options['network_apply'] && 0 === intval( $network_options['network_view'] ) ) {
						$this->forbid_view = true;
					}
				}
			}
			add_action( get_parent_class( $this ) . '_display_metabox', array( $this, 'display_metabox' ) );
			parent::__construct(
				array(
					'plugin_basename' => $plugin_basename,
					'plugins_info'    => $rtng_plugin_info,
					'prefix'          => 'rtng',
					'default_options' => rtng_get_default_options(),
					'options'         => $rtng_options,
					'tabs'            => $tabs,
					'doc_link'        => 'https://docs.google.com/document/d/1xFQZHTvem37naS9h3l_Xx_LnRy7djUKBlvtYgHR6k7s/',
					'wp_slug'         => 'rating-bws',
					'link_key'        => 'cc5cf22c4332ef4ba368cf4b739c90df',
					'link_pn'         => '630',
				)
			);
		}

		/**
		 * Save plugin options to the database
		 *
		 * @access public
		 * @param  void
		 * @return array    The action results
		 */
		public function save_options() {
			$message        = $notice = $error = '';
			$all_post_types = get_post_types(
				array(
					'public'  => 1,
					'show_ui' => 1,
				),
				'objects'
			);
			$editable_roles = get_editable_roles();
			if ( ! isset( $_GET['action'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rtng_nonce_admin'] ) ), plugin_basename( __FILE__ ) ) ) {
				$this->options['use_post_types'] = isset( $_REQUEST['rtng_use_post_types'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_REQUEST['rtng_use_post_types'] ) ) : array();
				foreach ( (array) $this->options['use_post_types'] as $key => $post_type ) {
					if ( ! array_key_exists( $post_type, $all_post_types ) ) {
						unset( $this->options['use_post_types'][ $key ] );
					}
				}

				$this->options['average_position'] = isset( $_REQUEST['rtng_average_position'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_REQUEST['rtng_average_position'] ) ) : array();
				foreach ( (array) $this->options['average_position'] as $key => $position ) {
					if ( ! in_array( $position, array( 'before', 'after' ) ) ) {
						unset( $this->options['average_position'][ $key ] );
					}
				}

				$this->options['combined']      = isset( $_REQUEST['rtng_combined'] ) ? 1 : 0;
				$this->options['rate_position'] = isset( $_REQUEST['rtng_rate_position'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_REQUEST['rtng_rate_position'] ) ) : array();

				if ( in_array( 'in_comment', (array) $this->options['rate_position'] ) ) {
					$rtng_options['rate_position'] = array( 'in_comment' );
				} else {
					foreach ( (array) $this->options['rate_position'] as $key => $position ) {
						if ( ! in_array( $position, array( 'before', 'after' ) ) ) {
							unset( $this->options['rate_position'][ $key ] );
						}
					}
				}

				$this->options['enabled_roles'] = array();
				if ( isset( $_POST['rtng_roles'] ) && is_array( $_POST['rtng_roles'] ) ) {
					$_POST['rtng_roles'] = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_POST['rtng_roles'] ) );
					foreach ( array_filter( (array) $_POST['rtng_roles'] ) as $role ) {
						if ( array_key_exists( $role, $editable_roles ) || 'guest' === $role ) {
							$this->options['enabled_roles'][] = $role;
						}
					}
				}

				$this->options['add_schema']      = isset( $_REQUEST['rtng_add_schema'] ) ? 1 : 0;
				$this->options['schema_min_rate'] = isset( $_REQUEST['rtng_schema_min_rate'] ) ? floatval( sanitize_text_field( wp_unslash( $_REQUEST['rtng_schema_min_rate'] ) ) ) : '';
				if ( $this->options['schema_min_rate'] > $this->options['quantity_star'] ) {
					$this->options['schema_min_rate'] = $this->options['quantity_star'];
				} elseif ( $this->options['schema_min_rate'] < 1 ) {
					$this->options['schema_min_rate'] = 1;
				}

				$this->options['always_clickable'] = isset( $_REQUEST['rtng_always_clickable'] ) ? 1 : 0;
				$this->options['rating_required']  = isset( $_REQUEST['rtng_check_rating_required'] ) ? 1 : 0;
				$this->options['rate_color']       = isset( $_REQUEST['rtng_rate_color'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_rate_color'] ) ) : '';
				$this->options['rate_hover_color'] = isset( $_REQUEST['rtng_rate_hover_color'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_rate_hover_color'] ) ) : '';
				$this->options['rate_size']        = isset( $_REQUEST['rtng_rate_size'] ) ? intval( $_REQUEST['rtng_rate_size'] ) : 0;

				$this->options['text_color']            = isset( $_REQUEST['rtng_text_color'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_text_color'] ) ) : '';
				$this->options['text_size']             = isset( $_REQUEST['rtng_text_size'] ) ? intval( $_REQUEST['rtng_text_size'] ) : 0;
				$this->options['star_post']             = isset( $_REQUEST['rtng_star_post'] ) ? 1 : 0;
				$this->options['result_title']          = isset( $_REQUEST['rtng_result_title'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_result_title'] ) ) : '';
				$this->options['total_message']         = isset( $_REQUEST['rtng_total_message'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_total_message'] ) ) : '';
				$this->options['vote_title']            = isset( $_REQUEST['rtng_vote_title'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_vote_title'] ) ) : '';
				$this->options['enable_testimonials']   = isset( $_REQUEST['rtng_testimonials'] ) ? 1 : 0;
				$this->options['options_quantity']      = isset( $_REQUEST['rtng_options_quantity'] ) ? intval( $_REQUEST['rtng_options_quantity'] ) : 0;
				$this->options['testimonials_titles']   = isset( $_REQUEST['rtng_testimonials_titles'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_REQUEST['rtng_testimonials_titles'] ) ) : array( '' );
				$this->options['non_login_message']     = isset( $_REQUEST['rtng_non_login_message'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_non_login_message'] ) ) : '';
				$this->options['thankyou_message']      = isset( $_REQUEST['rtng_thankyou_message'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_thankyou_message'] ) ) : '';
				$this->options['error_message']         = isset( $_REQUEST['rtng_error_message'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_error_message'] ) ) : '';
				$this->options['already_rated_message'] = isset( $_REQUEST['rtng_already_rated_message'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rtng_already_rated_message'] ) ) : '';
				$this->options                          = array_map( 'stripslashes_deep', $this->options );

				update_option( 'rtng_options', $this->options );
				$message = __( 'Settings saved.', 'rating-bws' );
			}
			return compact( 'message', 'notice', 'error' );
		}
		/**
		 * s
		 */
		public function tab_settings() {
			$all_post_types = get_post_types(
				array(
					'public'  => 1,
					'show_ui' => 1,
				),
				'objects'
			);
			$editable_roles = get_editable_roles();
			?>
			<h3><?php esc_html_e( 'Rating Settings', 'rating-bws' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Add Rating to', 'rating-bws' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $all_post_types as $key => $value ) { ?>
								<label>
									<input type="checkbox" name="rtng_use_post_types[]" value="<?php echo esc_attr( $key ); ?>" 
										<?php
										if ( in_array( $key, $this->options['use_post_types'] ) ) {
											echo 'checked="checked"';}
										?>
									/>
									<?php echo esc_html( $value->label ); ?>
								</label>
								<br/>
							<?php } ?>
						</fieldset>
					</td>
				</tr>
				<?php
				if ( is_plugin_active( 'bws-testimonials/bws-testimonials.php' ) ) {
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Testimonials Review Form', 'rating-bws' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="rtng_testimonials" value="1" <?php checked( $this->options['enable_testimonials'] ); ?> />
							</label>
						</td>
					</tr>
					<tr class="rtng-show-testimonials">
						<th scope="row"><?php esc_html_e( 'Number of Titles', 'rating-bws' ); ?></th>
						<td>
							<input type="number" min="1" max="5" value="<?php echo esc_attr( $this->options['options_quantity'] ); ?>" name="rtng_options_quantity" />
						</td>
					</tr>
					<tr class="rtng-show-testimonials">
						<th scope="row"><?php esc_html_e( 'Review Title', 'rating-bws' ); ?></th>
						<td>
							<input type="text" maxlength="250" class="regular-text" value="<?php echo isset( $this->options['testimonials_titles'][0] ) ? esc_attr( $this->options['testimonials_titles'][0] ) : ''; ?>" name="rtng_testimonials_titles[]" />
						</td>
					</tr>
					<?php
					if ( $this->options['options_quantity'] > 1 ) {
						for ( $i = 1; $i < $this->options['options_quantity']; $i++ ) {
							?>
							<tr class="rtng-show-testimonials">
								<th scope="row"><?php printf( esc_html__( 'Review Title %1$s %2$d', 'rating-bws' ), '&numero;', intval( $i + 1 ) ); ?></th>
								<td>
									<input type="text" maxlength="250" class="regular-text" value="<?php echo isset( $this->options['testimonials_titles'][ $i ] ) ? esc_attr( $this->options['testimonials_titles'][ $i ] ) : ''; ?>" name="rtng_testimonials_titles[]" />
								</td>
							</tr>
							<?php
						}
					}
					?>
					<?php
				} else {
					$all_plugins = get_plugins();

					if ( array_key_exists( 'bws-testimonials/bws-testimonials.php', $all_plugins ) ) {
						$button_url        = network_admin_url( 'plugins.php' );
						$button_text       = __( 'Activate', 'rating-bws' );
						$button_text_after = $all_plugins['bws-testimonials/bws-testimonials.php']['Name'];
					} else {
						$button_url        = 'https://bestwebsoft.com/products/wordpress/plugins/testimonials/';
						$button_text       = __( 'Download', 'rating-bws' );
						$button_text_after = 'Testimonials by BestWebSoft.';
					}
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Testimonial Reviews', 'rating-bws' ); ?></th>
						<td>
							<label>
								<input type="checkbox" disabled="disabled" name="rtng_testimonials" value="1" <?php checked( $this->options['enable_testimonials'] ); ?> />
								<span class="bws_info">
									<a href="<?php echo esc_url( $button_url ); ?>" target="_blank"><?php echo esc_html( $button_text ); ?></a> <?php echo esc_html( $button_text_after ); ?>
								</span>
							</label>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<th><?php esc_html_e( 'Average Rating Position', 'rating-bws' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="rtng_average_position[]" value="before" 
								<?php
								if ( in_array( 'before', $this->options['average_position'] ) ) {
									echo wp_kses_post( 'checked="checked"' );
								}
								?>
								/>
								<?php esc_html_e( 'Before the content', 'rating-bws' ); ?>
							</label>
							<br/>
							<label>
								<input type="checkbox" name="rtng_average_position[]" value="after" 
								<?php
								if ( in_array( 'after', $this->options['average_position'] ) ) {
									echo wp_kses_post( 'checked="checked"' );
								}
								?>
								/>
								<?php esc_html_e( 'After the content', 'rating-bws' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Combine Average and My Rating Blocks', 'rating-bws' ); ?></th>
					<td>
						<input type="checkbox" name="rtng_combined" value="1" 
						<?php
						if ( 1 === intval( $this->options['combined'] ) ) {
							echo wp_kses_post( 'checked="checked"' );
						}
						?>
						/>
						<span class="bws_info">
							<?php esc_html_e( 'Enable to use a single rating block.', 'rating-bws' ); ?>
						</span>
					</td>
				</tr>
				<tr id="rtng_rate_position">
					<th><?php esc_html_e( 'My Rating Position', 'rating-bws' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="rtng_rate_position[]" value="before" 
								<?php
								if ( in_array( 'before', $this->options['rate_position'] ) ) {
									echo wp_kses_post( 'checked="checked"' );
								}
								?>
								/>
								<?php esc_html_e( 'Before the content', 'rating-bws' ); ?>
							</label>
							<br/>
							<label>
								<input type="checkbox" name="rtng_rate_position[]" value="after" 
								<?php
								if ( in_array( 'after', $this->options['rate_position'] ) ) {
									echo wp_kses_post( 'checked="checked"' );
								}
								?>
								/>
								<?php esc_html_e( 'After the content', 'rating-bws' ); ?>
							</label>
							<br/>
							<label>
								<input type="checkbox" name="rtng_rate_position[]" value="in_comment" 
								<?php
								if ( in_array( 'in_comment', $this->options['rate_position'] ) ) {
									echo wp_kses_post( 'checked="checked"' );
								}
								?>
								/>
								<?php esc_html_e( 'In comments', 'rating-bws' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr id="rtng_required_rate">
					<th scope="row"><?php esc_html_e( 'Required Rating', 'rating-bws' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="rtng_check_rating_required" value="1" <?php checked( $this->options['rating_required'] ); ?> />
							<span class="bws_info">
								<?php esc_html_e( 'Enable to make the rating required for comment submission.', 'rating-bws' ); ?>
							</span>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Change Rating', 'rating-bws' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="rtng_always_clickable" value="1" <?php checked( $this->options['always_clickable'] ); ?> />&nbsp;
							<span class="bws_info">
									<?php esc_html_e( 'Enable to allow users change their rating.', 'rating-bws' ); ?>
							</span>
						</label>
					</td>
				</tr>
				</table>
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'rating-bws' ); ?>"></button>
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr>
									<th><?php esc_html_e( 'Show Rating in Posts', 'rating-bws' ); ?></th>
									<td>
										<input type="checkbox" name="rtng_star_post" value="1" />
										<span class="bws_info">
											<?php
											printf(
												'%s %s',
												sprintf(
													esc_html__( 'Enable to display average rating in the list of %s and', 'rating-bws' ),
													sprintf(
														'<a href="https://' . esc_html( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . '/wp-admin/edit.php" target="_blank">%s</a>',
														esc_html__( 'posts', 'rating-bws' )
													)
												),
												sprintf(
													'<a href="https://' . esc_html( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . '/wp-admin/edit.php?post_type=page" target="_blank">%s</a>',
													esc_html__( 'pages.', 'rating-bws' )
												)
											);
											?>
										</span>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
				<?php } ?>
				<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Schema Markup', 'rating-bws' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="rtng_add_schema" value="1" <?php checked( ! empty( $this->options['add_schema'] ) ); ?> />&nbsp;
							<span class="bws_info">
								<?php
								printf(
									'%s %s',
									sprintf(
										esc_html__( 'Enable to add JSON-LD rating %s markup.', 'rating-bws' ),
										sprintf(
											'<a href="http://schema.org/AggregateRating" target="_blank">%s</a>',
											esc_html__( 'schema', 'rating-bws' )
										)
									),
									sprintf(
										'<a href="https://developers.google.com/search/docs/guides/intro-structured-data" target="_blank">%s</a>',
										esc_html__( 'Learn More', 'rating-bws' )
									)
								);
								?>
						</span>
						</label>
					</td>
				</tr>
				<tr id="rtng_minimun_rating">
					<th scope="row"><?php esc_html_e( 'Minimum Rating to Add Schema', 'rating-bws' ); ?></th>
					<td>
						<label>
							<input type="number" name="rtng_schema_min_rate" class="small-text" value="<?php echo esc_attr( $this->options['schema_min_rate'] ); ?>" min="1" max="5" step="0.1" />&nbsp;<span class="bws_info"><?php esc_html_e( 'Schema markup will not be included if post rating goes under this value.', 'rating-bws' ); ?></span>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Rating for', 'rating-bws' ); ?></th>
					<td>
						<fieldset>
							<label class="hide-if-no-js">
								<input type="checkbox" id="rtng-all-roles" />&nbsp;<span class="rtng-role-name"><strong><?php esc_html_e( 'All', 'rating-bws' ); ?></strong></span>
							</label><br />
								<?php foreach ( $editable_roles as $role => $role_info ) { ?>
									<label>
										<input type="checkbox" class="rtng-role" name="rtng_roles[]" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $this->options['enabled_roles'] ) ); ?>/>&nbsp;<span class="rtng-role-name"><?php echo esc_html( translate_user_role( $role_info['name'] ) ); ?></span>
									</label><br />
								<?php } ?>
							<label>
								<input type="checkbox" class="rtng-role" name="rtng_roles[]" value="guest" <?php checked( in_array( 'guest', $this->options['enabled_roles'] ) ); ?>/>&nbsp;<span class="rtng-role-name"><?php esc_html_e( 'Guest', 'rating-bws' ); ?></span>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
			<?php
			echo wp_nonce_field( plugin_basename( __FILE__ ), 'rtng_nonce_admin', true, false );
		}

		public function tab_appearance() {
			?>
			<h3 class="bws_tab_label"><?php esc_html_e( 'Appearance', 'rating-bws' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<form method="post" action="" enctype="multipart/form-data" class="bws_form">
				<?php if ( ! $this->hide_pro_tabs ) { ?>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php esc_html_e( 'Close', 'rating-bws' ); ?>"></button>
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr>
									<th><?php esc_html_e( 'Number of Stars', 'rating-bws' ); ?></th>
									<td>
										<input type="number" min="1" max="5" name="rtng_quantity_star" /><?php echo esc_html( ' ' ); ?>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
				<?php } ?>
				<table class="form-table rtng-form-table">
					<tr>
						<th><?php esc_html_e( 'Star Color', 'rating-bws' ); ?></th>
						<td>
							<input type="text" class="rtng_color" value="<?php echo esc_attr( $this->options['rate_color'] ); ?>" name="rtng_rate_color" data-default-color="#ffb900" /><div class="clear"></div>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Star Color on Hover', 'rating-bws' ); ?></th>
						<td>
							<input type="text" class="rtng_color" value="<?php echo esc_attr( $this->options['rate_hover_color'] ); ?>" name="rtng_rate_hover_color" data-default-color="#ffb900" /><div class="clear"></div>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Star Size', 'rating-bws' ); ?></th>
						<td>
							<input type="number" min="1" max="300" value="<?php echo esc_attr( $this->options['rate_size'] ); ?>" name="rtng_rate_size" /> <?php esc_html_e( 'px', 'rating-bws' ); ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Text Color', 'rating-bws' ); ?></th>
						<td>
							<input type="text" class="rtng_color" value="<?php echo esc_attr( $this->options['text_color'] ); ?>" name="rtng_text_color" data-default-color="#ffb900" /><div class="clear"></div>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Text Font Size', 'rating-bws' ); ?></th>
						<td>
							<input type="number" min="1" max="100" value="<?php echo esc_attr( $this->options['text_size'] ); ?>" name="rtng_text_size" /> <?php esc_html_e( 'px', 'rating-bws' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Average Rating Title', 'rating-bws' ); ?></th>
						<td>
							<input type="text" maxlength="250" class="regular-text" value="<?php echo esc_attr( $this->options['result_title'] ); ?>" name="rtng_result_title" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Average Rating Text', 'rating-bws' ); ?></th>
						<td>
							<input type="text" maxlength="250" class="regular-text" value="<?php echo esc_attr( $this->options['total_message'] ); ?>" name="rtng_total_message" />
							<br>
							<span class="bws_info">
								<?php
								printf(
									esc_html__( 'Use %s to insert current rate.', 'rating-bws' ),
									'{total_rate}'
								);
								?>
								<?php
								printf(
									esc_html__( 'Use %s to insert rates count.', 'rating-bws' ),
									'{total_count}'
								);
								?>
							</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'My Rating', 'rating-bws' ); ?></th>
						<td>
							<input type="text" maxlength="250" class="regular-text" value="<?php echo esc_attr( $this->options['vote_title'] ); ?>" name="rtng_vote_title" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Authorization Required Text', 'rating-bws' ); ?></th>
						<td>
							<input type="text" maxlength="250" class="regular-text" value="<?php echo esc_attr( $this->options['non_login_message'] ); ?>" name="rtng_non_login_message" />
							<br>
							<span class="bws_info">
								<?php
								printf(
									esc_html__( 'Use %s to insert login link.', 'rating-bws' ),
									'{login_link="text"}'
								);
								?>
							</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Thank You Message', 'rating-bws' ); ?></th>
						<td>
							<input type="text" maxlength="250" class="regular-text" value="<?php echo esc_attr( $this->options['thankyou_message'] ); ?>" name="rtng_thankyou_message" />
							<br>
							<span class="bws_info">
								<?php esc_html_e( 'This message will be displayed after the rating is submitted.', 'rating-bws' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'No Permissions Message', 'rating-bws' ); ?></th>
						<td>
							<input type="text" maxlength="250" class="regular-text" value="<?php echo esc_attr( $this->options['error_message'] ); ?>" name="rtng_error_message" />
							<br>
							<span class="bws_info">
								<?php esc_html_e( 'This message will be displayed if user role is disabled.', 'rating-bws' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Rating Already Submitted Message', 'rating-bws' ); ?></th>
						<td>
							<input type="text" maxlength="250" class="regular-text" value="<?php echo esc_attr( $this->options['already_rated_message'] ); ?>" name="rtng_already_rated_message" />
							<br>
							<span class="bws_info">
								<?php esc_html_e( 'This message will be displayed if the user has already rated the post.', 'rating-bws' ); ?>
							</span>
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
		public function display_metabox() {
			?>
			<div class="postbox">
				<h3 class="hndle">
					<?php esc_html_e( 'Rating', 'rating-bws' ); ?>
				</h3>
				<div class="inside">
					<?php esc_html_e( 'If you would like to add rating to your page or post, please use next shortcode:', 'rating-bws' ); ?>
					<?php bws_shortcode_output( '[bws-rating]' ); ?>
				</div>
			</div>
			<?php
		}
	}
}

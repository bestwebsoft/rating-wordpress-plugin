<?php
/**
 * Displays the content on the plugin settings page
 */

require_once( dirname( dirname( __FILE__ ) ) . '/bws_menu/class-bws-settings.php' );

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

        $this->is_general_settings = ( isset( $_GET['page'] ) && 'rating.php' == $_GET['page'] );

        if ( $this->is_general_settings ) {
            $tabs = array(
                'settings' 		=> array( 'label' => __( 'Settings', 'rating-bws' ) ),
                'appearance'    => array( 'label' => __( 'Appearance', 'rating-bws' ) ),
                'misc' 			=> array( 'label' => __( 'Misc', 'rating-bws' ) ),
                'custom_code' 	=> array( 'label' => __( 'Custom Code', 'rating-bws' ) ),
                'license'       => array( 'label' => __( 'Licence Key', 'rating-bws' ) )
            );
        }

        if ( $this->is_multisite && ! $this->is_network_options ) {
            if ( $network_options = get_site_option( 'rtng_options' ) ) {
                if ( 'all' == $network_options['network_apply'] && 0 == $network_options['network_change'] )
                    $this->change_permission_attr = ' readonly="readonly" disabled="disabled"';
                if ( 'all' == $network_options['network_apply'] && 0 == $network_options['network_view'] )
                    $this->forbid_view = true;
            }
        }
        add_action( get_parent_class( $this ) . '_display_metabox', array( $this, 'display_metabox' ) );
        parent::__construct( array(
            'plugin_basename' 	 => $plugin_basename,
            'plugins_info'		 => $rtng_plugin_info,
            'prefix' 			 => 'rtng',
            'default_options' 	 => rtng_get_default_options(),
            'options' 			 => $rtng_options,
            'tabs' 				 => $tabs,
            'doc_link'			 => 'https://docs.google.com/document/d/1gy5uDVoebmYRUvlKRwBmc97jdJFz7GvUCtXy3L7r_Yg/',
            'wp_slug'			 => 'rating-bws',
            'pro_page'			 => 'admin.php?page=rating-bws-pro.php',
            'bws_license_plugin' => 'rating-bws-pro/rating-bws-pro.php',
            'link_key'			 => '427287ceae749cbd015b4bba6041c4b8',
            'link_pn'			 => '78'
        ) );
    }

        /**
         * Save plugin options to the database
         * @access public
         * @param  void
         * @return array    The action results
         */
            public function save_options(){
                $all_post_types = get_post_types( array( 'public' => 1, 'show_ui' => 1 ), 'objects' );
                $editable_roles = get_editable_roles();
                if ( !isset( $_GET['action'] ) ) {
                    $this->options['use_post_types'] = isset( $_REQUEST['rtng_use_post_types'] ) ? $_REQUEST['rtng_use_post_types'] : array();
                    foreach ( ( array )$this->options['use_post_types'] as $key => $post_type ) {
                        if ( !array_key_exists( $post_type, $all_post_types ) )
                            unset( $this->options['use_post_types'][$key] );
                    }

                    $this->options['average_position'] = isset( $_REQUEST['rtng_average_position'] ) ? $_REQUEST['rtng_average_position'] : array();
                    foreach ( ( array )$this->options['average_position'] as $key => $position ) {
                        if (!in_array( $position, array( 'before', 'after' ) ) )
                            unset ( $this->options['average_position'][$key] );
                    }

                    $this->options['combined'] = isset( $_REQUEST['rtng_combined'] ) ? 1 : 0;
                    $this->options['rate_position'] = isset( $_REQUEST['rtng_rate_position'] ) ? $_REQUEST['rtng_rate_position'] : array();

                    if ( in_array('in_comment', ( array )$this->options['rate_position'] ) ) {
                        $rtng_options['rate_position'] = array( 'in_comment' );
                    } else {
                        foreach ( ( array )$this->options['rate_position'] as $key => $position ) {
                            if ( !in_array( $position, array( 'before', 'after' ) ) )
                                unset( $this->options['rate_position'][$key] );
                        }
                    }

                    $this->options['enabled_roles'] = array();
                    if ( isset( $_POST['rtng_roles'] ) && is_array( $_POST['rtng_roles'] ) ) {
                        foreach ( array_filter( ( array )$_POST['rtng_roles'] ) as $role ) {
                            if ( array_key_exists( $role, $editable_roles ) || 'guest' == $role ) {
                                $this->options['enabled_roles'][] = $role;
                            }
                        }
                    }

                    $this->options['add_schema'] = isset( $_REQUEST['rtng_add_schema'] ) ? 1 : 0;
                    $this->options['schema_min_rate'] = floatval( $_REQUEST['rtng_schema_min_rate'] );
                    if ( $this->options['schema_min_rate'] > $this->options['quantity_star'] ) {
                        $this->options['schema_min_rate'] = $this->options['quantity_star'];
                    } elseif ( $this->options['schema_min_rate'] < 1 ) {
                        $this->options['schema_min_rate'] = 1;
                    }

                    $this->options['always_clickable'] = isset( $_REQUEST['rtng_always_clickable'] ) ? 1 : 0;
                    $this->options['rating_required'] = isset( $_REQUEST['rtng_check_rating_required'] ) ? 1 : 0;
                    $this->options['rate_color'] = stripslashes( esc_html( $_REQUEST['rtng_rate_color'] ) );
                    $this->options['rate_hover_color'] = stripslashes( esc_html( $_REQUEST['rtng_rate_hover_color'] ) );
                    $this->options['rate_size'] = intval( $_REQUEST['rtng_rate_size'] );

                    $this->options['text_color'] = stripslashes( esc_html( $_REQUEST['rtng_text_color'] ) );
                    $this->options['text_size'] = intval( $_REQUEST['rtng_text_size'] );
                    $this->options['star_post'] = isset( $_REQUEST['rtng_star_post'] ) ? 1 : 0;
                    $this->options['result_title'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_result_title'] ) );
                    $this->options['total_message'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_total_message'] ) );
                    $this->options['vote_title'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_vote_title'] ) );
                    $this->options['non_login_message'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_non_login_message'] ) );
                    $this->options['thankyou_message'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_thankyou_message'] ) );
                    $this->options['error_message'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_error_message'] ) );
                    $this->options['already_rated_message'] = stripslashes( htmlspecialchars( $_REQUEST['rtng_already_rated_message'] ) );
                    $this->options = array_map('stripslashes_deep', $this->options );

                    update_option( 'rtng_options', $this->options );
                    $message = __( 'Settings saved.', 'rating-bws' );
                }
                return compact('message', 'notice', 'error' );
            }
        /**
         *s
         */
        public function tab_settings() {
            $all_post_types = get_post_types( array( 'public' => 1, 'show_ui' => 1 ), 'objects' );
            $editable_roles = get_editable_roles();
            ?>
            <h3><?php _e( 'Rating Settings', 'rating-bws' ); ?></h3>
            <?php $this->help_phrase(); ?>
            <hr>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Display Rating', 'rating-bws' ); ?></th>
                    <td>
                        <fieldset>
                            <?php foreach ( $all_post_types as $key => $value ) { ?>
                                <label>
                                    <input type="checkbox" name="rtng_use_post_types[]" value="<?php echo $key; ?>" <?php if ( in_array( $key, $this->options['use_post_types'] ) ) echo 'checked="checked"'; ?> />
                                    <?php echo $value->label; ?>
                                </label>
                                <br/>
                            <?php } ?>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Average Rating Position', 'rating-bws' ); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="rtng_average_position[]" value="before" <?php if ( in_array( 'before', $this->options['average_position'] ) ) echo 'checked="checked"'; ?> />
                                <?php _e( 'Before the content', 'rating-bws' ); ?>
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="rtng_average_position[]" value="after" <?php if ( in_array( 'after', $this->options['average_position'] ) ) echo 'checked="checked"'; ?> />
                                <?php _e( 'After the content', 'rating-bws' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Combine Average Rating with Rate Option', 'rating-bws' ); ?></th>
                    <td>
                        <input type="checkbox" name="rtng_combined" value="1" <?php if ( 1 == $this->options['combined'] ) echo 'checked="checked"'; ?> />
                    </td>
                </tr>
                <tr id="rtng_rate_position">
                    <th><?php _e( 'Rate Option Position', 'rating-bws' ); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="rtng_rate_position[]" value="before" <?php if ( in_array( 'before', $this->options['rate_position'] ) ) echo 'checked="checked"'; ?> />
                                <?php _e( 'Before the content', 'rating-bws' ); ?>
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="rtng_rate_position[]" value="after" <?php if ( in_array( 'after', $this->options['rate_position'] ) ) echo 'checked="checked"'; ?> />
                                <?php _e( 'After the content', 'rating-bws' ); ?>
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="rtng_rate_position[]" value="in_comment" <?php if ( in_array( 'in_comment', $this->options['rate_position'] ) ) echo 'checked="checked"'; ?> />
                                <?php _e( 'In comments', 'rating-bws' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Required Rating', 'rating-bws' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rtng_check_rating_required" value="1" <?php checked( $this->options['rating_required'] ); ?> />
                            <span class="bws_info">
                                <?php _e( 'Enable to make rating submitting required for comments form.', 'rating-bws' ); ?>
                            </span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Always Clickable Stars', 'rating-bws' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rtng_always_clickable" value="1" <?php checked( $this->options['always_clickable'] ); ?> />&nbsp;
                            <span class="bws_info">
											<?php _e( 'Enable to make stars always clickable, even if the user has already rated the post or user role is disabled.', 'rating-bws'); ?>
                            </span>
                        </label>
                    </td>
                </tr>
                </table>
                <?php if ( ! $this->hide_pro_tabs ) { ?>
                    <div class="bws_pro_version_bloc">
                        <div class="bws_pro_version_table_bloc">
                            <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'rating-bws' ); ?>"></button>
                            <div class="bws_table_bg"></div>
                            <table class="form-table bws_pro_version">
                                <tr>
                                    <th><?php _e( 'Show Rating in Posts', 'rating-bws' ); ?></th>
                                    <td>
                                        <input type="checkbox" name="rtng_star_post" value="1" />
                                        <span class="bws_info">
                                            <?php printf(
                                                '%s %s',
                                                sprintf(
                                                    __( 'Enable to display average rating in the list of %s and', 'rating-bws' ),
                                                    sprintf(
                                                        '<a href="https://' . $_SERVER['HTTP_HOST'] . '/wp-admin/edit.php" target="_blank">%s</a>',
                                                        __( 'posts', 'rating-bws' )
                                                    )
                                                ),
                                                sprintf(
                                                    '<a href="https://' . $_SERVER['HTTP_HOST'] . '/wp-admin/edit.php?post_type=page" target="_blank">%s</a>',
                                                    __( 'pages.', 'rating-bws' )
                                                )
                                            ); ?>
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
                    <th scope="row"><?php _e( 'Schema Markup', 'rating-bws' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="rtng_add_schema" value="1" <?php checked( ! empty( $this->options['add_schema'] ) ); ?> />&nbsp;
                            <span class="bws_info">
                                <?php printf(
                                    '%s %s',
                                    sprintf(
                                        __( 'Enable to add JSON-LD rating %s markup.', 'rating-bws' ),
                                        sprintf(
                                            '<a href="http://schema.org/AggregateRating" target="_blank">%s</a>',
                                            __( 'schema', 'rating-bws' )
                                        )
                                    ),
                                    sprintf(
                                        '<a href="https://developers.google.com/search/docs/guides/intro-structured-data" target="_blank">%s</a>',
                                        __( 'Learn More', 'rating-bws' )
                                    )
                                ); ?>
                        </span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Minimum Rating to Add Schema', 'rating-bws' ); ?></th>
                    <td>
                        <label>
                            <input type="number" name="rtng_schema_min_rate" class="small-text" value="<?php echo $this->options['schema_min_rate']; ?>" min="1" max="5" step="0.1" />&nbsp;<span class="bws_info"><?php _e( 'Schema markup will not be included if post rating goes under this value.', 'rating-bws' ); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Enable Rating for', 'rating-bws' ); ?></th>
                    <td>
                        <fieldset>
                            <label class="hide-if-no-js">
                                <input type="checkbox" id="rtng-all-roles" />&nbsp;<span class="rtng-role-name"><strong><?php _e( 'All', 'rating-bws' ); ?></strong></span>
                            </label><br />
                                <?php foreach ( $editable_roles as $role => $role_info ) { ?>
                                    <label>
                                        <input type="checkbox" class="rtng-role" name="rtng_roles[]" value="<?php echo $role; ?>" <?php checked( in_array( $role, $this->options['enabled_roles'] ) ); ?>/>&nbsp;<span class="rtng-role-name"><?php echo translate_user_role( $role_info['name'] ); ?></span>
                                    </label><br />
                                <?php } ?>
                            <label>
                                <input type="checkbox" class="rtng-role" name="rtng_roles[]" value="guest" <?php checked( in_array( 'guest', $this->options['enabled_roles'] ) ); ?>/>&nbsp;<span class="rtng-role-name"><?php _e( 'Guest', 'rating-bws' ); ?></span>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        <?php }

        public function tab_appearance(){?>
            <h3 class="bws_tab_label"><?php _e( 'Appearance', 'rating-bws' ); ?></h3>
            <?php $this->help_phrase(); ?>
            <hr>
            <form method="post" action="" enctype="multipart/form-data" class="bws_form">
                <?php if ( ! $this->hide_pro_tabs ) { ?>
                    <div class="bws_pro_version_bloc">
                        <div class="bws_pro_version_table_bloc">
                            <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'rating-bws' ); ?>"></button>
                            <div class="bws_table_bg"></div>
                            <table class="form-table bws_pro_version">
                                <tr>
                                    <th><?php _e( 'Number of Stars', 'rating-bws' ); ?></th>
                                    <td>
                                        <input type="number" min="1" max="100" name="rtng_quantity_star" /> <?php _e( ' ', 'rating-bws' );?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <?php $this->bws_pro_block_links(); ?>
                    </div>
                <?php } ?>
                <table class="form-table rtng-form-table">
                    <tr>
                        <th><?php _e( 'Star Color', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" class="rtng_color" value="<?php echo $this->options['rate_color']; ?>" name="rtng_rate_color" data-default-color="#ffb900" /><div class="clear"></div>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Star Color on Hover', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" class="rtng_color" value="<?php echo $this->options['rate_hover_color']; ?>" name="rtng_rate_hover_color" data-default-color="#ffb900" /><div class="clear"></div>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Star Size', 'rating-bws' ); ?></th>
                        <td>
                            <input type="number" min="1" max="300" value="<?php echo $this->options['rate_size']; ?>" name="rtng_rate_size" /> <?php _e( 'px', 'rating-bws' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Text Color', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" class="rtng_color" value="<?php echo $this->options['text_color']; ?>" name="rtng_text_color" data-default-color="#ffb900" /><div class="clear"></div>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Text Font-Size', 'rating-bws' ); ?></th>
                        <td>
                            <input type="number" min="1" max="100" value="<?php echo $this->options['text_size']; ?>" name="rtng_text_size" /> <?php _e( 'px', 'rating-bws' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Average Rating Title', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" maxlength="250" class="regular-text" value="<?php echo $this->options['result_title']; ?>" name="rtng_result_title" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Total Rating Message', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" maxlength="250" class="regular-text" value="<?php echo $this->options['total_message']; ?>" name="rtng_total_message" />
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
                        <th scope="row"><?php _e( 'Rate Option Title', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" maxlength="250" class="regular-text" value="<?php echo $this->options['vote_title']; ?>" name="rtng_vote_title" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Message for Guests', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" maxlength="250" class="regular-text" value="<?php echo $this->options['non_login_message']; ?>" name="rtng_non_login_message" />
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
                        <th scope="row"><?php _e( 'Thank You Message', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" maxlength="250" class="regular-text" value="<?php echo $this->options['thankyou_message']; ?>" name="rtng_thankyou_message" />
                            <br>
                            <span class="bws_info">
                                <?php _e( 'This message will be displayed after the rating is submitted.', 'rating-bws' ); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Disabled User Role Message', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" maxlength="250" class="regular-text" value="<?php echo $this->options['error_message']; ?>" name="rtng_error_message" />
                            <br>
                            <span class="bws_info">
										<?php _e( 'This message will be displayed if user role is disabled.', 'rating-bws' ); ?>
									</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Already Rated Message', 'rating-bws' ); ?></th>
                        <td>
                            <input type="text" maxlength="250" class="regular-text" value="<?php echo $this->options['already_rated_message']; ?>" name="rtng_already_rated_message" />
                            <br>
                            <span class="bws_info">
                                <?php _e( 'This message will be displayed if the user has already rated the post.', 'rating-bws' ); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </form>
        <?php }
        public function display_metabox() { ?>
            <div class="postbox">
                <h3 class="hndle">
                    <?php _e( 'Rating', 'rating-bws' ); ?>
                </h3>
                <div class="inside">
                    <?php _e( "If you would like to add rating to your page or post, please use next shortcode:", 'rating-bws' ); ?>
                    <?php bws_shortcode_output( "[bws-rating]" ); ?>
                </div>
            </div>
        <?php }
    }
}
<?php
/**
 * Plugin Name: Admin Customizer
 * Plugin URI:  https://www.nilambar.net/2013/11/admin-customizer-wordpress-plugin.html
 * Description: This plugin allows you to customize the admin interface of your WordPress site.
 * Version:     2.1.0
 * Author:      Nilambar Sharma
 * Author URI:  https://www.nilambar.net/
 * Text Domain: admin-customizer
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define.
define( 'ADMIN_CUSTOMIZER_PLUGIN_FILE', __FILE__ );
define( 'ADMIN_CUSTOMIZER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'ADMIN_CUSTOMIZER_PLUGIN_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );
define( 'ADMIN_CUSTOMIZER_PLUGIN_URI', rtrim( plugin_dir_path( __FILE__ ), '/' ) );

// Load files.
require_once 'npf-framework/init.php';
require_once 'inc/plugin-options.php';

/**
 * Main Class.
 */
class AdminCustomizer {

	/**
	 * Plugin options.
	 *
	 * @var string
	 * @since 2.0.0
	 */
	private $options = array();

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	function __construct() {
		$this->options = adns_get_options();

		$this->init_hooks();
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );

		// Add settings link in plugin listing.
		$plugin = plugin_basename( __FILE__ );
		add_filter( 'plugin_action_links_' . $plugin, array( $this, 'add_settings_link' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_head', array( $this, 'add_admin_logo' ) );
		add_action( 'admin_head', array( $this, 'rearrange_logout_menu' ) );
		add_action( 'admin_head', array( $this, 'custom_css' ) );
		add_action( 'login_head', array( $this, 'custom_login_css' ) );
		add_filter( 'login_headerurl', array( $this, 'change_login_logo_url_link' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'login_theme_loader' ) );
		// Hide admin default logo.
		add_action( 'wp_before_admin_bar_render', array( $this, 'hide_admin_logo' ) );

		// Admin bar My account customization.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_my_account_customization' ) );
		// Update footer version.
		add_filter( 'update_footer', array( $this, 'change_footer_version' ), 9999 );
		// Footer message.
		add_filter( 'admin_footer_text', array( $this, 'change_footer_text' ) );
		// Number of columns in dashboard.
		add_filter( 'screen_layout_columns', array( $this, 'change_number_of_screen_columns_available' ) );
		add_filter( 'get_user_option_screen_layout_dashboard', array( $this, 'change_user_number_of_screen_columns_available' ) );
		// Number of columns in dashboard.
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widgets_customization' ), 99 );
		// Mail from name and email customization.
		add_filter( 'wp_mail_from', array( $this, 'new_mail_from_email' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'new_mail_from_name' ) );

		// Customize update nagging bar.
		add_action( 'admin_init', array( $this, 'hide_update_nagging_bar' ) );

		// Revisions.
		if ( intval( $this->options['adns_max_revision_count'] ) >= 0 ) {
			if ( ! defined( 'WP_POST_REVISIONS' ) ) {
				define( 'WP_POST_REVISIONS', intval( $this->options['adns_max_revision_count'] ) );
			}
		}

		// Admin sidebar content.
		add_action( 'npf_sidebar_admin-customizer', array( $this, 'admin_sidebar' ) );

	}
	/**
	 * Plugin init.
	 *
	 * @since 2.0.0
	 */
	function init() {
		// Load plugin text domain.
		load_plugin_textdomain( 'admin-customizer', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Links in plugin listing.
	 *
	 * @since 2.0.0
	 *
	 * @param array $links Array of links.
	 * @return array Modified array of links.
	 */
	public function add_settings_link( $links ) {
		$url = add_query_arg( array(
			'page' => 'admin-customizer',
			),
			admin_url( 'options-general.php' )
		);
		$settings_link = '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'admin-customizer' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Admin admin logo.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_logo() {

		if ( empty( $this->options['adns_admin_logo_url'] ) ) {
			return;
		}
		$url = $this->options['adns_admin_logo_url'];
		$alt_text = get_bloginfo( 'name', 'display' ) . ' ' . __( 'Admin', 'admin-customizer' );
		?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var image = $('<img/>', {
                    src: '<?php echo esc_url( $url ); ?>',
                    alt: '<?php echo esc_attr( $alt_text ); ?>',
                    style: 'padding-top:4px;height:25px'
                });
                var anchorlink = $('<a/>', {
                    href: '<?php echo esc_url( admin_url() ); ?>',
                    html: image,
                    title: '<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>'
                });
                jQuery('<li/>', {
                    html: anchorlink,
                    class: 'menupop'
                }).prependTo( '#wp-admin-bar-root-default' );
            });
        </script>
        <?php
	}

	/**
	 * Rearrange logout menu.
	 *
	 * @since 1.0.0
	 */
	public function rearrange_logout_menu() {

		if ( 1 !== absint( $this->options['adns_rearrange_logout_menu'] ) ) {
			return;
		}
		$sure_text = __( 'Are you sure?', 'admin-customizer' );
		?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var $logout = jQuery('#wp-admin-bar-user-actions li:eq(2)');
                jQuery('#wp-admin-bar-user-actions').remove();
                jQuery('#wp-admin-bar-my-account').removeClass('menupop');
                jQuery('#wp-admin-bar-my-account > a >img').remove();
                jQuery('#wp-admin-bar-my-account div.ab-sub-wrapper').remove();
                $logout.prependTo('#wp-admin-bar-top-secondary');
                <?php if ( 1 === absint( $this->options['adns_enable_logout_confirmation'] ) ) : ?>
                    $('#wp-admin-bar-logout a').click(function() {
                        var confirmation = confirm('<?php echo esc_html( $sure_text ); ?>');
                        if (confirmation) {
                            return true;
                        }
                        return false;
                    });
                <?php endif; ?>
            });
        </script>
        <?php
	}

	/**
	 * Hide default admin logo.
	 *
	 * @since 1.0.0
	 */
	public function hide_admin_logo() {
		global $wp_admin_bar;
		// Admin bar logo.
		if ( 1 === absint( $this->options['adns_hide_admin_logo'] ) ) {
			$wp_admin_bar->remove_menu( 'wp-logo' );
			$wp_admin_bar->remove_menu( 'view-site' );
		}
		// Comment popup.
		if ( 1 === absint( $this->options['adns_hide_comments_menu_header'] ) ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}
		// Update notification.
		if ( 1 === absint( $this->options['adns_hide_updates_menu_header'] ) ) {
			$wp_admin_bar->remove_menu( 'updates' );
		}
	}

	/**
	 * Add the "My Account" item.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP Admin Bar object.
	 */
	function admin_bar_my_account_customization( $wp_admin_bar ) {

		$user_id      = get_current_user_id();
		$current_user = wp_get_current_user();

		if ( ! $user_id ) {
			return;
		}

		$howdy = $current_user->display_name;
		if ( ! empty( $this->options['adns_howdy_replace'] ) ) {
			$howdy = esc_html( $this->options['adns_howdy_replace'] ) . ' ' . $howdy;
		}

		$wp_admin_bar->add_node( array(
			'id'    => 'my-account',
			'title' => $howdy,
		) );

	}

	/**
	 * Custom admin CSS.
	 *
	 * @since 2.0.0
	 */
	public function custom_css() {

		// Hide help tab.
		if ( 1 === absint( $this->options['adns_hide_help_tab'] ) ) {
			echo '<style type="text/css">#contextual-help-link-wrap { display: none !important; }</style>';
		}
		// Hide footer.
		if ( 1 === absint( $this->options['adns_hide_whole_footer'] ) ) {
			echo '<style type="text/css">#wpfooter { display:none!important; }</style>';
		}
		// Admin custom CSS.
		if ( 'CUSTOM' === esc_attr( $this->options['adns_admin_theme'] ) ) {
			if ( ! empty( $this->options['adns_custom_admin_theme_content'] ) ) {
				echo '<style>';
				echo $this->options['adns_custom_admin_theme_content'];
				echo '</style>';
			}
		}

	}

	/**
	 * Custom login CSS.
	 *
	 * @since 2.0.0
	 */
	public function custom_login_css() {

		// Login logo.
		if ( ! empty( $this->options['adns_login_logo_url'] ) ) {
			echo '<style type="text/css">
              div#login h1 a { background-image:url(' . esc_url( $this->options['adns_login_logo_url'] ) . ') !important;
                    background-size: auto auto !important;width: auto !important;}
            </style>';
		}

		// Login custom CSS.
		if ( 'CUSTOM' === esc_attr( $this->options['adns_login_theme'] ) ) {
			if ( ! empty( $this->options['adns_custom_login_theme_content'] ) ) {
				echo '<style type="text/css">';
				echo $this->options['adns_custom_login_theme_content'];
				echo '</style>';
			}
		}

		// Login background image.
		if ( ! empty( $this->options['adns_login_background_url'] ) ) {
			echo '<style type="text/css">';
			echo 'body.login { background : url(' . esc_url( $this->options['adns_login_background_url'] ) . ') no-repeat scroll center top !important; }' ;
			echo '</style>';
		}

		// Login background color.
		if ( ! empty( $this->options['adns_login_background_color'] ) ) {
			echo '<style type="text/css">';
			echo 'body.login {background-color:'  . esc_attr( $this->options['adns_login_background_color'] ) . '!important;} ';
			echo '</style>';
		}

	}

	/**
	 * Change login logo URL link.
	 *
	 * @since 2.0.0
	 *
	 * @param string $link Login link URL.
	 * @return string Modified login link URL.
	 */
	public function change_login_logo_url_link( $link ) {

		if ( ! empty( $this->options['adns_login_logo_url'] ) ) {
			$link = esc_url( home_url( '/' ) );
		}
		return $link;

	}

	/**
	 * Change footer version content.
	 *
	 * @since 2.0.0
	 *
	 * @param string $output Footer content.
	 * @return string Modified footer content.
	 */
	public function change_footer_version( $output ) {
		$output = '';

		if ( 1 !== absint( $this->options['adns_hide_footer_version'] ) ) {
			$output .= wp_kses_post( $this->options['adns_footer_version'] );
		}

		return $output;
	}

	/**
	 * Change footer content.
	 *
	 * @since 2.0.0
	 *
	 * @param string $output Footer content.
	 * @return string Modified footer content.
	 */
	public function change_footer_text( $output ) {
		$output = '';

		if ( 1 === absint( $this->options['adns_hide_footer_text'] ) ) {
			return $output;
		}

		if ( ! empty( $this->options['adns_footer_logo'] ) ) {
			$output = '<img src="' . esc_url( $this->options['adns_footer_logo'] ) . '" alt="" class="adns-footer-logo" />';
		}
		$output .= wp_kses_post( $this->options['adns_footer_text'] );
		return $output;
	}

	/**
	 * Login theme loader.
	 *
	 * @since 2.0.0
	 */
	public function login_theme_loader() {
		$theme = strtolower( esc_attr( $this->options['adns_login_theme'] ) );
		if ( ! in_array( $theme, array( '-1', 'custom' ) ) ) {
			wp_enqueue_style( 'adns-login-theme', plugins_url( "css/login-theme/$theme.css", __FILE__ ) );
		}
	}

	/**
	 * Screen columns.
	 *
	 * @since 2.0.0
	 *
	 * @param array $columns Columns array.
	 * @return array Modified columns array.
	 */
	public function change_number_of_screen_columns_available( $columns ) {
		if ( absint( $this->options['adns_no_of_columns_available_in_dashboard'] ) > 0 ) {
			$columns['dashboard'] = absint( $this->options['adns_no_of_columns_available_in_dashboard'] );
		}

		return $columns;
	}

	/**
	 * Screen columns user settings.
	 *
	 * @since 2.1.0
	 *
	 * @param int $columns Columns.
	 * @return int Modified columns.
	 */
	public function change_user_number_of_screen_columns_available( $columns ) {
		if ( absint( $this->options['adns_no_of_columns_available_in_dashboard'] ) > 0 ) {
			$columns = absint( $this->options['adns_no_of_columns_available_in_dashboard'] );
		}

		return $columns;
	}

	/**
	 * Dashboard widgets customization.
	 *
	 * @since 2.0.0
	 */
	public function dashboard_widgets_customization() {

		// Show hide dashboard widgets.
		if ( ! empty( $this->options['adns_hide_dashboard_widgets'] ) && is_array( $this->options['adns_hide_dashboard_widgets'] ) ) {
			foreach ( $this->options['adns_hide_dashboard_widgets'] as $widget ) {
				switch ( $widget ) {
					case 'dashboard_activity':
						// Activity.
						$this->remove_dashboard_widget( 'dashboard_activity', 'normal' );
						break;
					case 'dashboard_primary':
						// WordPress News.
						$this->remove_dashboard_widget( 'dashboard_primary', 'side' );
						break;
					case 'dashboard_right_now':
						// At a Glance.
						$this->remove_dashboard_widget( 'dashboard_right_now', 'normal' );
						break;
					case 'dashboard_quick_press':
						// Quick Draft.
						$this->remove_dashboard_widget( 'dashboard_quick_press', 'side' );
						break;
					case 'dashboard_site_health':
						// Site Health.
						$this->remove_dashboard_widget( 'dashboard_site_health', 'normal' );
						break;
					default:
						break;
				}
			}
		}

		// Add new widget.
		if ( 1 === absint( $this->options['adns_add_custom_dashboard_widget_onoff'] ) ) {
			$custom_dashboard_title = esc_attr( $this->options['adns_my_custom_dashboard_widget_title'] );
			wp_add_dashboard_widget( 'my_custom_dashboard_widget', $custom_dashboard_title, array( $this, 'custom_dashboard_widget_content_function' ) );
		}

	}

	/**
	 * Dashboard widgets customization.
	 *
	 * @since 2.0.0
	 *
	 * @param string $widget Widget id.
	 * @param string $side Widget side.
	 */
	private function remove_dashboard_widget( $widget, $side ) {
		global $wp_meta_boxes;
		// nspre( $wp_meta_boxes );

		if ( ! in_array( $side, array( 'side', 'normal' ) ) ) {
			return;
		}

		if ( isset( $wp_meta_boxes['dashboard'][$side]['core'][$widget] ) ) {
			unset( $wp_meta_boxes['dashboard'][$side]['core'][$widget] );
		}
	}

	/**
	 * Render custom dashboard widgets content.
	 *
	 * @since 2.0.0
	 */
	public function custom_dashboard_widget_content_function() {

		echo wp_kses_post( $this->options['adns_my_custom_dashboard_widget_content'] );

	}

	/**
	 * Customize from email.
	 *
	 * @since 2.0.0
	 *
	 * @param string $email From email.
	 * @return string Modified from email.
	 */
	public function new_mail_from_email( $email ) {
		if ( ! empty( $this->options['adns_default_email_address_email'] ) ) {
			$email = esc_attr( $this->options['adns_default_email_address_email'] );
		}
		return $email;
	}

	/**
	 * Customize from name.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name From email.
	 * @return string Modified from email.
	 */
	public function new_mail_from_name( $name ) {
		if ( ! empty( $this->options['adns_default_email_address_name'] ) ) {
			$name = esc_attr( $this->options['adns_default_email_address_name'] );
		}
		return $name;
	}

	/**
	 * Customize nagging bar.
	 *
	 * @since 2.0.0
	 */
	public function hide_update_nagging_bar() {
		if ( 1 === absint( $this->options['adns_hide_update_nagging_bar'] ) ) {
			remove_action( 'admin_notices', 'update_nag', 3 );
			remove_action( 'network_admin_notices', 'update_nag', 3 );
		}
	}

	/**
	 * Admin sidebar.
	 *
	 * @since 2.0.0
	 */
	public function admin_sidebar() {
		?>
        <div class="meta-box-sortables">

        	<div class="postbox">

        		<h3><span>Help &amp; Support</span></h3>
        		<div class="inside">
        			<ul>
        				<li><strong>Questions, bugs, or great ideas?</strong></li>
        				<li><a href="https://wordpress.org/support/plugin/admin-customizer/" target="_blank">Visit our plugin support page</a></li>
        				<li><strong>Wanna help make this plugin better?</strong></li>
        				<li><a href="https://wordpress.org/support/plugin/admin-customizer/reviews/#new-post" target="_blank">Review and rate this plugin on WordPress.org</a></li>
        			</ul>
        		</div> <!-- .inside -->

        	</div> <!-- .postbox -->

            <div class="postbox">

            	<h3><span>My Blog</span></h3>
            	<div class="inside">
            		<?php
            		$rss = fetch_feed( 'https://www.nilambar.net/category/wordpress/feed' );

            		$maxitems = 0;

            		$rss_items = array();

            		if ( ! is_wp_error( $rss ) ) {
            			$maxitems  = $rss->get_item_quantity( 5 );
            			$rss_items = $rss->get_items( 0, $maxitems );
            		}
            		?>

            		<?php if ( ! empty( $rss_items ) ) : ?>

            			<ul>
            				<?php foreach ( $rss_items as $item ) : ?>
            					<li><a href="<?php echo esc_url( $item->get_permalink() ); ?>" target="_blank"><?php echo esc_html( $item->get_title() ); ?></a></li>
            				<?php endforeach; ?>
            			</ul>

            		<?php endif; ?>
            	</div> <!-- .inside -->

            </div> <!-- .postbox -->

        </div> <!-- .meta-box-sortables -->
        <?php
	}

	/**
	 * Admin scripts.
	 *
	 * @since 2.0.0
	 */
	function admin_scripts() {

		wp_enqueue_style( 'admin-customizer-css', plugin_dir_url( __FILE__ ) . '/css/admin.css', false, '2.0.0' );

	}
}

$admin_customizer = new AdminCustomizer();

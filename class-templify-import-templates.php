<?php
/**
 * Importer class.
 *
 * @package Templify Import Templates
 */



/**
 * Block direct access to the main plugin file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Main plugin class with initialization tasks.
 */
class Importer_Templates {

    /**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * The instance of the Importer class.
	 *
	 * @var object
	 */
	public $importer;

	/**
	 * The resulting page's hook_suffix, or false if the user does not have the capability required.
	 *
	 * @var boolean or string
	 */
	private $plugin_page;

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	public $import_files;

	/**
	 * The path of the log file.
	 *
	 * @var string
	 */
	public $log_file_path;

	/**
	 * The index of the `import_files` array (which import files was selected).
	 *
	 * @var int
	 */
	private $selected_index;

	/**
	 * The palette for the import.
	 *
	 * @var string
	 */
	private $selected_palette;

	/**
	 * The font for the import.
	 *
	 * @var string
	 */
	private $selected_font;

	/**
	 * The page for the import.
	 *
	 * @var string
	 */
	private $selected_page;

	/**
	 * The selected builder for import.
	 *
	 * @var string
	 */
	private $selected_builder;

	/**
	 * Import Single Override colors
	 *
	 * @var boolean
	 */
	private $override_colors;

	/**
	 * Import Single Override fonts
	 *
	 * @var boolean
	 */
	private $override_fonts;

	/**
	 * Import Shop/Cart/Checkout Pages.
	 *
	 * @var boolean
	 */
	private $ss = false;

	/**
	 * The paths of the actual import files to be used in the import.
	 *
	 * @var array
	 */
	private $selected_import_files;

	/**
	 * Holds any error messages, that should be printed out at the end of the import.
	 *
	 * @var string
	 */
	public $frontend_error_messages = array();

	/**
	 * Was the before content import already triggered?
	 *
	 * @var boolean
	 */
	private $before_import_executed = false;

	/**
	 * Make plugin page options available to other methods.
	 *
	 * @var array
	 */
	private $plugin_page_setup = array();

	/**
	 * Used to cache token authorization to prevent multiple
	 * remote requests to the licensing server in the same
	 * request lifecycle.
	 *
	 * @var null|bool
	 */
	private static $authorized_cache = null;

    /**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Construct function
	 */
	public function __construct() {
		// Set plugin constants.
		$this->include_plugin_files();
		add_action( 'init', array( $this, 'init_config' ) );
    }

    	/**
	 * Include all plugin files.
	 */


	 
	private function include_plugin_files() {
		//require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-template-library-rest-api.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-template-database-importer.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-author-meta.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-export-option.php';
	//	require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-plugin-check.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-helpers.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-actions.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-widget-importer.php';
		//require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-give.php';
        require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-importer.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-logger.php';
	 	require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-logger-cli.php';
	 	require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-downloader.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-customizer-importer.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-elementor.php';
	//	require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-fluent.php';
		/**
		 * AI-specific usage tracking. Only track if AI is opted in by user.
		 */
		// require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-starter-ai-events.php';
		// $ai_events = new \Kadence_Starter_Templates_AI_Events();
		// $ai_events->register();
		
		
	}



		/**
	 * Get Page by title.
	 */
	public function get_page_by_title( $page_title, $output = OBJECT, $post_type = 'page' ) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'title'                  => $page_title,
				'post_status'            => 'all',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'orderby'                => 'date',
				'order'                  => 'ASC',
			)
		);

		if ( ! empty( $query->post ) ) {
			$_post = $query->post;

			if ( ARRAY_A === $output ) {
				return $_post->to_array();
			} elseif ( ARRAY_N === $output ) {
				return array_values( $_post->to_array() );
			}

			return $_post;
		}

		return null;
	} 

	/**
	 * Add a little css for submenu items.
	 */
	public function basic_css_menu_support() {
		wp_register_style( 'templify-import-admin', false );
		wp_enqueue_style( 'templify-import-admin' );
		$css = '#menu-appearance .wp-submenu a[href^="themes.php?page=kadence-"]:before {content: "\21B3";margin-right: 0.5em;opacity: 0.5;}';
		wp_add_inline_style( 'templify-import-admin', $css );
	}

    /**
	 * Templify Import
	 */
	public function init_config() {
			add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'basic_css_menu_support' ) );
			if ( is_admin() ) {
				// Ajax Calls.
				add_action( 'wp_ajax_templify_import_demo_data', array( $this, 'import_demo_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_initial', array( $this, 'initial_install_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_install_plugins', array( $this, 'install_plugins_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_customizer_data', array( $this, 'import_customizer_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_after_import_data', array( $this, 'after_all_import_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_single_data', array( $this, 'import_demo_single_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_remove_past_import_data', array( $this, 'remove_past_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_check_plugin_data', array( $this, 'check_plugin_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_importer_dismiss_notice', array( $this, 'ajax_dismiss_starter_notice' ) );
			}

			add_action( 'init', array( $this, 'setup_plugin_with_filter_data' ) );
		add_action( 'templify-importer-templates/after_import', array( $this, 'templify_templify_theme_after_import' ), 10, 3 );

		add_action( 'templify-importer-templates/after_import', array( $this, 'templify_elementor_after_import' ), 20, 3 );

		add_filter( 'plugin_action_links_templify-importer-templates/templify-importer-templates.php', array( $this, 'add_settings_link' ) );

		add_filter( 'update_post_metadata', array( $this, 'forcibly_fix_issue_with_metadata' ), 15, 5 );

	
	}



	/**
	 * Add settings link
	 *
	 * @param array $links holds plugin links.
	 */
	public function add_settings_link( $links ) {
		$starter_link = admin_url( 'admin.php?page=templify-importer-templates' );
		$settings_link = '<a href="' . esc_url( $starter_link ) . '">' . __( 'View Template Library', 'templify-importer-templates' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Templify Import function.
	 */
	public function import_demo_woocommerce( $shop = 'Shop', $cart = 'Cart', $checkout = 'Checkout', $myaccount = 'My Account' ) {
		$woopages = array(
			'woocommerce_shop_page_id'      => $shop,
			'woocommerce_cart_page_id'      => $cart,
			'woocommerce_checkout_page_id'  => $checkout,
			'woocommerce_myaccount_page_id' => $myaccount,
		);
		foreach ( $woopages as $woo_page_name => $woo_page_title ) {
			$woopage = $this->get_page_by_title( $woo_page_title );
			if ( isset( $woopage ) && $woopage->ID ) {
				update_option( $woo_page_name, $woopage->ID );
			}
		}

		// We no longer need to install pages.
		delete_option( '_wc_needs_pages' );
		delete_transient( '_wc_activation_redirect' );

		// Flush rules after install.
		flush_rewrite_rules();
	}


	/**
	 * Templify After Import functions.
	 *
	 * @param array $selected_import the selected import.
	 */
	public function templify_templify_theme_after_import( $selected_import, $selected_palette, $selected_font ) {
		if ( class_exists( 'woocommerce' ) && isset( $selected_import['ecommerce'] ) && $selected_import['ecommerce'] ) {
			$this->import_demo_woocommerce();
		}
		
		if ( function_exists( 'tribe_update_option' ) ) {
			tribe_update_option( 'toggle_blocks_editor', true );
		}
	
			$menus_array = array();
			foreach ( $selected_import['menus'] as $key => $value ) {
				$menu = get_term_by( 'name', $value['title'], 'nav_menu' );
				if ( $menu ) {
					$menus_array[ $value['menu'] ] = $menu->term_id;
				}
			}
			set_theme_mod( 'nav_menu_locations', $menus_array );
		

		// Fix Custom Menu items.
	
			$site_url = $this->remove_trailing_slash( $selected_import['url'] );

			$menu_item_ids = $this->get_menu_item_ids();
			if ( is_array( $menu_item_ids ) ) {
				foreach ( $menu_item_ids as $menu_id ) {
					$menu_url = get_post_meta( $menu_id, '_menu_item_url', true );

					if ( $menu_url ) {
						$menu_url = str_replace( $site_url, site_url(), $menu_url );
						update_post_meta( $menu_id, '_menu_item_url', $menu_url );
					}
				}
			}
		

		
			$homepage = $this->get_page_by_title( $selected_import['homepage'] );
			if ( isset( $homepage ) && $homepage->ID ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $homepage->ID ); // Front Page.
			}
		
	
			$blogpage = $this->get_page_by_title( $selected_import['blogpage'] );
			if ( isset( $blogpage ) && $blogpage->ID ) {
				update_option( 'page_for_posts', $blogpage->ID );
			}
		
		
			$palette_presets = json_decode( '{"base":[{"color":"#2B6CB0"},{"color":"#265E9A"},{"color":"#222222"},{"color":"#3B3B3B"},{"color":"#515151"},{"color":"#626262"},{"color":"#E1E1E1"},{"color":"#F7F7F7"},{"color":"#ffffff"}],"bright":[{"color":"#255FDD"},{"color":"#00F2FF"},{"color":"#1A202C"},{"color":"#2D3748"},{"color":"#4A5568"},{"color":"#718096"},{"color":"#EDF2F7"},{"color":"#F7FAFC"},{"color":"#ffffff"}],"darkmode":[{"color":"#3296ff"},{"color":"#003174"},{"color":"#ffffff"},{"color":"#f7fafc"},{"color":"#edf2f7"},{"color":"#cbd2d9"},{"color":"#2d3748"},{"color":"#252c39"},{"color":"#1a202c"}],"orange":[{"color":"#e47b02"},{"color":"#ed8f0c"},{"color":"#1f2933"},{"color":"#3e4c59"},{"color":"#52606d"},{"color":"#7b8794"},{"color":"#f3f4f7"},{"color":"#f9f9fb"},{"color":"#ffffff"}],"pinkish":[{"color":"#E21E51"},{"color":"#4d40ff"},{"color":"#040037"},{"color":"#032075"},{"color":"#514d7c"},{"color":"#666699"},{"color":"#deddeb"},{"color":"#efeff5"},{"color":"#f8f9fa"}],"pinkishdark":[{"color":"#E21E51"},{"color":"#4d40ff"},{"color":"#f8f9fa"},{"color":"#efeff5"},{"color":"#deddeb"},{"color":"#c3c2d6"},{"color":"#514d7c"},{"color":"#221e5b"},{"color":"#040037"}],"green":[{"color":"#049f82"},{"color":"#008f72"},{"color":"#222222"},{"color":"#353535"},{"color":"#454545"},{"color":"#676767"},{"color":"#eeeeee"},{"color":"#f7f7f7"},{"color":"#ffffff"}],"fire":[{"color":"#dd6b20"},{"color":"#cf3033"},{"color":"#27241d"},{"color":"#423d33"},{"color":"#504a40"},{"color":"#625d52"},{"color":"#e8e6e1"},{"color":"#faf9f7"},{"color":"#ffffff"}],"mint":[{"color":"#2cb1bc"},{"color":"#13919b"},{"color":"#0f2a43"},{"color":"#133453"},{"color":"#587089"},{"color":"#829ab1"},{"color":"#e0fcff"},{"color":"#f5f7fa"},{"color":"#ffffff"}],"rich":[{"color":"#295CFF"},{"color":"#0E94FF"},{"color":"#1C0D5A"},{"color":"#3D3D3D"},{"color":"#57575D"},{"color":"#636363"},{"color":"#E1EBEE"},{"color":"#EFF7FB"},{"color":"#ffffff"}],"fem":[{"color":"#D86C97"},{"color":"#282828"},{"color":"#282828"},{"color":"#333333"},{"color":"#4d4d4d"},{"color":"#646464"},{"color":"#f7dede"},{"color":"#F6F2EF"},{"color":"#ffffff"}],"hot":[{"color":"#FF5698"},{"color":"#000000"},{"color":"#020202"},{"color":"#020202"},{"color":"#4E4E4E"},{"color":"#808080"},{"color":"#FDEDEC"},{"color":"#FDF6EE"},{"color":"#ffffff"}],"bold":[{"color":"#000000"},{"color":"#D1A155"},{"color":"#000000"},{"color":"#010101"},{"color":"#111111"},{"color":"#282828"},{"color":"#F6E7BC"},{"color":"#F9F7F7"},{"color":"#ffffff"}],"teal":[{"color":"#7ACFC4"},{"color":"#044355"},{"color":"#000000"},{"color":"#010101"},{"color":"#111111"},{"color":"#282828"},{"color":"#F5ECE5"},{"color":"#F9F7F7"},{"color":"#ffffff"}]}', true );
			
				$default = json_decode( '{"palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"second-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"third-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"active":"palette"}', true );
				$default['palette'][0]['color'] = $palette_presets[ $selected_palette ][0]['color'];
				$default['palette'][1]['color'] = $palette_presets[ $selected_palette ][1]['color'];
				$default['palette'][2]['color'] = $palette_presets[ $selected_palette ][2]['color'];
				$default['palette'][3]['color'] = $palette_presets[ $selected_palette ][3]['color'];
				$default['palette'][4]['color'] = $palette_presets[ $selected_palette ][4]['color'];
				$default['palette'][5]['color'] = $palette_presets[ $selected_palette ][5]['color'];
				$default['palette'][6]['color'] = $palette_presets[ $selected_palette ][6]['color'];
				$default['palette'][7]['color'] = $palette_presets[ $selected_palette ][7]['color'];
				$default['palette'][8]['color'] = $palette_presets[ $selected_palette ][8]['color'];
				update_option( 'templify_global_palette', json_encode( $default ) );
			
		
		
	}


	/**
	 * After import run elementor stuff.
	 */
	public function templify_elementor_after_import( $selected_import, $selected_palette, $selected_font ) {
		// If elementor make sure we set things up and clear cache.
		
			if ( class_exists( 'Elementor\Plugin' ) ) {
			
				if ( isset( $selected_import['content_width'] ) && 'large' === $selected_import['content_width'] ) {
					$container_width = array(
						'unit' => 'px',
						'size' => 1242,
						'sizes' => array(),
					);
					$container_width_tablet = array(
						'unit' => 'px',
						'size' => 700,
						'sizes' => array(),
					);
					if ( method_exists( \Elementor\Plugin::$instance->kits_manager, 'update_kit_settings_based_on_option' ) ) {
						\Elementor\Plugin::$instance->kits_manager->update_kit_settings_based_on_option( 'container_width', $container_width );
						\Elementor\Plugin::$instance->kits_manager->update_kit_settings_based_on_option( 'container_width_tablet', $container_width_tablet );
					}
				} else {
					$container_width = array(
						'unit' => 'px',
						'size' => 1140,
						'sizes' => array(),
					);
					$container_width_tablet = array(
						'unit' => 'px',
						'size' => 700,
						'sizes' => array(),
					);
					if ( method_exists( \Elementor\Plugin::$instance->kits_manager, 'update_kit_settings_based_on_option' ) ) {
						\Elementor\Plugin::$instance->kits_manager->update_kit_settings_based_on_option( 'container_width', $container_width );
						\Elementor\Plugin::$instance->kits_manager->update_kit_settings_based_on_option( 'container_width_tablet', $container_width_tablet );
					}
				}
				\Elementor\Plugin::instance()->files_manager->clear_cache();
			
		}
	}


    /**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function create_admin_page() {
	
		add_menu_page(
            'Templify Import',            // Page title
            'Templify Import',            // Menu title
            'manage_options',              // Capability
            'templify-import',            // Menu slug
            '',                             // Callback function
            'dashicons-layout',            // Icon URL or Dashicons class
            30                             // Position
        );


        add_submenu_page(
            'templify-import',             // Parent slug
            'Home',             // Page title
            'Home',             // Menu title
            'manage_options',               // Capability
            'templify-import',             // Menu slug
            array( $this, 'templify_import_main_page')   // Callback function
        );


        add_submenu_page(
            'templify-import',            // Parent slug
            'Templify Template',   // Page title
            'Templify Template',                    // Menu title
            'manage_options',              // Capability
            'templify-importer-templates',   // Menu slug
            array( $this, 'templify_import_templates_page')     // Callback function
        );


        add_submenu_page(
            'templify-import',            // Parent slug
            'Settings',   // Page title
            'Settings',                    // Menu title
            'manage_options',              // Capability
            'templify-import-settings',   // Menu slug
            array( $this, 'templify_import_settings_page')   // Callback function
        );
    
		
	}

    public function templify_import_main_page(){

    }


	/**
	 * Add a little css for submenu items.
	 * @param string $forward null, unless we should overide.
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before updating.
	 */
	public function forcibly_fix_issue_with_metadata( $forward, $object_id, $meta_key, $meta_value, $prev_value ) {
		$meta_keys_to_allow = [
			'kt_blocks_editor_width' => true,
			'_kad_post_transparent' => true,
			'_kad_post_title' => true,
			'_kad_post_layout' => true,
			'_kad_post_content_style' => true,
			'_kad_post_vertical_padding' => true,
			'_kad_post_sidebar_id' => true,
			'_kad_post_feature' => true,
			'_kad_post_feature_position' => true,
			'_kad_post_header' => true,
			'_kad_post_footer' => true,
		];
		if ( isset( $meta_keys_to_allow[$meta_key] ) ) {
			$old_value = get_metadata( 'post', $object_id, $meta_key );
			if ( is_array( $old_value ) && 1 < count( $old_value ) ) {
				// Data is an array which shouldn't be the case so we need to clean that up.
				delete_metadata( 'post', $object_id, $meta_key );
				add_metadata( 'post', $object_id, $meta_key, $meta_value );
				return true;
			}
		}
		return $forward;
	}

	/**
	 * Get the current license key for the plugin.
	 *
	 * @return array{key: string, email: string}
	 */
	public function get_current_license_data(): array {

		$license_data = array(
			'ktp_api_key'   => 'ktl_wc_order_2lLY7ITAV3etu_am_oG518g6iDCIN',
			'activation_email' => 'admin@bloggertutor.com',
		);

		return $license_data;
	}
	/**
	 * Get the current license key for the plugin.
	 *
	 * @return string 
	 */
	public function get_current_license_key() {

		// if ( function_exists( 'kadence_blocks_get_current_license_data' ) ) {
		// 	$data = kadence_blocks_get_current_license_data();
		// 	if ( ! empty( $data['key'] ) ) {
		// 		return $data['key'];
		// 	}
		// } elseif ( class_exists( 'Kadence_Theme_Pro' ) ) {
		// 	$pro_data = array();
		// 	if ( function_exists( '\KadenceWP\KadencePro\StellarWP\Uplink\get_license_key' ) ) {
		// 		$pro_data['ktp_api_key'] = \KadenceWP\KadencePro\StellarWP\Uplink\get_license_key( 'kadence-theme-pro' );
		// 	}
		// 	if ( empty( $pro_data ) ) {
		// 		if ( is_multisite() && ! apply_filters( 'kadence_activation_individual_multisites', false ) ) {
		// 			$pro_data = get_site_option( 'ktp_api_manager' );
		// 		} else {
		// 			$pro_data = get_option( 'ktp_api_manager' );
		// 		}
		// 	}
		// 	if ( ! empty( $pro_data['ktp_api_key'] ) ) {
		// 		return $pro_data['ktp_api_key'];
		// 	}
		// } else {
		// 	$key = get_license_key( 'templify-importer-templates' );
		// 	if ( ! empty( $key ) ) {
		// 		return $key;
		// 	}
		// }
		return 'ktl_wc_order_2lLY7ITAV3etu_am_oG518g6iDCIN';
	}
	/**
	 * Get the current license email for the plugin.
	 *
	 * @return string 
	 */
	public function get_current_license_email() {

		// if ( function_exists( 'kadence_blocks_get_current_license_data' ) ) {
		// 	$data = kadence_blocks_get_current_license_data();
		// 	if ( ! empty( $data['email'] ) ) {
		// 		return $data['email'];
		// 	}
		// } else if ( class_exists( 'Kadence_Theme_Pro' ) ) {
		// 	$pro_data = array();
		// 	if ( function_exists( '\KadenceWP\KadencePro\StellarWP\Uplink\get_license_key' ) ) {
		// 		$pro_data['ktp_api_key'] = \KadenceWP\KadencePro\StellarWP\Uplink\get_license_key( 'kadence-theme-pro' );
		// 	}
		// 	if ( empty( $pro_data ) ) {
		// 		if ( is_multisite() && ! apply_filters( 'kadence_activation_individual_multisites', false ) ) {
		// 			$pro_data = get_site_option( 'ktp_api_manager' );
		// 		} else {
		// 			$pro_data = get_option( 'ktp_api_manager' );
		// 		}
		// 	}
		// 	if ( ! empty( $pro_data['activation_email'] ) ) {
		// 		return $pro_data['activation_email'];
		// 	}
		// }
		// $current_user = wp_get_current_user();
	//	return $current_user->user_email;
	return 'admin@bloggertutor.com';
	}

    
	/**
	 * Loads admin style sheets and scripts
	 */
	public function scripts() {
		$using_network_enabled = false;
		$is_network_admin      = is_multisite() && is_network_admin() ? true : false;
		$plugins = array ();
		$palettes = array();
		$fonts = array();
		$old_data = get_option( '_templify_import_templates_last_import_data', array() );
		$has_content = false;
		$has_previous = false;
		// if ( ! empty( $old_data ) ) {
		// 	$has_content  = true;
		// 	$has_previous = true;
		// }
		// Check for multiple posts.
		if ( false === $has_content ) {
			$has_content = ( 1 < wp_count_posts()->publish ? true : false );
		}
		if ( false === $has_content ) {
			// Check for multiple pages.
			$has_content = ( 1 < wp_count_posts( 'page' )->publish ? true : false );
		}
		if ( false === $has_content ) {
			// Check for multiple images.
			$has_content = ( 0 < wp_count_posts( 'attachment' )->inherit ? true : false );
		}
		$pro_data = $this->get_current_license_data();
		$current_user     = wp_get_current_user();
		$license_key    = $this->get_current_license_key();
		$disconnect_url = '';
		$is_authorized  = false;
		$user_email       = $current_user->user_email;
		$show_builder_choice = ( 'active' === $plugins['elementor']['state'] ? true : false );
		$subscribed       =true ;
		wp_enqueue_media();
		$templify_import_templates_meta = $this->get_asset_file( 'dist/importer-templates' );
		wp_enqueue_style( 'templify-importer-templates', TEMPLIFY_IMPORT_TEMPLATES_URL . 'dist/importer-templates.css', array( 'wp-components' ), TEMPLIFY_IMPORT_TEMPLATES_VERSION );
		wp_enqueue_script( 'templify-importer-templates', TEMPLIFY_IMPORT_TEMPLATES_URL . 'dist/importer-templates.js', array_merge( array( 'wp-api', 'wp-components', 'wp-plugins', 'wp-edit-post' ), $templify_import_templates_meta['dependencies'] ), $templify_import_templates_meta['version'], true );
		wp_localize_script(
			'templify-importer-templates',
			'templifyImporterParams',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'homeUrl'             => home_url( '/' ),
				'pagesUrl'            => admin_url( 'edit.php?post_type=page' ),
				'ajax_nonce'           => wp_create_nonce( 'templify-ajax-verification' ),
				'livePreviewStyles'    => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/css/live-preview-base.css?ver=' . TEMPLIFY_IMPORT_TEMPLATES_VERSION,
				'has_content'          => $has_content,
				'has_previous'         => $has_previous,
				'starterSettings'      => get_option( 'kadence_starter_templates_config' ),
				'proData'              => $pro_data,
				'notice'               => esc_html__( 'Please Note: Full site importing is designed for new/empty sites with no content. Your site customizer settings, widgets, menus will all be overridden.', 'templify-importer-templates' ),
				'notice_previous'      => esc_html( 'Please Note: Full site importing is designed for new/empty sites with no content. Your site customizer settings, widgets, menus will all be overridden. It is recommended that you enable "Delete Previously Imported Posts and Images" if you are testing out different starter templates.'),
				'remove_progress'      => esc_html__( 'Removing Past Imported Content', 'templify-importer-templates' ),
				'subscribe_progress'   => esc_html__( 'Getting Started', 'templify-importer-templates' ),
				'plugin_progress'      => esc_html__( 'Checking/Installing/Activating Required Plugins', 'templify-importer-templates' ),
				'content_progress'     => esc_html__( 'Importing Content...', 'templify-importer-templates' ),
				'content_new_progress' => esc_html__( 'Importing Content... Creating pages.', 'templify-importer-templates' ),
				'content_newer_progress' => esc_html__( 'Importing Content... Downloading images.', 'templify-importer-templates' ),
				'content_newest_progress' => esc_html__( 'Importing Content... Still Importing.', 'templify-importer-templates' ),
				'widgets_progress'     => esc_html__( 'Importing Widgets...', 'templify-importer-templates' ),
				'customizer_progress'  => esc_html__( 'Importing Customizer Settings...', 'templify-importer-templates' ),
				'user_email'           => $user_email,
				'subscribed'           => $subscribed,
				'openBuilder'          => $show_builder_choice,
				'isAuthorized'        => $is_authorized,
				'licenseKey'          => $license_key,
				'authUrl'             => esc_url( $auth_url ),
				'disconnectUrl'       => esc_url( $disconnect_url ),
				'isNetworkAdmin'      => $is_network_admin,
				'isNetworkEnabled'    => $using_network_enabled,
				'blocksActive'        =>  true,
			)
		);
	}


	/**
	 * Get the asset file produced by wp scripts.
	 *
	 * @param string $filepath the file path.
	 * @return array
	 */
	public function get_asset_file( $filepath ) {
		$asset_path = TEMPLIFY_IMPORT_TEMPLATES_PATH . $filepath . '.asset.php';
		return file_exists( $asset_path )
			? include $asset_path
			: array(
				'dependencies' => array( 'lodash', 'react', 'react-dom', 'wp-block-editor', 'wp-blocks', 'wp-data', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-primitives', 'wp-api' ),
				'version'      => TEMPLIFY_IMPORT_TEMPLATES_VERSION,
			);
	}


	/**
	 * Check if a setting is registered.
	 *
	 * @param string $option_name the option group.
	 */
	public function is_setting_registered( $option_name ) {
		global $wp_registered_settings;
		return isset( $wp_registered_settings[ $option_name ] );
	}





    
	/**
	 * Plugin page display.
	 * Output (HTML) is in another file.
	 */
	public function templify_import_templates_page() {
		?>
		<div class="wrap templify_theme_starter_dash">
			<div class="templify_theme_starter_dashboard">
				<h2 class="notices" style="display:none;"></h2>
				<?php settings_errors(); ?>
				<div class="page-grid">
					<div class="templify_importer_dashboard_main">
					</div>
			</div>
		</div>
		<?php
	}


    public function templify_import_settings_page(){
        
    }


	
	/**
	 * Getter function to retrieve the private log_file_path value.
	 *
	 * @return string The log_file_path value.
	 */
	public function get_log_file_path() {
		return $this->log_file_path;
	}


	/**
	 * Main AJAX callback function for:
	 * 1). prepare import files (uploaded or predefined via filters)
	 * 2). execute 'before content import' actions (before import WP action)
	 * 3). import content
	 * 4). execute 'after content import' actions (before widget import WP action, widget import, customizer import, after import WP action)
	 */
	public function import_demo_single_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		ini_set( 'memory_limit', '512M'  );


			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			
			$this->log_file_path = Helpers::get_log_path();
			
			// Get selected file index or set it to 0.
			$this->selected_index = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
			$this->selected_builder = empty( $_POST['builder'] ) ? 'blocks' : sanitize_text_field( $_POST['builder'] );
			$this->selected_page = empty( $_POST['page_id'] ) ? '' : sanitize_text_field( $_POST['page_id'] );
			$this->override_colors = 'true' === $_POST['override_colors'] ? true : false;
			$this->override_fonts = 'true' === $_POST['override_fonts'] ? true : false;
			$this->selected_palette = empty( $_POST['palette'] ) ? '' : sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = empty( $_POST['font'] ) ? '' : sanitize_text_field( $_POST['font'] );

			
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				wp_send_json_error();
			}

			// Download the import files (content, widgets and customizer files).
			$this->selected_import_files = Helpers::download_import_file( $this->import_files[ $this->selected_index ], $this->selected_page );

			// Check Errors.
			if ( is_wp_error( $this->selected_import_files ) ) {
				// Write error to log file and send an AJAX response with the error.
				Helpers::log_error_and_send_ajax_response(
					$this->selected_import_files->get_error_message(),
					$this->log_file_path,
					esc_html__( 'Downloaded files', 'templify-importer-templates' )
				);
			}
			if ( apply_filters( 'kadence_starter_templates_save_log_files', false ) ) {
				// Add this message to log file.
				$log_added = Helpers::append_to_file(
					sprintf(
						__( 'The import files for: %s were successfully downloaded!', 'templify-importer-templates' ),
						$this->import_files[ $this->selected_index ]['slug']
					) . Helpers::import_file_info( $this->selected_import_files ),
					$this->log_file_path,
					esc_html__( 'Downloaded files' , 'templify-importer-templates' )
				);
			}
		
		//}

		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );

		// If elementor make sure the defaults are off.
		$elementor = false;
		if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
			update_option( 'elementor_disable_color_schemes', 'yes' );
			update_option( 'elementor_disable_typography_schemes', 'yes' );
			$elementor = true;
		
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		$new_post = '';
		if ( ! empty( $this->selected_import_files['content'] ) ) {
			$meta = ( ! empty( $this->import_files[ $this->selected_index ] ) && ! empty( $this->selected_page ) && isset( $this->import_files[ $this->selected_index ]['pages'] ) && isset( $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ] ) && isset( $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ]['meta'] ) ? $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ]['meta'] : 'inherit' );
			$logger = $this->importer->import_content( $this->selected_import_files['content'], true, $meta, $elementor );
			if ( is_object( $logger ) && property_exists( $logger, 'error_output' ) && $logger->error_output ) {
				$this->append_to_frontend_error_messages( $logger->error_output );
			} elseif ( is_object( $logger ) && $logger->messages ) {
				$messages = $logger->messages;
				if ( isset( $messages[1] ) && isset( $messages[1]['level'] ) && 'debug' == $messages[1]['level'] && isset( $messages[1]['message'] ) && ! empty( $messages[1]['message'] ) ) {
					$pieces   = explode( ' ', $messages[1]['message'] );
					$new_post = array_pop( $pieces );
				}
			}
		}

		if ( $this->override_colors ) {
			if ( $this->selected_palette && ! empty( $this->selected_palette ) ) {
				$palette_presets = json_decode( '{"base":[{"color":"#2B6CB0"},{"color":"#265E9A"},{"color":"#222222"},{"color":"#3B3B3B"},{"color":"#515151"},{"color":"#626262"},{"color":"#E1E1E1"},{"color":"#F7F7F7"},{"color":"#ffffff"}],"bright":[{"color":"#255FDD"},{"color":"#00F2FF"},{"color":"#1A202C"},{"color":"#2D3748"},{"color":"#4A5568"},{"color":"#718096"},{"color":"#EDF2F7"},{"color":"#F7FAFC"},{"color":"#ffffff"}],"darkmode":[{"color":"#3296ff"},{"color":"#003174"},{"color":"#ffffff"},{"color":"#f7fafc"},{"color":"#edf2f7"},{"color":"#cbd2d9"},{"color":"#2d3748"},{"color":"#252c39"},{"color":"#1a202c"}],"orange":[{"color":"#e47b02"},{"color":"#ed8f0c"},{"color":"#1f2933"},{"color":"#3e4c59"},{"color":"#52606d"},{"color":"#7b8794"},{"color":"#f3f4f7"},{"color":"#f9f9fb"},{"color":"#ffffff"}],"pinkish":[{"color":"#E21E51"},{"color":"#4d40ff"},{"color":"#040037"},{"color":"#032075"},{"color":"#514d7c"},{"color":"#666699"},{"color":"#deddeb"},{"color":"#efeff5"},{"color":"#f8f9fa"}],"pinkishdark":[{"color":"#E21E51"},{"color":"#4d40ff"},{"color":"#f8f9fa"},{"color":"#efeff5"},{"color":"#deddeb"},{"color":"#c3c2d6"},{"color":"#514d7c"},{"color":"#221e5b"},{"color":"#040037"}],"green":[{"color":"#049f82"},{"color":"#008f72"},{"color":"#222222"},{"color":"#353535"},{"color":"#454545"},{"color":"#676767"},{"color":"#eeeeee"},{"color":"#f7f7f7"},{"color":"#ffffff"}],"fire":[{"color":"#dd6b20"},{"color":"#cf3033"},{"color":"#27241d"},{"color":"#423d33"},{"color":"#504a40"},{"color":"#625d52"},{"color":"#e8e6e1"},{"color":"#faf9f7"},{"color":"#ffffff"}],"mint":[{"color":"#2cb1bc"},{"color":"#13919b"},{"color":"#0f2a43"},{"color":"#133453"},{"color":"#587089"},{"color":"#829ab1"},{"color":"#e0fcff"},{"color":"#f5f7fa"},{"color":"#ffffff"}],"rich":[{"color":"#295CFF"},{"color":"#0E94FF"},{"color":"#1C0D5A"},{"color":"#3D3D3D"},{"color":"#57575D"},{"color":"#636363"},{"color":"#E1EBEE"},{"color":"#EFF7FB"},{"color":"#ffffff"}],"fem":[{"color":"#D86C97"},{"color":"#282828"},{"color":"#282828"},{"color":"#333333"},{"color":"#4d4d4d"},{"color":"#646464"},{"color":"#f7dede"},{"color":"#F6F2EF"},{"color":"#ffffff"}],"hot":[{"color":"#FF5698"},{"color":"#000000"},{"color":"#020202"},{"color":"#020202"},{"color":"#4E4E4E"},{"color":"#808080"},{"color":"#FDEDEC"},{"color":"#FDF6EE"},{"color":"#ffffff"}],"bold":[{"color":"#000000"},{"color":"#D1A155"},{"color":"#000000"},{"color":"#010101"},{"color":"#111111"},{"color":"#282828"},{"color":"#F6E7BC"},{"color":"#F9F7F7"},{"color":"#ffffff"}],"teal":[{"color":"#7ACFC4"},{"color":"#044355"},{"color":"#000000"},{"color":"#010101"},{"color":"#111111"},{"color":"#282828"},{"color":"#F5ECE5"},{"color":"#F9F7F7"},{"color":"#ffffff"}]}', true );
				if ( isset( $palette_presets[ $this->selected_palette ] ) ) {
					$default = json_decode( '{"palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"second-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"third-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"active":"palette"}', true );
					$default['palette'][0]['color'] = $palette_presets[ $this->selected_palette ][0]['color'];
					$default['palette'][1]['color'] = $palette_presets[ $this->selected_palette ][1]['color'];
					$default['palette'][2]['color'] = $palette_presets[ $this->selected_palette ][2]['color'];
					$default['palette'][3]['color'] = $palette_presets[ $this->selected_palette ][3]['color'];
					$default['palette'][4]['color'] = $palette_presets[ $this->selected_palette ][4]['color'];
					$default['palette'][5]['color'] = $palette_presets[ $this->selected_palette ][5]['color'];
					$default['palette'][6]['color'] = $palette_presets[ $this->selected_palette ][6]['color'];
					$default['palette'][7]['color'] = $palette_presets[ $this->selected_palette ][7]['color'];
					$default['palette'][8]['color'] = $palette_presets[ $this->selected_palette ][8]['color'];
					update_option( 'templify_global_palette', json_encode( $default ) );
				}
			} else {
				/**
				 * Execute the customizer import actions.
				 */
				do_action( 'templify-importer-templates/customizer_import_color_only_execution', $this->selected_import_files );
			}
		}
		
			

		// If elementor make sure the defaults are off.
		// if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
		// 	if ( class_exists( 'Elementor\Plugin' ) ) {
		// 		\Elementor\Plugin::instance()->files_manager->clear_cache();
		// 	}
		// }

		// Send a JSON response with final report.
		$this->final_response( $new_post );
	}


	/**
	 * Main AJAX callback function for:
	 * 1). prepare import files (uploaded or predefined via filters)
	 * 2). execute 'before content import' actions (before import WP action)
	 * 3). import content
	 * 4). execute 'after content import' actions (before widget import WP action, widget import, customizer import, after import WP action)
	 */
	public function import_demo_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		ini_set( 'memory_limit',  '512M' );
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();
		// Is this a new AJAX call to continue the previous import?
		
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			
				// Define log file path.
				$this->log_file_path = Helpers::get_log_path();
			

			// Get selected file index or set it to 0.
			$this->selected_index   = sanitize_text_field( $_POST['selected'] );
			$this->selected_palette = sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = sanitize_text_field( $_POST['font'] );
			$this->selected_builder = sanitize_text_field( $_POST['builder'] );

		
				$template_database  = Template_Database_Importer::get_instance();
				$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-importer-templates' ) );
			}
			/**
			 * 1). Prepare import files.
			 * Predefined import files via filter: templify-importer-templates/import_files
			 */
			if ( ! empty( $this->import_files[ $this->selected_index ] ) ) { // Use predefined import files from wp filter: templify-importer-templates/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_files( $this->import_files[ $this->selected_index ] );

				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'templify-importer-templates' )
					);
				}
				if ( apply_filters( 'kadence_starter_templates_save_log_files', false ) ) {
					// Add this message to log file.
					$log_added = Helpers::append_to_file(
						sprintf(
							__( 'The import files for: %s were successfully downloaded!', 'templify-importer-templates' ),
							$this->import_files[ $this->selected_index ]['slug']
						) . Helpers::import_file_info( $this->selected_import_files ),
						$this->log_file_path,
						esc_html__( 'Downloaded files' , 'templify-importer-templates' )
					);
				}
			} else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-importer-templates' ) );
			}
		
		// if ( class_exists( 'woocommerce' ) && isset( $this->import_files[ $this->selected_index ]['ecommerce'] ) && $this->import_files[ $this->selected_index ]['ecommerce'] && ! $this->import_woo_pages ) {
		// 	add_filter( 'stop_importing_woo_pages', '__return_true' );
		// }
		// If elementor make sure the defaults are off.
		if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
			update_option( 'elementor_disable_color_schemes', 'yes' );
			update_option( 'elementor_disable_typography_schemes', 'yes' );
		}
		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );
		if ( ! $this->before_import_executed ) {
			$this->before_import_executed = true;

			/**
			 * Save Current Theme mods for a potential undo.
			 */
			update_option( '_kadence_starter_templates_old_customizer', get_option( 'theme_mods_' . get_option( 'stylesheet' ) ) );
			// Save Import data for use if we need to reset it.
			update_option( '_kadence_starter_templates_last_import_data', $this->import_files[ $this->selected_index ], 'no' );
			/**
			 * 2). Execute the actions hooked to the 'templify-importer-templates/before_content_import_execution' action:
			 *
			 * Default actions:
			 * 1 - Before content import WP action (with priority 10).
			 */
			/**
			 * Clean up default contents.
			 */
			$hello_world = $this->get_page_by_title( 'Hello World', OBJECT, 'post' );
			if ( $hello_world ) {
				wp_delete_post( $hello_world->ID, true );// Hello World.
			}
			$sample_page = $this->get_page_by_title( 'Sample Page' );
			if ( $sample_page ) {
				wp_delete_post( $sample_page->ID, true ); // Sample Page.
			}
			wp_delete_comment( 1, true ); // WordPress comment.
			/**
			 * Clean up default woocommerce.
			 */
			$woopages = array(
				'woocommerce_shop_page_id'      => 'shop',
				'woocommerce_cart_page_id'      => 'cart',
				'woocommerce_checkout_page_id'  => 'checkout',
				'woocommerce_myaccount_page_id' => 'my-account',
			);
			foreach ( $woopages as $woo_page_option => $woo_page_slug ) {
				if ( get_option( $woo_page_option ) ) {
					wp_delete_post( get_option( $woo_page_option ), true );
				}
			}
			// Move All active widgets into inactive.
			$sidebars = wp_get_sidebars_widgets();
			if ( is_array( $sidebars ) ) {
				foreach ( $sidebars as $sidebar_id => $sidebar_widgets ) {
					if ( 'wp_inactive_widgets' === $sidebar_id ) {
						continue;
					}
					if ( is_array( $sidebar_widgets ) && ! empty( $sidebar_widgets ) ) {
						foreach ( $sidebar_widgets as $i => $single_widget ) {
							$sidebars['wp_inactive_widgets'][] = $single_widget;
							unset( $sidebars[ $sidebar_id ][ $i ] );
						}
					}
				}
			}
			wp_set_sidebars_widgets( $sidebars );
			// Reset to default settings values.
			delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );
			// Reset Global Palette
			update_option( 'kadence_global_palette', '{"palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"second-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"third-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"active":"palette"}' );
			do_action( 'templify-importer-templates/before_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index, $this->selected_palette, $this->selected_font );
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		
			$this->append_to_frontend_error_messages( $this->importer->import_content( $this->selected_import_files['content'] ) );
		

		/**
		 * 4). Execute the actions hooked to the 'templify-importer-templates/after_content_import_execution' action:
		 *
		 * Default actions:
		 * 1 - Before widgets import setup (with priority 10).
		 * 2 - Import widgets (with priority 20).
		 * 3 - Import Redux data (with priority 30).
		 */
		do_action( 'templify-importer-templates/after_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index, $this->selected_palette, $this->selected_font );
		// Save the import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );
		// Request the customizer import AJAX call.
		if ( ! empty( $this->selected_import_files['customizer'] ) ) {
			wp_send_json( array( 'status' => 'customizerAJAX' ) );
		}

		// Request the after all import AJAX call.
		if ( false !== has_action( 'templify-importer-templates/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}



		/**
	 * Get content importer data, so we can continue the import with this new AJAX request.
	 *
	 * @return boolean
	 */
	private function use_existing_importer_data() {
	
			$this->frontend_error_messages = empty( $data['frontend_error_messages'] ) ? array() : $data['frontend_error_messages'];
			$this->log_file_path           = empty( $data['log_file_path'] ) ? '' : $data['log_file_path'];
			$this->selected_index          = empty( $data['selected_index'] ) ? 0 : $data['selected_index'];
			$this->selected_palette        = empty( $data['selected_palette'] ) ? '' : $data['selected_palette'];
			$this->selected_font           = empty( $data['selected_font'] ) ? '' : $data['selected_font'];
			$this->selected_import_files   = empty( $data['selected_import_files'] ) ? array() : $data['selected_import_files'];
			$this->import_files            = empty( $data['import_files'] ) ? array() : $data['import_files'];
			$this->before_import_executed  = empty( $data['before_import_executed'] ) ? false : $data['before_import_executed'];
			$this->importer->set_importer_data( $data );

			return true;
		
	}


	/**
	 * AJAX callback to install a plugin.
	 */
	public function initial_install_ajax_callback() {
		//Helpers::verify_ajax_call();

		if ( ! isset( $_POST['selected'] ) || ! isset( $_POST['builder'] ) ) {
			wp_send_json_error( 'Missing Information' );
		}
		// Get selected file index or set it to 0.
		$selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		$selected_builder = empty( $_POST['builder'] ) ? '' : sanitize_text_field( $_POST['builder'] );
		if ( empty( $selected_index ) || empty( $selected_builder ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		delete_transient( 'templify_importer_data' );
		
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importer_files( $selected_index, $selected_builder );
		
		if ( ! isset( $this->import_files[ $selected_index ] ) ) {
			wp_send_json_error( 'Missing Template' );
		}
		wp_send_json( array( 'status' => 'initialSuccess' ) );
	}

	/**
	 * AJAX callback to install a plugin.
	 */
	public function install_plugins_ajax_callback() {
		//

		if ( ! isset( $_POST['selected'] ) || ! isset( $_POST['builder'] ) ) {
			wp_send_json_error( 'Missing Information' );
		}
		// Get selected file index or set it to 0.
		$selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		$selected_builder = empty( $_POST['builder'] ) ? '' : sanitize_text_field( $_POST['builder'] );
		if ( empty( $selected_index ) || empty( $selected_builder ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		delete_transient( 'templify_importer_data' );
		
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importer_files( $selected_index, $selected_builder );
		
		if ( ! isset( $this->import_files[ $selected_index ] ) ) {
			wp_send_json_error( 'Missing Template' );
		}
		$info = $this->import_files[ $selected_index ];
		$install = true;
		if ( isset( $info['plugins'] ) && ! empty( $info['plugins'] ) ) {

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			}
			if ( ! class_exists( 'WP_Upgrader' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			}
			$importer_plugins = array ();
			foreach( $info['plugins'] as $plugin ) {
				$path = false;
				if ( strpos( $plugin, '/' ) !== false ) {
					$path = $plugin;
					$arr  = explode( '/', $plugin, 2 );
					$base = $arr[0];
					if ( isset( $importer_plugins[ $base ] ) && isset( $importer_plugins[ $base ]['src'] ) ) {
						$src = $importer_plugins[ $base ]['src'];
					} else {
						$src = 'unknown';
					}
				} elseif ( isset( $importer_plugins[ $plugin ] ) ) {
					$path = $importer_plugins[ $plugin ]['path'];
					$base = $importer_plugins[ $plugin ]['base'];
					$src  = $importer_plugins[ $plugin ]['src'];
				}
				if ( $path ) {

					$state = Plugin_Check::active_check( $path );
					if ( 'woocommerce' === $base && empty( get_option( 'woocommerce_db_version' ) ) ) {
						update_option( 'woocommerce_db_version', '4.0' );
					}
					if ( 'woocommerce' === $base && ( 'notactive' === $state || 'installed' === $state ) ) {
						$this->ss = true;
					}
					if ( 'unknown' === $src ) {
						$check_api = plugins_api(
							'plugin_information',
							array(
								'slug' => $base,
								'fields' => array(
									'short_description' => false,
									'sections' => false,
									'requires' => false,
									'rating' => false,
									'ratings' => false,
									'downloaded' => false,
									'last_updated' => false,
									'added' => false,
									'tags' => false,
									'compatibility' => false,
									'homepage' => false,
									'donate_link' => false,
								),
							)
						);
						if ( ! is_wp_error( $check_api ) ) {
							$src = 'repo';
						}
					}
					if ( 'notactive' === $state && 'repo' === $src ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							wp_send_json_error( 'Permissions Issue' );
						}
						$api = plugins_api(
							'plugin_information',
							array(
								'slug' => $base,
								'fields' => array(
									'short_description' => false,
									'sections' => false,
									'requires' => false,
									'rating' => false,
									'ratings' => false,
									'downloaded' => false,
									'last_updated' => false,
									'added' => false,
									'tags' => false,
									'compatibility' => false,
									'homepage' => false,
									'donate_link' => false,
								),
							)
						);
						if ( ! is_wp_error( $api ) ) {

							// Use AJAX upgrader skin instead of plugin installer skin.
							// ref: function wp_ajax_install_plugin().
							$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );

							$installed = $upgrader->install( $api->download_link );
							if ( $installed ) {
								$silent = ( 'give' === $base || 'elementor' === $base || 'fluentform' === $base || 'restrict-content' === $base ? false : true );
								
									add_option( 'give_install_pages_created', 1, '', false );
								
								if ( 'restrict-content' === $base ) {
									update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
								}
								$activate = activate_plugin( $path, '', false, $silent );
								if ( is_wp_error( $activate ) ) {
									$install = false;
								}
							} else {
								$install = false;
							}
						} else {
							$install = false;
						}
					} elseif ( 'installed' === $state ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							wp_send_json_error( 'Permissions Issue' );
						}
						$silent = false; 
						//$silent = ( 'give' === $base || 'elementor' === $base ? false : true );
						if ( 'give' === $base ) {
							// Make sure give doesn't add it's pages, prevents having two sets.
							update_option( 'give_install_pages_created', 1, '', false );
						}
						if ( 'restrict-content' === $base ) {
							$silent = true;
							update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
						}
						$activate = activate_plugin( $path, '', false, $silent );
						if ( is_wp_error( $activate ) ) {
							$install = false;
						}
					}
					
					
				}
			}
		}
		if ( false === $install ) {
			wp_send_json_error();
		} else {
			wp_send_json( array( 'status' => 'pluginSuccess' ) );
		}
	}



	/**
	 * Get data from filters, after the theme has loaded and instantiate the importer.
	 */
	public function setup_plugin_with_filter_data() {
		if ( ! ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) ) {
			return;
		}
		// Get info of import data files and filter it.
		//$this->import_files = apply_filters( 'templify-importer-templates/import_files', array() );
		//$this->import_files = '';
		$this->import_files = Helpers::validate_import_file_info( array()  );
		/**
		 * Register all default actions (before content import, widget, customizer import and other actions)
		 * to the 'before_content_import_execution' and the 'templify-importer-templates/after_content_import_execution' action hook.
		  */
		// $import_actions = new ImportActions();
		// $import_actions->register_hooks();

		// // Importer options array.
		// $importer_options =  array(
		// 	'fetch_attachments' => true,
		// 	'aggressive_url_search' => true,
		// ) ;

		// // Logger options for the logger used in the importer.
		// $logger_options =  array(
		// 	'logger_min_level' => 'warning',
		// ) ;

		// // Configure logger instance and set it to the importer.
		// $logger            = new Logger();
		// $logger->min_level = $logger_options['logger_min_level'];

		// // Create importer instance with proper parameters.
		// $this->importer = new Importer( $importer_options, $logger );
	}



		/**
	 * Send a JSON response with final report.
	 */
	private function final_response( $extra = '' ) {
		// Delete importer data transient for current import.
		delete_transient( 'templify_importer_data' );

		// Display final messages (success or error messages).
		if ( empty( $this->frontend_error_messages ) && ! empty( $extra ) ) {
			$response['message'] = '';

			$response['message'] .= sprintf(
				__( '%1$sFinished! View your page%2$s', 'templify-importer-templates' ),
				'<div class="finshed-notice-success"><p><a href="' . esc_url( get_permalink( $extra ) ) . '" class="button-primary button templify-importer-templates-finish-button">',
				'</a></p></div>'
			);
		} elseif ( empty( $this->frontend_error_messages ) ) {
			$response['message'] = '';

			$response['message'] .= sprintf(
				__( '%1$sFinished! View your site%2$s', 'templify-importer-templates' ),
				'<div class="finshed-notice-success"><p><a href="' . esc_url( home_url( '/' ) ) . '" class="button-primary button templify-importer-templates-finish-button">',
				'</a></p></div>'
			);
			
		} else {
			$response['message'] = $this->frontend_error_messages_display() . '<br>';
	
				$response['message'] .= sprintf(
					__( '%1$sThe demo import has finished, but there were some import errors.%2$sMore details about the errors can be found in this %3$s%5$slog file%6$s%4$s%7$s', 'templify-importer-templates' ),
					'<div class="notice  notice-warning"><p>',
					'<br>',
					'<strong>',
					'</strong>',
					'<a href="' . Helpers::get_log_url( $this->log_file_path ) .'" target="_blank">',
					'</a>',
					'</p></div>'
				);
			  
				$response['message'] .= sprintf(
					__( '%1$sThe demo import has finished, but there were some import errors.%2$sPlease check your php error logs if site is incomplete.%3$s', 'templify-importer-templates' ),
					'<div class="notice  notice-warning"><p>',
					'<br>',
					'</p></div>'
				);
			
		}

		wp_send_json( $response );
	}


	/**
	 * AJAX callback for importing the customizer data.
	 * This request has the wp_customize set to 'on', so that the customizer hooks can be called
	 * (they can only be called with the $wp_customize instance). But if the $wp_customize is defined,
	 * then the widgets do not import correctly, that's why the customizer import has its own AJAX call.
	 */
	public function import_customizer_data_ajax_callback() {
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
	//	Helpers::verify_ajax_call();
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			//Helpers::set_demo_import_start_time();

			
				// Define log file path.
				$this->log_file_path = Helpers::get_log_path();
			

			// Get selected file index or set it to 0.
			$this->selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
			$this->selected_palette = empty( $_POST['palette'] ) ? '' : sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = empty( $_POST['font'] ) ? '' : sanitize_text_field( $_POST['font'] );
			$this->selected_builder = empty( $_POST['builder'] ) ? 'blocks' : sanitize_text_field( $_POST['builder'] );

			
			
				$template_database  = Template_Database_Importer::get_instance();
				$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-importer-templates' ) );
			}
			/**
			 * 1). Prepare import files.
			 * Predefined import files via filter: templify-importer-templates/import_files
			 */
			if ( ! empty( $this->import_files[ $this->selected_index ] ) ) { // Use predefined import files from wp filter: templify-importer-templates/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_files( $this->import_files[ $this->selected_index ] );
				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'templify-importer-templates' )
					);
				}
			
					// Add this message to log file.
					$log_added = Helpers::append_to_file(
						sprintf(
							__( 'The import files for: %s were successfully downloaded!', 'templify-importer-templates' ),
							$this->import_files[ $this->selected_index ]['slug']
						) . Helpers::import_file_info( $this->selected_import_files ),
						$this->log_file_path,
						esc_html__( 'Downloaded files' , 'templify-importer-templates' )
					);
				
			} else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-importer-templates' ) );
			}
			// If elementor make sure the defaults are off.
			if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
				update_option( 'elementor_disable_color_schemes', 'yes' );
				update_option( 'elementor_disable_typography_schemes', 'yes' );
			}
			// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
			Helpers::set_import_data_transient( $this->get_current_importer_data() );
			if ( ! $this->before_import_executed ) {
				$this->before_import_executed = true;
	
				/**
				 * Save Current Theme mods for a potential undo.
				 */
				update_option( '_templify_import_templates_old_customizer', get_option( 'theme_mods_' . get_option( 'stylesheet' ) ) );
				// Save Import data for use if we need to reset it.
				update_option( '_templify_import_templates_last_import_data', $this->import_files[ $this->selected_index ], 'no' );
				// Reset to default settings values.
				delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );
				// Reset Global Palette
				if ( get_option( 'templify_global_palette' ) !== false ) {
					// The option already exists, so update it.
					update_option( 'templify_global_palette', '{"palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"second-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"third-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"active":"palette"}' );
				}
			}
		}
		
	
		/**
		 * Execute the customizer import actions.
		 *
		 * Default actions:
		 * 1 - Customizer import (with priority 10).
		 */
		do_action( 'templify-importer-templates/customizer_import_execution', $this->selected_import_files );

		// Request the after all import AJAX call.
		if ( false !== has_action( 'templify-importer-templates/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}


	/**
	 * Get the current state of selected data.
	 *
	 * @return array
	 */
	public function get_current_importer_data() {
		return array(
			'frontend_error_messages' => $this->frontend_error_messages,
			'log_file_path'           => $this->log_file_path,
			'selected_index'          => $this->selected_index,
			'selected_palette'        => $this->selected_palette,
			'selected_font'           => $this->selected_font,
			'selected_import_files'   => $this->selected_import_files,
			'import_files'            => $this->import_files,
			'before_import_executed'  => $this->before_import_executed,
		);
	}

	
	/**
	 * AJAX callback for the after all import action.
	 */
	public function after_all_import_data_ajax_callback() {
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		//Helpers::verify_ajax_call();

		// Get existing import data.
		if ( $this->use_existing_importer_data() ) {
			/**
			 * Execute the after all import actions.
			 *
			 * Default actions:
			 * 1 - after_import action (with priority 10).
			 */
			do_action( 'templify-importer-templates/after_all_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index, $this->selected_palette, $this->selected_font );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}



	/**
	 * AJAX callback to remove past content..
	 */
	public function remove_past_data_ajax_callback() {
	//	Helpers::verify_ajax_call();

		if ( ! current_user_can( 'customize' ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		// Prevents elementor from pushing out an confrimation and breaking the import.
		$_GET['force_delete_kit'] = true;
		$removed_content = true;

		$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_templify_import_templates_imported_post'" );
		$term_ids = $wpdb->get_col( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key='_templify_import_templates_imported_term'" );
		if ( isset( $post_ids ) && is_array( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				$worked = wp_delete_post( $post_id, true );
				if ( false === $worked ) {
					$removed_content = false;
				}
			}
		}
		if ( isset( $term_ids ) && is_array( $term_ids ) ) {
			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) ) {
					wp_delete_term( $term_id, $term->taxonomy );
				}
			}
		}

		if ( false === $removed_content ) {
			wp_send_json_error();
		} else {
			wp_send_json( array( 'status' => 'removeSuccess' ) );
		}
	}


	


	/**
	 * AJAX callback to install a plugin.
	 */
	public function check_plugin_data_ajax_callback() {
		//Helpers::verify_ajax_call();
		if ( ! isset( $_POST['selected'] ) || ! isset( $_POST['builder'] ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		$selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		$selected_builder = empty( $_POST['builder'] ) ? '' : sanitize_text_field( $_POST['builder'] );
		if ( empty( $selected_index ) || empty( $selected_builder ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importter_files( $selected_index, $selected_builder );
		
		if ( ! isset( $this->import_files[ $selected_index ] ) ) {
			wp_send_json_error( 'Missing Template' );
		}
		$info = $this->import_files[ $selected_index ];
	
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			}
			$importer_plugins = array (
				'woocommerce' => array(
					'title' => 'Woocommerce',
					'base'  => 'woocommerce',
					'slug'  => 'woocommerce',
					'path'  => 'woocommerce/woocommerce.php',
					'src'   => 'repo',
				),
				'elementor' => array(
					'title' => 'Elementor',
					'base'  => 'elementor',
					'slug'  => 'elementor',
					'path'  => 'elementor/elementor.php',
					'src'   => 'repo',
				),
				'kadence-blocks' => array(
					'title' => 'Kadence Blocks',
					'base'  => 'kadence-blocks',
					'slug'  => 'kadence-blocks',
					'path'  => 'kadence-blocks/kadence-blocks.php',
					'src'   => 'repo',
				),
				'kadence-blocks-pro' => array(
					'title' => 'Kadence Block Pro',
					'base'  => 'kadence-blocks-pro',
					'slug'  => 'kadence-blocks-pro',
					'path'  => 'kadence-blocks-pro/kadence-blocks-pro.php',
					'src'   => 'bundle',
				),
				'kadence-pro' => array(
					'title' => 'Kadence Pro',
					'base'  => 'kadence-pro',
					'slug'  => 'kadence-pro',
					'path'  => 'kadence-pro/kadence-pro.php',
					'src'   => 'bundle',
				),
				'fluentform' => array(
					'title' => 'Fluent Forms',
					'src'   => 'repo',
					'base'  => 'fluentform',
					'slug'  => 'fluentform',
					'path'  => 'fluentform/fluentform.php',
				),
				'wpzoom-recipe-card' => array(
					'title' => 'Recipe Card Blocks by WPZOOM',
					'base'  => 'recipe-card-blocks-by-wpzoom',
					'slug'  => 'wpzoom-recipe-card',
					'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
					'src'   => 'repo',
				),
				'recipe-card-blocks-by-wpzoom' => array(
					'title' => 'Recipe Card Blocks by WPZOOM',
					'base'  => 'recipe-card-blocks-by-wpzoom',
					'slug'  => 'wpzoom-recipe-card',
					'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
					'src'   => 'repo',
				),
				'learndash' => array(
					'title' => 'LearnDash',
					'base'  => 'sfwd-lms',
					'slug'  => 'sfwd_lms',
					'path'  => 'sfwd-lms/sfwd_lms.php',
					'src'   => 'thirdparty',
				),
				'learndash-course-grid' => array(
					'title' => 'LearnDash Course Grid Addon',
					'base'  => 'learndash-course-grid',
					'slug'  => 'learndash_course_grid',
					'path'  => 'learndash-course-grid/learndash_course_grid.php',
					'src'   => 'thirdparty',
				),
				'sfwd-lms' => array(
					'title' => 'LearnDash',
					'base'  => 'sfwd-lms',
					'slug'  => 'sfwd_lms',
					'path'  => 'sfwd-lms/sfwd_lms.php',
					'src'   => 'thirdparty',
				),
				'lifterlms' => array(
					'title' => 'LifterLMS',
					'base'  => 'lifterlms',
					'slug'  => 'lifterlms',
					'path'  => 'lifterlms/lifterlms.php',
					'src'   => 'repo',
				),
				'tutor' => array(
					'title' => 'Tutor LMS',
					'base'  => 'tutor',
					'slug'  => 'tutor',
					'path'  => 'tutor/tutor.php',
					'src'   => 'repo',
				),
				'give' => array(
					'title' => 'GiveWP',
					'base'  => 'give',
					'slug'  => 'give',
					'path'  => 'give/give.php',
					'src'   => 'repo',
				),
				'the-events-calendar' => array(
					'title' => 'The Events Calendar',
					'base'  => 'the-events-calendar',
					'slug'  => 'the-events-calendar',
					'path'  => 'the-events-calendar/the-events-calendar.php',
					'src'   => 'repo',
				),
				'event-tickets' => array(
					'title' => 'Event Tickets',
					'base'  => 'event-tickets',
					'slug'  => 'event-tickets',
					'path'  => 'event-tickets/event-tickets.php',
					'src'   => 'repo',
				),
				'orderable' => array(
					'title' => 'Orderable',
					'base'  => 'orderable',
					'slug'  => 'orderable',
					'path'  => 'orderable/orderable.php',
					'src'   => 'repo',
				),
				'restrict-content' => array(
					'title' => 'Restrict Content',
					'base'  => 'restrict-content',
					'slug'  => 'restrictcontent',
					'path'  => 'restrict-content/restrictcontent.php',
					'src'   => 'repo',
				),
				'kadence-woo-extras' => array(
					'title' => 'Kadence Shop Kit',
					'base'  => 'kadence-woo-extras',
					'slug'  => 'kadence-woo-extras',
					'path'  => 'kadence-woo-extras/kadence-woo-extras.php',
					'src'   => 'bundle',
				),
				'depicter' => array(
					'title' => 'Depicter Slider',
					'base'  => 'depicter',
					'slug'  => 'depicter',
					'path'  => 'depicter/depicter.php',
					'src'   => 'repo',
				),
				'seriously-simple-podcasting' => array(
					'title' => 'Seriously Simple Podcasting',
					'base'  => 'seriously-simple-podcasting',
					'slug'  => 'seriously-simple-podcasting',
					'path'  => 'seriously-simple-podcasting/seriously-simple-podcasting.php',
					'src'   => 'repo',
				),
			);
			$plugin_information = array();
			foreach( $info['plugins'] as $plugin ) {
				$path = false;
				if ( strpos( $plugin, '/' ) !== false ) {
					$path = $plugin;
					$arr  = explode( '/', $plugin, 2 );
					$base = $arr[0];
					if ( isset( $importer_plugins[ $base ] ) && isset( $importer_plugins[ $base ]['src'] ) ) {
						$src = $importer_plugins[ $base ]['src'];
					} else {
						$src = 'unknown';
					}
					if ( isset( $importer_plugins[ $base ] ) && isset( $importer_plugins[ $base ]['title'] ) ) {
						$title = $importer_plugins[ $base ]['title'];
					} else {
						$title = $base;
					}
				} elseif ( isset( $importer_plugins[ $plugin ] ) ) {
					$path   = $importer_plugins[ $plugin ]['path'];
					$base   = $importer_plugins[ $plugin ]['base'];
					$src    = $importer_plugins[ $plugin ]['src'];
					$title  = $importer_plugins[ $plugin ]['title'];
				}
				if ( $path ) {
					$state = Plugin_Check::active_check( $path );
					if ( 'unknown' === $src ) {
						$check_api = plugins_api(
							'plugin_information',
							array(
								'slug' => $base,
								'fields' => array(
									'short_description' => false,
									'sections' => false,
									'requires' => false,
									'rating' => false,
									'ratings' => false,
									'downloaded' => false,
									'last_updated' => false,
									'added' => false,
									'tags' => false,
									'compatibility' => false,
									'homepage' => false,
									'donate_link' => false,
								),
							)
						);


						
						if ( ! is_wp_error( $check_api ) ) {
							$title = $check_api->name;
							$src   = 'repo';
						}
					}
					$plugin_information[ $plugin ] = array(
						'state' => $state,
						'src'   => $src,
						'title' => $title,
					);
				} else {
					$plugin_information[ $plugin ] = array(
						'state' => 'unknown',
						'src'   => 'unknown',
						'title' => $plugin,
					);
				}
			}
			wp_send_json( $plugin_information );
	
		
	}


	/**
	 * Run check to see if we need to dismiss the notice.
	 * If all tests are successful then call the dismiss_notice() method.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function ajax_dismiss_starter_notice() {

		// Sanity check: Early exit if we're not on a wptrt_dismiss_notice action.
		if ( ! isset( $_POST['action'] ) || 'templify_importer_dismiss_notice' !== $_POST['action'] ) {
			return;
		}
		// Security check: Make sure nonce is OK.
		check_ajax_referer( 'templify-import-ajax-verification', 'security', true );

		// If we got this far, we need to dismiss the notice.
		update_option( 'templify_import_templates_dismiss_upsell', true, false );
	}

}

Importer_Templates::get_instance();

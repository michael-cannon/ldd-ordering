<?php
/**
 * Copyright 2014 Michael Cannon (email: mc@aihr.us)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

require_once LDD_ORDERING_DIR_LIB . 'aihrus-framework/class-aihrus-common.php';
require_once LDD_ORDERING_DIR_INC . 'class-ldd-ordering-settings.php';
require_once LDD_ORDERING_DIR_INC . 'class-ldd-ordering-widget.php';

if ( class_exists( 'LDD_Ordering' ) )
	return;


class LDD_Ordering extends Aihrus_Common {
	const BASE    = LDD_ORDERING_BASE;
	const ID      = 'ldd-ordering';
	const SLUG    = 'ldd_ordering_';
	const VERSION = LDD_ORDERING_VERSION;

	const PT = 'ldd-ordering';

	public static $class        = __CLASS__;
	public static $cpt_category = '';
	public static $cpt_tags     = '';
	public static $menu_id;
	public static $notice_key;
	public static $plugin_assets;
	public static $scripts = array();
	public static $settings_link;
	public static $styles        = array();
	public static $styles_called = false;

	public static $post_id;


	public function __construct() {
		parent::__construct();

		self::$plugin_assets = plugins_url( '/assets/', dirname( __FILE__ ) );
		self::$plugin_assets = self::strip_protocol( self::$plugin_assets );

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		// fixme add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );
		// fixme add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ) );
		add_shortcode( 'ldd_ordering_shortcode', array( __CLASS__, 'ldd_ordering_shortcode' ) );
	}


	public static function admin_init() {
		self::support_thumbnails();
		self::update();

		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );

		self::$settings_link = '<a href="' . get_admin_url() . 'edit.php?post_type=' . self::PT . '&page=' . LDD_Ordering_Settings::ID . '">' . __( 'Settings', 'ldd-ordering' ) . '</a>';
	}


	public static function admin_menu() {
		self::$menu_id = add_management_page( esc_html__( 'Legal Document Deliveries - Ordering Processor', 'ldd-ordering' ), esc_html__( 'Legal Document Deliveries - Ordering Processor', 'ldd-ordering' ), 'manage_options', self::ID, array( __CLASS__, 'user_interface' ) );

		add_action( 'admin_print_scripts-' . self::$menu_id, array( __CLASS__, 'scripts' ) );
		add_action( 'admin_print_styles-' . self::$menu_id, array( __CLASS__, 'styles' ) );
	}


	public static function init() {
		load_plugin_textdomain( self::ID, false, 'ldd-ordering/languages' );

		add_action( 'wp_ajax_ajax_process_post', array( __CLASS__, 'ajax_process_post' ) );

		self::$cpt_category = self::PT . '-category';
		self::$cpt_tags     = self::PT . '-post_tag';

		self::init_post_type();
		self::styles();
	}


	public static function plugin_action_links( $links, $file ) {
		if ( self::BASE == $file ) {
			array_unshift( $links, self::$settings_link );

			// fixme $link = '<a href="' . get_admin_url() . 'tools.php?page=' . self::ID . '">' . esc_html__( 'Process', 'ldd-ordering' ) . '</a>';
			// fixme array_unshift( $links, $link );
		}

		return $links;
	}


	public static function activation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	public static function deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		global $wpdb;
		
		require_once LDD_ORDERING_DIR_INC . 'class-ldd-ordering-settings.php';

		$delete_data = ldd_ordering_get_option( 'delete_data', false );
		if ( $delete_data ) {
			delete_option( LDD_Ordering_Settings::ID );
			$wpdb->query( 'OPTIMIZE TABLE `' . $wpdb->options . '`' );
		}
	}


	public static function plugin_row_meta( $input, $file ) {
		if ( self::BASE != $file )
			return $input;

		$disable_donate = ldd_ordering_get_option( 'disable_donate', true );
		if ( $disable_donate )
			return $input;

		$links = array(
			self::$donate_link,
		);

		$input = array_merge( $input, $links );

		return $input;
	}


	public static function notice_0_0_1() {
		$text = sprintf( __( 'If your Legal Document Deliveries - Ordering display has gone to funky town, please <a href="%s">read the FAQ</a> about possible CSS fixes.', 'ldd-ordering' ), 'https://aihrus.zendesk.com/entries/23722573-Major-Changes-Since-2-10-0' );

		aihr_notice_updated( $text );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function notice_donate( $disable_donate = null, $item_name = null ) {
		$disable_donate = ldd_ordering_get_option( 'disable_donate', true );

		parent::notice_donate( $disable_donate, LDD_ORDERING_NAME );
	}


	public static function update() {
		$prior_version = ldd_ordering_get_option( 'admin_notices' );
		if ( $prior_version ) {
			if ( $prior_version < '0.0.1' )
				add_action( 'admin_notices', array( __CLASS__, 'notice_0_0_1' ) );

			if ( $prior_version < self::VERSION )
				do_action( 'ldd_ordering_update' );

			ldd_ordering_set_option( 'admin_notices' );
		}

		// display donate on major/minor version release
		$donate_version = ldd_ordering_get_option( 'donate_version', false );
		if ( ! $donate_version || ( $donate_version != self::VERSION && preg_match( '#\.0$#', self::VERSION ) ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_donate' ) );
			ldd_ordering_set_option( 'donate_version', self::VERSION );
		}
	}


	public static function scripts( $atts = array() ) {
		if ( is_admin() ) {
			wp_enqueue_script( 'jquery' );

			// fixme wp_register_script( 'jquery-ui-progressbar', self::$plugin_assets . 'js/jquery.ui.progressbar.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-widget' ), '1.10.3' );
			// fixme wp_enqueue_script( 'jquery-ui-progressbar' );

			add_action( 'admin_footer', array( 'LDD_Ordering', 'get_scripts' ) );
		} else {
			add_action( 'wp_footer', array( 'LDD_Ordering', 'get_scripts' ) );
		}

		do_action( 'ldd_ordering_scripts', $atts );
	}


	public static function styles() {
		if ( is_admin() ) {
			// fixme wp_register_style( 'jquery-ui-progressbar', self::$plugin_assets . 'css/redmond/jquery-ui-1.10.3.custom.min.css', false, '1.10.3' );
			// fixme wp_enqueue_style( 'jquery-ui-progressbar' );

			add_action( 'admin_footer', array( 'LDD_Ordering', 'get_styles' ) );
		} else {
			wp_register_style( __CLASS__, self::$plugin_assets . 'css/ldd-ordering.css' );
			wp_enqueue_style( __CLASS__ );

			add_action( 'wp_footer', array( 'LDD_Ordering', 'get_styles' ) );
		}

		do_action( 'ldd_ordering_styles' );
	}


	public static function ldd_ordering_shortcode( $atts ) {
		self::call_scripts_styles( $atts );

		return __CLASS__ . ' shortcode';
	}


	public static function version_check() {
		$good_version = true;

		return $good_version;
	}


	public static function call_scripts_styles( $atts ) {
		self::scripts( $atts );
	}


	public static function get_scripts() {
		if ( empty( self::$scripts ) )
			return;

		foreach ( self::$scripts as $script )
			echo $script;
	}


	public static function get_styles() {
		if ( empty( self::$styles ) )
			return;

		if ( empty( self::$styles_called ) ) {
			echo '<style>';

			foreach ( self::$styles as $style )
				echo $style;

			echo '</style>';

			self::$styles_called = true;
		}
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function do_load() {
		$do_load = false;
		if ( ! empty( $GLOBALS['pagenow'] ) && in_array( $GLOBALS['pagenow'], array( 'edit.php', 'options.php', 'plugins.php' ) ) ) {
			$do_load = true;
		} elseif ( ! empty( $_REQUEST['page'] ) && LDD_Ordering_Settings::ID == $_REQUEST['page'] ) {
			$do_load = true;
		} elseif ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$do_load = true;
		}

		return $do_load;
	}


	public static function widgets_init() {
		register_widget( 'LDD_Ordering_Widget' );
	}


	public static function get_defaults( $single_view = false ) {
		if ( empty( $single_view ) )
			return apply_filters( 'ldd_ordering_defaults', ldd_ordering_get_options() );
		else
			return apply_filters( 'ldd_ordering_defaults_single', ldd_ordering_get_options() );
	}


	public static function init_post_type() {
		$labels = array(
			'add_new' => esc_html__( 'Add New' ),
			'add_new_item' => esc_html__( 'Add New Delivery' ),
			'edit_item' => esc_html__( 'Edit Delivery' ),
			'name' => esc_html__( 'Deliveries' ),
			'new_item' => esc_html__( 'Add New Delivery' ),
			'not_found' => esc_html__( 'No deliveries found' ),
			'not_found_in_trash' => esc_html__( 'No deliveries found in Trash' ),
			'parent_item_colon' => null,
			'search_items' => esc_html__( 'Search Deliveries' ),
			'singular_name' => esc_html__( 'Delivery' ),
			'view_item' => esc_html__( 'View Delivery' ),
		);

		$supports = array(
			'title',
			'editor',
			'thumbnail',
			'comments',
			'publicize',
		);

		$taxonomies = array(
			self::$cpt_category,
			self::$cpt_tags,
		);

		self::register_taxonomies();

		$args = array(
			'label' => esc_html__( 'LDD Ordering' ),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'query_var' => true,
			'rewrite' => array(
				'slug' => 'delivery',
				'with_front' => false,
			),
			'supports' => $supports,
			'taxonomies' => $taxonomies,
		);

		register_post_type( self::PT, $args );

		register_taxonomy_for_object_type( self::$cpt_category, self::PT );
		register_taxonomy_for_object_type( self::$cpt_tags, self::PT );
	}


	public static function register_taxonomies() {
		$args = array(
			'hierarchical' => true,
			'show_admin_column' => true,
		);
		register_taxonomy( self::$cpt_category, self::PT, $args );

		$args = array(
			'show_admin_column' => true,
			'update_count_callback' => '_update_post_term_count',
		);
		register_taxonomy( self::$cpt_tags, self::PT, $args );
	}


	public static function support_thumbnails() {
		$feature       = 'post-thumbnails';
		$feature_level = get_theme_support( $feature );

		if ( true === $feature_level ) {
			// already enabled for all post types
			return;
		} elseif ( false === $feature_level ) {
			// none allowed, only enable for our own
			add_theme_support( $feature, array( self::PT ) );
		} else {
			// add our own to list of supported
			$feature_level[0][] = self::PT;
			add_theme_support( $feature, $feature_level[0] );
		}
	}


}

?>

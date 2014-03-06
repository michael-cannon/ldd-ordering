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

require_once LDD_ORDERING_DIR_LIB . 'class-redrokk-metabox-class.php';

if ( class_exists( 'LDD_Ordering' ) )
	return;


class LDD_Ordering extends Aihrus_Common {
	const BASE    = LDD_ORDERING_BASE;
	const ID      = 'ldd-ordering';
	const SLUG    = 'ldd_ordering_';
	const VERSION = LDD_ORDERING_VERSION;

	const KEY_DELIVERY_ID = '_ldd_delivery_id';
	const KEY_PAYMENT_ID  = '_ldd_payment_id';
	const KEY_PAGE_COUNT  = 'ldd_page_count';

	public static $class = __CLASS__;
	public static $menu_id;
	public static $notice_key;
	public static $plugin_assets;
	public static $scripts = array();
	public static $settings_link;
	public static $styles        = array();
	public static $styles_called = false;


	public function __construct() {
		parent::__construct();

		self::$plugin_assets = plugins_url( '/assets/', dirname( __FILE__ ) );
		self::$plugin_assets = self::strip_protocol( self::$plugin_assets );

		self::actions();
		self::filters();

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		// fixme add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_shortcode( 'ldd_ordering_shortcode', array( __CLASS__, 'ldd_ordering_shortcode' ) );
	}


	public static function admin_init() {
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );

		self::$settings_link = '<a href="' . get_admin_url() . 'edit.php?post_type=' . LDD::PT . '&page=' . LDD_Settings::ID . '">' . __( 'Settings', 'ldd-ordering' ) . '</a>';

		self::add_delivery_meta_box();
		self::add_poc_meta_box();
	}


	public static function admin_menu() {
		self::$menu_id = add_management_page( esc_html__( 'Legal Document Deliveries - Ordering Processor', 'ldd-ordering' ), esc_html__( 'Legal Document Deliveries - Ordering Processor', 'ldd-ordering' ), 'manage_options', self::ID, array( __CLASS__, 'user_interface' ) );

		add_action( 'admin_print_scripts-' . self::$menu_id, array( __CLASS__, 'scripts' ) );
		add_action( 'admin_print_styles-' . self::$menu_id, array( __CLASS__, 'styles' ) );
	}


	public static function init() {
		load_plugin_textdomain( self::ID, false, 'ldd-ordering/languages' );

		if ( LDD::do_load() ) {
			self::styles();
		}
	}


	public static function actions() {
		add_action( 'edd_update_payment_status', array( __CLASS__, 'edd_update_payment_status' ), 10, 3 );
	}


	public static function filters() {
		add_filter( 'edd_email_template_tags', array( __CLASS__, 'edd_email_template_tags' ), 10, 3 );
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

		$delete_data = ldd_get_option( 'delete_data', false );
		if ( $delete_data ) {
			delete_option( LDD_Settings::ID );
			$wpdb->query( 'OPTIMIZE TABLE `' . $wpdb->options . '`' );
		}
	}


	public static function plugin_row_meta( $input, $file ) {
		if ( self::BASE != $file )
			return $input;

		$disable_donate = ldd_get_option( 'disable_donate', true );
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


	public static function edd_complete_purchase( $payment_id ) {
		$title = esc_html__( 'Delivery record for Payment #%1$s' );
		$title = sprintf( $title, $payment_id );

		// create new delivery record
		$delivery_data = array(
			'post_type' => LDD::PT,
			'post_author' => null,
			'post_status' => 'pending',
			'post_title' => $title,
		);

		$delivery_id = wp_insert_post( $delivery_data, true );

		// relate delivery record to order payment and vice versa
		add_post_meta( $payment_id, self::KEY_DELIVERY_ID, $delivery_id );
		add_post_meta( $delivery_id, self::KEY_PAYMENT_ID, $payment_id );

		// carry over delivery details
		$fields = cfm_get_checkout_fields( $payment_id );
		foreach ( $fields as $key => $value ) {
			add_post_meta( $delivery_id, $key, $value );
		}

		if ( ! empty( $fields['court_filings'] ) ) {
			$page_count = 0;

			$docs = $fields['court_filings'];
			$docs = is_array( $docs ) ? $docs : array( $docs );
			foreach ( $docs as $key => $doc_id ) {
				$file = get_attached_file( $doc_id );

				// pull files over
				self::add_media( $delivery_id, $file, null, false );

				$page_count += self::getNumPagesPdf( $file );
			}

			// set page count
			add_post_meta( $delivery_id, self::KEY_PAGE_COUNT, $page_count );
		}

		// add point of contact details
		$name = edd_email_tag_fullname( $payment_id );
		add_post_meta( $delivery_id, 'name', $name );

		$email = edd_get_payment_user_email( $payment_id );
		add_post_meta( $delivery_id, 'email', $email );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function edd_update_payment_status( $payment_id, $new_status, $old_status ) {
		if ( 'publish' == $new_status )
			self::edd_complete_purchase( $payment_id );
	}


	/**
	 *
	 *
	 * @ref http://stackoverflow.com/questions/1143841/count-the-number-of-pages-in-a-pdf-in-only-php
	 */
	public static function getNumPagesPdf( $filepath ) {
		$fp  = fopen( preg_replace( '/\[( .*? )\]/i', '', $filepath ), 'r' );
		$max = 0;
		while ( ! feof( $fp ) ) {
			$line = fgets( $fp, 255 );
			if ( preg_match( '/\/Count [0-9]+/', $line, $matches ) ) {
				preg_match( '/[0-9]+/', $matches[0], $matches2 );
				if ( $max < $matches2[0] )
					$max = $matches2[0];
			}
		}

		fclose( $fp );

		if ( $max == 0 ) {
			$im  = new Imagick( $filepath );
			$max = $im->getNumberImages();
		}

		return $max;
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public static function add_delivery_meta_box() {
		$fields = array(
			array(
				'name' => esc_html__( 'Page Count' ),
				'id' => self::KEY_PAGE_COUNT,
				'type' => 'text',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Delivery County' ),
				'id' => 'delivery_county',
				'type' => 'text',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Delivery Court' ),
				'id' => 'delivery_court',
				'type' => 'text',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Courthouse Address' ),
				'id' => 'courthouse_address',
				'type' => 'textarea',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Court Filings' ),
				'id' => 'court_filings',
				'type' => 'text',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Special Instructions' ),
				'id' => 'special_instructions',
				'type' => 'textarea',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Shared Notifications' ),
				'id' => 'shared_notifications',
				'type' => 'textarea',
				'desc' => '',
			),
		);

		$fields = apply_filters( 'ldd_ordering_delivery_meta_box', $fields );

		$meta_box = redrokk_metabox_class::getInstance(
			self::ID . '-delivery',
			array(
				'title' => esc_html__( 'Delivery Data' ),
				'description' => '',
				'_object_types' => LDD::PT,
				'priority' => 'high',
				'_fields' => $fields,
			)
		);
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public static function add_poc_meta_box() {
		$fields = array(
			array(
				'name' => esc_html__( 'Company' ),
				'id' => 'company',
				'type' => 'text',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Name' ),
				'id' => 'name',
				'type' => 'text',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Job Title' ),
				'id' => 'job_title',
				'type' => 'text',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Telephone' ),
				'id' => 'telephone',
				'type' => 'text',
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Email' ),
				'id' => 'email',
				'type' => 'text',
				'desc' => '',
			),
		);

		$fields = apply_filters( 'ldd_ordering_poc_meta_box', $fields );

		$meta_box = redrokk_metabox_class::getInstance(
			self::ID . '-poc',
			array(
				'title' => esc_html__( 'Point of Contact' ),
				'description' => '',
				'_object_types' => LDD::PT,
				'priority' => 'high',
				'_fields' => $fields,
			)
		);
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.LongVariable)
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function edd_email_template_tags( $message, $payment_data, $payment_id ) {
		$delivery_details = self::pretty_print_delivery_details( $payment_id );

		$delivery_id = get_post_meta( $payment_id, LDD_Ordering::KEY_DELIVERY_ID, true );

		$ldd_company   = get_post_meta( $delivery_id, 'company', true );
		$ldd_telephone = get_post_meta( $delivery_id, 'telephone', true );
		$ldd_job_title = get_post_meta( $delivery_id, 'job_title', true );

		$message = str_replace( '{ldd_company}', $ldd_company, $message );
		$message = str_replace( '{ldd_delivery_details}', $delivery_details, $message );
		$message = str_replace( '{ldd_job_title}', $ldd_job_title, $message );
		$message = str_replace( '{ldd_telephone}', $ldd_telephone, $message );

		return $message;
	}


	public static function pretty_print_delivery_details( $payment_id ) {
		$fields_id = get_post_meta( $payment_id, CFM_Render_Form::$config_id, true );
		if ( empty( $fields_id ) )
			return '';

		$fields = get_post_meta( $fields_id, CFM_Render_Form::$meta_key, true );
		if ( empty( $fields ) )
			return '';

		$details     = array();
		$skip_fields = array(
			'company',
			'job_title',
			'telephone',
		);
		$text_format = esc_html__( '%1$s: %2$s' );
		foreach ( $fields as $field ) {
			if ( empty( $field['name'] ) || empty( $field['is_meta'] ) || 'yes' != $field['is_meta'] )
				continue;

			$key = $field['name'];
			if ( in_array( $key, $skip_fields ) )
				continue;

			$label = $field['label'];

			if ( empty( $field['count'] ) ) {
				$data = get_post_meta( $payment_id, $key, true );
			} else {
				$files = get_post_meta( $payment_id, $key );
				$data  = '<ul>';
				foreach ( $files as $key => $file ) {
					$data .= '<li>';
					$data .= wp_get_attachment_link( $file );
					$data .= '</li>';
				}

				$data .= '</ul>';
			}

			$details[ $key ] = sprintf( $text_format, $label, $data );
		}

		$delivery_details = implode( "\n\n", $details );

		return $delivery_details;
	}

}

?>

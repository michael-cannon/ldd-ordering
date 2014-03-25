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

require_once AIHR_DIR_LIB . 'class-redrokk-metabox-class.php';

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

		add_shortcode( 'ldd_ordering_shortcode', array( __CLASS__, 'ldd_ordering_shortcode' ) );
	}


	public static function admin_init() {
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
		// fixme add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'scripts' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'edd_after_checkout_cart', array( __CLASS__, 'edd_after_checkout_cart' ) );
		add_action( 'edd_checkout_error_checks', array( __CLASS__, 'edd_checkout_error_checks' ), 10, 2 );
		add_action( 'edd_post_add_to_cart', array( __CLASS__, 'edd_post_add_to_cart' ), 10, 2 );
		add_action( 'edd_post_remove_from_cart', array( __CLASS__, 'edd_post_remove_from_cart' ), 10, 2 );
		add_action( 'edd_update_payment_status', array( __CLASS__, 'edd_update_payment_status' ), 10, 3 );
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'scripts' ) );
	}


	public static function filters() {
		add_filter( 'edd_email_template_tags', array( __CLASS__, 'edd_email_template_tags' ), 10, 3 );
		add_filter( 'ldd_settings', array( __CLASS__, 'settings' ) );
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
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
			// do something
		} else {
			// fixme wp_enqueue_script( 'jquery' );

			// fixme wp_register_script( __CLASS__, self::$plugin_assets . 'js/ldd-ordering.js', array( 'jquery'  ), self::VERSION, true );
			// fixme wp_enqueue_script( __CLASS__ );
		}

		add_action( 'wp_footer', array( 'LDD_Ordering', 'get_scripts' ) );

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
		$title = esc_html__( 'Delivery Record for Payment #%1$s' );
		$title = sprintf( $title, $payment_id );

		$client_id = edd_get_payment_user_id( $payment_id );

		// create new delivery record
		$delivery_data = array(
			'post_type' => LDD::PT,
			'post_author' => $client_id,
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

				// fixme $page_count += self::getNumPagesPdf( $file );
			}

			// set page count
			// fixme add_post_meta( $delivery_id, self::KEY_PAGE_COUNT, $page_count );
		}

		// add point of contact details
		$name = edd_email_tag_fullname( $payment_id );
		add_post_meta( $delivery_id, 'name', $name );

		$email = edd_get_payment_user_email( $payment_id );
		add_post_meta( $delivery_id, 'email', $email );

		$payment = get_post( $payment_id );
		add_post_meta( $delivery_id, 'order_date', $payment->post_date );
		add_post_meta( $delivery_id, 'last_update', $payment->post_date );
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
				'name' => esc_html__( 'Order Summary' ),
				'id' => self::KEY_PAYMENT_ID,
				'type' => 'ldd_get_order_summary',
			),
			array(
				'name' => esc_html__( 'Delivery County' ),
				'id' => 'delivery_county',
				'type' => 'text',
			),
			array(
				'name' => esc_html__( 'Delivery Court' ),
				'id' => 'delivery_court',
				'type' => 'text',
			),
			array(
				'name' => esc_html__( 'Delivery Options' ),
				'id' => 'delivery_options',
				'type' => 'ldd_display_piped',
			),
			array(
				'name' => esc_html__( 'Court Filings' ),
				'id' => 'court_filings',
				'type' => 'ldd_get_attachment_links',
			),
			array(
				'name' => esc_html__( 'Return Mailing Address' ),
				'id' => 'return_mailing_address',
				'type' => 'textarea',
			),
			array(
				'name' => esc_html__( 'Opposing Counsel Mailing Address' ),
				'id' => 'opposing_counsel_mailing_address',
				'type' => 'textarea',
			),
			array(
				'name' => esc_html__( 'Special Instructions' ),
				'id' => 'special_instructions',
				'type' => 'textarea',
			),
			/** fixme
			array(
				'name' => esc_html__( 'Page Count' ),
				'id' => self::KEY_PAGE_COUNT,
				'type' => 'text',
			),
			 */
		);

		$fields = apply_filters( 'ldd_ordering_delivery_meta_box', $fields );

		$meta_box = redrokk_metabox_class::getInstance(
			self::ID . '-request',
			array(
				'title' => esc_html__( 'Request Details' ),
				'_object_types' => LDD::PT,
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
			),
			array(
				'name' => esc_html__( 'Name' ),
				'id' => 'name',
				'type' => 'text',
			),
			array(
				'name' => esc_html__( 'Job Title' ),
				'id' => 'job_title',
				'type' => 'text',
			),
			array(
				'name' => esc_html__( 'Telephone' ),
				'id' => 'telephone',
				'type' => 'text',
			),
			array(
				'name' => esc_html__( 'Email' ),
				'id' => 'email',
				'type' => 'text',
			),
			array(
				'name' => esc_html__( 'Shared Notifications' ),
				'id' => 'shared_notifications',
				'type' => 'textarea',
			),
		);

		$fields = apply_filters( 'ldd_ordering_poc_meta_box', $fields );

		$meta_box = redrokk_metabox_class::getInstance(
			self::ID . '-poc',
			array(
				'title' => esc_html__( 'Point of Contact' ),
				'_object_types' => LDD::PT,
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
				$data  = self::get_attachment_links( $files );
			}

			$details[ $key ] = sprintf( $text_format, $label, $data );
		}

		$delivery_details = implode( "\n\n", $details );

		return $delivery_details;
	}


	public static function get_attachment_links( $files ) {
		if ( ! is_array( $files ) )
			return;

		$edit_text = esc_html__( 'replace' );

		$data = '<ul>';
		foreach ( $files as $key => $file ) {
			$data .= '<li>';
			$data .= wp_get_attachment_link( $file );
			$data .= ' ';
			ob_start();
			edit_post_link( $edit_text, '(', ')', $file );
			$data .= ob_get_clean();
			$data .= '</li>';
		}

		$data .= '</ul>';

		return $data;
	}


	public static function get_order_summary( $payment_id ) {
		$cart = edd_get_purchase_download_links( $payment_id );

		$payment = get_post( $payment_id );
		$status  = edd_get_payment_status( $payment, true );

		$payment_date = $payment->post_date;

		$date = date_i18n( get_option( 'date_format' ), strtotime( $payment_date ) );
		$time = date_i18n( get_option( 'time_format' ), strtotime( $payment_date ) );

		$order_link = LDD_Operations::get_order_link( $payment_id );

		$text_positioning = esc_html__( '%2$s. %3$s %4$s. %5$s%1$s' );

		$data = sprintf( $text_positioning, $cart, $status, $date, $time, $order_link );

		return $data;
	}


	public static function edd_post_add_to_cart( $download_id, $options ) {
		if ( empty( $download_id ) )
			return;

		$fee_id = ldd_get_option( 'filing_fee_id' );
		if ( $fee_id == $download_id ) {
			$amount = ldd_get_option( 'filing_fee_amount' );
			$label  = esc_html__( 'Filing fee surcharge' );
			EDD()->fees->add_fee( $amount, $label, 'ffs' );
		}
	}


	public static function edd_post_remove_from_cart( $cart_key, $item_id ) {
		if ( empty( $item_id ) )
			return;

		$fee_id = ldd_get_option( 'filing_fee_id' );
		if ( $fee_id == $item_id ) {
			EDD()->fees->remove_fee( 'ffs' );
		}
	}


	public static function settings( $settings ) {
		$settings['delivery_heading'] = array(
			'desc' => esc_html__( 'Delivery Details' ),
			'type' => 'heading',
		);

		$settings['delivery_options'] = array(
			'title' => esc_html__( 'Delivery Order Restrictions' ),
			'desc' => esc_html__( 'Only one of these at a time is allowed to be ordered.' ),
			'std' => '69,146,169',
		);

		$settings['same_day_delivery_id'] = array(
			'title' => esc_html__( 'Same Day Delivery ID' ),
			'desc' => esc_html__( 'Used to help prevent same day delivery in rural locations.' ),
			'std' => 169,
		);

		$settings['filing_fee_heading'] = array(
			'desc' => esc_html__( 'Filing Details' ),
			'type' => 'heading',
		);

		$settings['filing_fee_id'] = array(
			'title' => esc_html__( 'Product ID' ),
			'std' => 241,
		);

		$settings['filing_fee_amount'] = array(
			'title' => esc_html__( 'Surcharge Fee' ),
			'std' => 50,
		);

		$settings['document_fee_heading'] = array(
			'desc' => esc_html__( 'Document Details' ),
			'type' => 'heading',
		);

		$settings['document_fee_amount'] = array(
			'title' => esc_html__( 'Fee' ),
			'desc' => esc_html__( 'Cost per printed document request.' ),
			'std' => 5,
		);

		$settings['mailing_fee_heading'] = array(
			'desc' => esc_html__( 'Mailing Details' ),
			'type' => 'heading',
		);

		$settings['mailing_fee_amount'] = array(
			'title' => esc_html__( 'Surcharge Fee' ),
			'std' => 25,
		);

		$settings['rural_fee_heading'] = array(
			'desc' => esc_html__( 'Rural Delivery Details' ),
			'type' => 'heading',
		);

		$settings['rural_fee_counties'] = array(
			'title' => esc_html__( 'Counties' ),
			'std' => 'Adams,Ferry,Lincoln,Steven,Whitman',
		);

		$settings['rural_fee_amount'] = array(
			'title' => esc_html__( 'Surcharge Fee' ),
			'std' => 35,
		);

		return $settings;
	}


	public static function edd_checkout_error_checks( $valid_data, $post ) {
		$require_return_mailing_address           = false;
		$require_opposing_counsel_mailing_address = false;

		$text_return_self     = 'Conformed set to be mailed back';
		$text_return_opposing = 'Conformed set to be mailed to Opposing Counsel';

		// a document set always goes to court clerk
		$doc_count_modifier = 1;
		if ( ! empty( $post['delivery_options'] ) ) {
			$doc_requests = $post['delivery_options'];
			if ( in_array( $text_return_self, $doc_requests ) ) {
				$require_return_mailing_address = true;
			}

			if ( in_array( $text_return_opposing, $doc_requests ) ) {
				$require_opposing_counsel_mailing_address = true;
			}

			$doc_count_modifier = count( $doc_requests );
		}

		$mailing_fee = ldd_get_option( 'mailing_fee_amount' );
		if ( $require_return_mailing_address ) {
			if ( empty( $post['return_mailing_address'] ) ) {
				$text = __( 'Please <a href="#return_mailing_address_wrap">add the return mailing address</a>.' );
				edd_set_error( 'missing_return_address', $text );
			}

			$label = esc_html__( 'Return mailing' );
			EDD()->fees->add_fee( $mailing_fee, $label, 'return_mailing' );
		} else {
			$has_mailing_fee = EDD()->fees->get_fee( 'return_mailing' );
			if ( ! empty( $has_mailing_fee ) ) {
				EDD()->fees->remove_fee( 'return_mailing' );
			}
		}

		if ( $require_opposing_counsel_mailing_address ) {
			if ( empty( $post['opposing_counsel_mailing_address'] ) ) {
				$text = __( 'Please <a href="#opposing_counsel_mailing_address_wrap">add the opposing counsel return mailing address</a>.' );
				edd_set_error( 'missing_opposing_return_address', $text );
			}

			$label = esc_html__( 'Opposing counsel return mailing' );
			EDD()->fees->add_fee( $mailing_fee, $label, 'opposing_return_mailing' );
		} else {
			$has_mailing_fee = EDD()->fees->get_fee( 'opposing_return_mailing' );
			if ( ! empty( $has_mailing_fee ) ) {
				EDD()->fees->remove_fee( 'opposing_return_mailing' );
			}
		}

		$rural_fee_counties = ldd_get_option( 'rural_fee_counties' );
		$rural_fee_counties = explode( ',', $rural_fee_counties );

		$prevent_same_day_delivery = false;
		if ( ! empty( $post['delivery_county'][0] ) && in_array( $post['delivery_county'][0], $rural_fee_counties ) ) {
			$total = ldd_get_option( 'rural_fee_amount' );
			$label = esc_html__( 'Rural delivery' );
			EDD()->fees->add_fee( $total, $label, 'rural' );
			
			$prevent_same_day_delivery = true;
		} else {
			$has_rural_fee = EDD()->fees->get_fee( 'rural' );
			if ( ! empty( $has_rural_fee ) ) {
				EDD()->fees->remove_fee( 'rural' );
			}
		}

		$cart = edd_get_cart_contents();

		$same_day_delivery_id  = ldd_get_option( 'same_day_delivery_id' );
		$has_same_day_delivery = false;

		$multiple_delivery = false;
		$ordered_items     = array();
		foreach ( $cart as $key => $item ) {
			if ( in_array( $item['id'], $ordered_items ) ) {
				$multiple_delivery = true;
			}

			if ( $same_day_delivery_id == $item['id'] ) {
				$has_same_day_delivery = true;
			}

			$ordered_items[] = $item['id'];
		}

		$delivery_options = ldd_get_option( 'delivery_options' );
		$delivery_options = explode( ',', $delivery_options );

		$delivery_intersect = array_intersect( $delivery_options, $ordered_items );

		$delivery_items = count( $delivery_intersect );
		if ( 1 < $delivery_items || $multiple_delivery ) {
			$text = __( 'Please <a href="#edd_checkout_cart_form">remove all but one delivery</a> option. Only one is allowed per order.' );
			edd_set_error( 'excess_delivery_items', $text );
		} elseif ( empty( $delivery_items ) ) {
			$text = __( 'Please <a href="/#services">choose a delivery option</a>. One is required per order.' );
			edd_set_error( 'no_delivery_item', $text );
		}

		if ( $prevent_same_day_delivery && $has_same_day_delivery ) {
			$text = __( 'Same day delivery for rural locations are not available. Please <a href="#edd_checkout_cart_form">remove same day delivery</a> and choose next day delivery instead.' );
			edd_set_error( 'prevent_same_day_rural', $text );
		}

		$has_no_docs = true;
		if ( ! empty( $post['cfm_files']['court_filings'] ) ) {
			$charge_for = count( $post['cfm_files']['court_filings'] );
			if ( ! empty( $charge_for ) ) {
				$amount    = ldd_get_option( 'document_fee_amount' );
				$doc_count = $charge_for * $doc_count_modifier;
				$total     = $doc_count * $amount;
				$text      = esc_html__( '%1$s printed %2$s' );
				$doc_text  = _n( 'document', 'documents', $doc_count );
				$label     = sprintf( $text, $doc_count, $doc_text );
				EDD()->fees->add_fee( $total, $label, 'docs' );

				$has_no_docs = false;
			}
		}
		
		if ( $has_no_docs ) {
			$has_doc_fee = EDD()->fees->get_fee( 'docs' );
			if ( ! empty( $has_doc_fee ) ) {
				EDD()->fees->remove_fee( 'docs' );
			}

			$text = __( 'Please <a href="#court_filings_wrap">upload documents for filing</a>.' );
			edd_set_error( 'no_documents', $text );
		}
	}


	public static function edd_after_checkout_cart() {
		$cart   = edd_get_cart_contents();
		$fee_id = ldd_get_option( 'filing_fee_id' );
		$show   = true;
		foreach ( $cart as $key => $item ) {
			if ( $fee_id == $item['id'] ) {
				$show = false;
			}
		}

		if ( $show ) {
			echo '<div class="ldd-ordering">';
			echo '<h3>Have Filings Fees? Add Them Here!</h3>';
			echo do_shortcode( '[purchase_link id="241"]' );
			echo '</div>';
		}
	}


	public static function display_piped( $meta ) {
		if ( empty( $meta[0] ) )
			return;

		$fields = explode( '|', $meta[0] );
		$data   = '<ul>';
		foreach ( $fields as $key => $option ) {
			$data .= '<li>';
			$data .= trim( $option );
			$data .= '</li>';
		}

		$data .= '</ul>';

		return $data;
	}
}

?>

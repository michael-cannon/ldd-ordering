<?php
/**
 * Plugin Name: Legal Document Deliveries - Ordering
 * Plugin URI: http://aihr.us
 * Description: LDD ordering system 
 * Version: 1.0.0
 * Author: Michael Cannon
 * Author URI: http://aihr.us/resume/
 * License: GPLv2 or later
 * Text Domain: ldd-ordering
 * Domain Path: /languages
 */


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

if ( ! defined( 'LDD_ORDERING_BASE' ) )
	define( 'LDD_ORDERING_BASE', plugin_basename( __FILE__ ) );

if ( ! defined( 'LDD_ORDERING_DIR' ) )
	define( 'LDD_ORDERING_DIR', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'LDD_ORDERING_DIR_INC' ) )
	define( 'LDD_ORDERING_DIR_INC', LDD_ORDERING_DIR . 'includes/' );

if ( ! defined( 'LDD_ORDERING_DIR_LIB' ) )
	define( 'LDD_ORDERING_DIR_LIB', LDD_ORDERING_DIR_INC . 'libraries/' );

if ( ! defined( 'LDD_ORDERING_NAME' ) )
	define( 'LDD_ORDERING_NAME', 'Legal Document Deliveries - Ordering' );

if ( ! defined( 'LDD_ORDERING_REQ_BASE' ) )
	define( 'LDD_ORDERING_REQ_BASE', 'ldd/ldd.php' );

if ( ! defined( 'LDD_ORDERING_REQ_NAME' ) )
	define( 'LDD_ORDERING_REQ_NAME', 'Legal Document Deliveries - Core ' );

if ( ! defined( 'LDD_ORDERING_REQ_SLUG' ) )
	define( 'LDD_ORDERING_REQ_SLUG', 'ldd' );

if ( ! defined( 'LDD_ORDERING_REQ_VERSION' ) )
	define( 'LDD_ORDERING_REQ_VERSION', '1.0.0' );

if ( ! defined( 'LDD_ORDERING_VERSION' ) )
	define( 'LDD_ORDERING_VERSION', '1.0.0' );

require_once LDD_ORDERING_DIR_INC . 'requirements.php';

global $ldd_ordering_activated;

$ldd_ordering_activated = true;
if ( ! ldd_ordering_requirements_check() ) {
	$ldd_ordering_activated = false;

	return false;
}

require_once LDD_ORDERING_DIR_INC . 'class-ldd-ordering.php';


add_action( 'plugins_loaded', 'ldd_ordering_init', 99 );


/**
 *
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
if ( ! function_exists( 'ldd_ordering_init' ) ) {
	function ldd_ordering_init() {
		if ( ! is_admin() )
			return;

		if ( LDD_Ordering::version_check() ) {
			global $LDD_Ordering;
			if ( is_null( $LDD_Ordering ) )
				$LDD_Ordering = new LDD_Ordering();
			
			do_action( 'ldd_ordering_init' );
		}
	}
}


register_activation_hook( __FILE__, array( 'LDD_Ordering', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'LDD_Ordering', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'LDD_Ordering', 'uninstall' ) );


if ( ! function_exists( 'ldd_ordering_shortcode' ) ) {
	function ldd_ordering_shortcode( $atts ) {
		return LDD_Ordering::ldd_ordering_shortcode( $atts );
	}
}

?>

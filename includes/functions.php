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

if ( ! function_exists( 'ldd_get_attachment_links' ) ) {
	function ldd_get_attachment_links( $args, $field, $meta ) {
		$files = array_shift( $meta );
		$files = maybe_unserialize( $files );

		echo LDD_Ordering::get_attachment_links( $files );
	}
}


if ( ! function_exists( 'ldd_get_order_summary' ) ) {
	function ldd_get_order_summary( $args, $field, $meta ) {
		if ( ! empty( $meta[0] ) )
			$payment_id = $meta[0];
		else
			return;

		echo LDD_Ordering::get_order_summary( $payment_id );
	}
}

?>

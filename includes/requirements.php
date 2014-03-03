<?php
/*
	Copyright 2014 Michael Cannon (email: mc@aihr.us)

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


function ldd_ordering_requirements_check() {
	$valid_requirements = true;
	if ( ! is_plugin_active( LDD_ORDERING_REQ_BASE ) ) {
		$valid_requirements = false;
		add_action( 'admin_notices', 'ldd_ordering_notice_version' );
	}

	if ( ! $valid_requirements ) {
		deactivate_plugins( LDD_ORDERING_BASE );
	}

	return $valid_requirements;
}


function ldd_ordering_notice_version() {
	aihr_notice_version( LDD_ORDERING_REQ_BASE, LDD_ORDERING_REQ_NAME, LDD_ORDERING_REQ_SLUG, LDD_ORDERING_REQ_VERSION, LDD_ORDERING_NAME );
}

?>

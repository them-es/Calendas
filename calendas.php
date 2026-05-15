<?php
/**
 * Plugin Name: Calendas
 * Plugin URI: https://wordpress.org/plugins/calendas
 * Description: 📅 A modern events calendar plugin for WordPress
 * Version: 0.9.0
 * Author: them.es
 * Author URI: https://them.es
 * Text Domain: calendas
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * On load: Initialize plugin.
 *
 * @return void
 */
function calendas_plugins_loaded(): void {
	// Initialize Classes.
	include_once __DIR__ . '/inc/class-calendas.php';

	if ( ! class_exists( 'Calendas' ) ) {
		return;
	}

	new Calendas();

	// Define an array of classes to initialize.
	$classes = array(
		'Calendas_Admin' => 'class-admin.php', // Admin area.
	);

	if ( class_exists( 'Polylang' ) || class_exists( 'SitePress' ) ) {
		$classes['Calendas_Polylang_To_WP_API'] = 'class-polylang-to-wpapi.php';
	}

	// Loop through the classes to include and instantiate.
	foreach ( $classes as $class => $file ) {
		if ( empty( $file ) ) {
			continue;
		}

		include_once __DIR__ . '/inc/' . $file;

		if ( class_exists( $class ) ) {
			new $class();
		} else {
			error_log( "Class $class could not be initialized." );
		}
	}
}
add_action( 'plugins_loaded', 'calendas_plugins_loaded', 100 );

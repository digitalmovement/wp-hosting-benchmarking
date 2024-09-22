<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://fastestwordpress.com
 * @since             1.0.0
 * @package           Wp_Hosting_Benchmarking
 *
 * @wordpress-plugin
 * Plugin Name:       Hosting Speed and Benchmark Analyzer
 * Plugin URI:        https://fastestwordpress.com/wp-hosting-benchmarking
 * Description:       Hosting Speed and Benchmark Analyzer is the ultimate WordPress plugin to measure and benchmark your website’s hosting performance. Got a slow site then this is the plugin that can help you find out if you hosting company is a fault.
 * Version:           1.0.0
 * Author:            Fastest WordPress
 * Author URI:        https://fastestwordpress.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-hosting-benchmarking
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WP_HOSTING_BENCHMARKING_VERSION', '1.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-hosting-benchmarking-activator.php
 */
function activate_wp_hosting_benchmarking() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-hosting-benchmarking-activator.php';
	Wp_Hosting_Benchmarking_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-hosting-benchmarking-deactivator.php
 */
function deactivate_wp_hosting_benchmarking() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-hosting-benchmarking-deactivator.php';
	Wp_Hosting_Benchmarking_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_hosting_benchmarking' );
register_deactivation_hook( __FILE__, 'deactivate_wp_hosting_benchmarking' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-hosting-benchmarking.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_hosting_benchmarking() {

	$plugin = new Wp_Hosting_Benchmarking();
	$plugin->run();

}
run_wp_hosting_benchmarking();

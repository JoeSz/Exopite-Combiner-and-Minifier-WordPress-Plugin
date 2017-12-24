<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://joe.szalai.org
 * @since             1.0.0
 * @package           Exopite_Combiner_Minifier
 *
 * @wordpress-plugin
 * Plugin Name:       Exopite Combiner and Minifier
 * Plugin URI:        https://joe.szalai.org/exopite/exopite-combiner-minifier
 * Description:       Minify and Combine Javascripts and CSS resources for better SEO and page speed. jQuery and external resources will be ignored.
 * Version:           20171224
 * Author:            Joe Szalai
 * Author URI:        https://joe.szalai.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       exopite-combiner-minifier
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
 * Combine and minify (c+m) enqueued css/js files.
 *  - Loop enqueued files,
 *  - 1.) ignore jQuery(s) and not same domain
 *    2.) only file list in settings
 *  - c+m,
 *  - write in file,
 *  - 1.) denqueue all
 *    2.) only file list in settings
 *  - display js data before js
 *  - enqueue c+m files (css -> head after not same domains, js -> footer, after data)
 *
 *
 * 2 ways:
 *  1.) - auto, conbine and minify, check file date to comapre,
 *        if nothing changed, then do not do anything.
 *        Pros: - easy to the user (default)
 *        Cons: - if different pages have different css/js, always minify and combine on the fly
 *                is way _too_ slow
 *  2.) - "save" button to c+m, save css/js file list to settings
 *        denqueue only what on the list
 *      - still check file times and new files, display an info in admin panel (settings page or global)
 *      - work after next site load?
 *
 * Problems:
 * - if some plugin enqueue someting in the footer, then this scripts is enqueued AFTER those,
 *   can be an dependency issue.
 * - if get footer scritps as well and some script is enqueued only on some pages, than too many "on the file"
 *   css/js file creation
 *
 * Async loading?
 * https://webmasters.stackexchange.com/questions/60276/how-does-cloudflares-rocket-loader-actually-work-and-how-can-a-developer-ensur
 * https://wordpress.org/support/topic/add-async-in-enqueue/
 * https://matthewhorne.me/defer-async-wordpress-scripts/
 */

define( 'EXOPITE_COMBINER_MINIFIER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Currently pligin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EXOPITE_COMBINER_MINIFIER_VERSION', '20171210' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-exopite-combiner-minifier-activator.php
 */
function activate_exopite_combiner_minifier() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-exopite-combiner-minifier-activator.php';
	Exopite_Combiner_Minifier_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-exopite-combiner-minifier-deactivator.php
 */
function deactivate_exopite_combiner_minifier() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-exopite-combiner-minifier-deactivator.php';
	Exopite_Combiner_Minifier_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_exopite_combiner_minifier' );
register_deactivation_hook( __FILE__, 'deactivate_exopite_combiner_minifier' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-exopite-combiner-minifier.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_exopite_combiner_minifier() {

	$plugin = new Exopite_Combiner_Minifier();
	$plugin->run();

}
run_exopite_combiner_minifier();

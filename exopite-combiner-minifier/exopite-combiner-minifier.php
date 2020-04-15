<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.joeszalai.org
 * @since             1.0.0
 * @package           Exopite_Combiner_Minifier
 *
 * @wordpress-plugin
 * Plugin Name:       Exopite Combiner and Minifier
 * Plugin URI:        https://www.joeszalai.org/exopite/exopite-combiner-minifier
 * Description:       Minify and Combine Javascripts and CSS resources for better SEO and page speed. jQuery and external resources will be ignored.
 * Version:           20200415
 * Author:            Joe Szalai
 * Author URI:        https://www.joeszalai.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       exopite-combiner-minifier
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * ToDos:
 *
 * Async loading?
 * https://webmasters.stackexchange.com/questions/60276/how-does-cloudflares-rocket-loader-actually-work-and-how-can-a-developer-ensur
 * https://wordpress.org/support/topic/add-async-in-enqueue/
 * https://matthewhorne.me/defer-async-wordpress-scripts/
 *
 */

/**
 * Note
 *
 * Sometimes JavaScripts are invalid or miss things, then after minifying will not work anymore.
 * Fix this with "try catch", it will add some extra chars, but worth it :)
 *
 * Combine and minify (c+m) enqueued css/js files.
 *  - Loop enqueued files,
 *  - 1.) ignore jQuery(s) and not same domain scripts
 *    2.) only file list in settings
 *  - c+m,
 *  - write in file,
 *  - 1.) denqueue all
 *    2.) only file list in settings
 *  - display js data (added with wp_localize_script) top of the file before any js file content
 *  - display css data (added with wp_add_inline_script) before css file content
 *  - change relative paths "url(path)" in css,
 *    make sure css is not break after file location changed (combined file isted of enqueued)
 *  - enqueue c+m files (css -> head after not same domains, js -> footer, after data)
 *
 *
 * 2 ways:
 *  1.) - auto, conbine and minify, check file date to comapre,
 *        if nothing changed, then do not do anything.
 *        Pros: - easy to the user (default)
 *        Cons: - may have dependency issues if/for scripts enqueued in footer
 *  2.) - process HTML before sent to browser
 *        Pros: - easy to the user, no dependency issues
 *        Cons: - separate file for each page
 *
 * Problems:
 * - (Methode 1) if some plugin enqueue someting in the footer, then this scripts is enqueued AFTER those,
 *   can be an dependency issue.
 * - (Methode 1) if get footer scritps as well and some script is enqueued only on some pages, than too many "on the fly"
 *   css/js file creation
 *
 */

define( 'EXOPITE_COMBINER_MINIFIER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EXOPITE_COMBINER_MINIFIER_PLUGIN_NAME', 'exopite-combiner-minifier' );

/**
 * Currently pligin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EXOPITE_COMBINER_MINIFIER_VERSION', '20200415' );

/*
 * Update
 */
if ( is_admin() ) {

    /**
     * A custom update checker for WordPress plugins.
     *
     * Useful if you don't want to host your project
     * in the official WP repository, but would still like it to support automatic updates.
     * Despite the name, it also works with themes.
     *
     * @link http://w-shadow.com/blog/2011/06/02/automatic-updates-for-commercial-themes/
     * @link https://github.com/YahnisElsts/plugin-update-checker
     * @link https://github.com/YahnisElsts/wp-update-server
     */
    if( ! class_exists( 'Puc_v4_Factory' ) ) {

        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'plugin-update-checker', 'plugin-update-checker.php' ) );

    }

    $MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
        'https://update.joeszalai.org/?action=get_metadata&slug=' . EXOPITE_COMBINER_MINIFIER_PLUGIN_NAME, //Metadata URL.
        __FILE__, //Full path to the main plugin file.
        EXOPITE_COMBINER_MINIFIER_PLUGIN_NAME //Plugin slug. Usually it's the same as the name of the directory.
    );

	/**
	 * add plugin upgrade notification
	 * https://andidittrich.de/2015/05/howto-upgrade-notice-for-wordpress-plugins.html
	 */
	add_action( 'in_plugin_update_message-' . EXOPITE_COMBINER_MINIFIER_PLUGIN_NAME . '/' . EXOPITE_COMBINER_MINIFIER_PLUGIN_NAME .'.php', 'exopite_combiner_minifier_show_upgrade_notification', 10, 2 );
	function exopite_combiner_minifier_show_upgrade_notification( $current_plugin_metadata, $new_plugin_metadata ) {

		/**
		 * Check "upgrade_notice" in readme.txt.
		 *
		 * Eg.:
		 * == Upgrade Notice ==
		 * = 20180624 = <- new version
		 * Notice		<- message
		 *
		 */
		if ( isset( $new_plugin_metadata->upgrade_notice ) && strlen( trim( $new_plugin_metadata->upgrade_notice ) ) > 0 ) {

			// Display "upgrade_notice".
			echo sprintf( '<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr( 'Important Upgrade Notice', 'exopite-multifilter' ), esc_html( rtrim( $new_plugin_metadata->upgrade_notice ) ) );

		}
    }

}
// End Update

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

<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://joe.szalai.org
 * @since      1.0.0
 *
 * @package    Exopite_Combiner_Minifier
 * @subpackage Exopite_Combiner_Minifier/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Exopite_Combiner_Minifier
 * @subpackage Exopite_Combiner_Minifier/includes
 * @author     Joe Szalai <joe@szalai.org>
 */
class Exopite_Combiner_Minifier {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Exopite_Combiner_Minifier_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'exopite-combiner-minifier';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Exopite_Combiner_Minifier_Loader. Orchestrates the hooks of the plugin.
	 * - Exopite_Combiner_Minifier_i18n. Defines internationalization functionality.
	 * - Exopite_Combiner_Minifier_Admin. Defines all hooks for the admin area.
	 * - Exopite_Combiner_Minifier_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-exopite-combiner-minifier-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-exopite-combiner-minifier-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-exopite-combiner-minifier-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-exopite-combiner-minifier-public.php';

		$this->loader = new Exopite_Combiner_Minifier_Loader();

        /**
         * Minify - minifier.org
         * Minify JavaScript and CSS.
         *
         * Make your website smaller and faster to load by minifying the JS and CSS code.
         * This minifier removes whitespace, strips comments, combines files, and optimizes/shortens a few common programming patterns.
         *
         * @link https://github.com/matthiasmullie/minify
         * @link https://github.com/matthiasmullie/minify/issues/83
         *
         * Ps. I had to modify data path in JS.php
         */
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'minifier.org', 'Minify.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'minifier.org', 'CSS.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'minifier.org', 'JS.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'minifier.org', 'Exception.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'minifier.org', 'Exceptions', 'BasicException.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'minifier.org', 'Exceptions', 'FileImportException.php' ) );
		require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'minifier.org', 'Exceptions', 'IOException.php' ) );

        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'path-converter', 'ConverterInterface.php' ) );
		require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'path-converter', 'Converter.php' ) );

		/**
		 * TEST
		 * minify scripts from autoptimize plugin, check if it is besser.
		 */
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'yui-php-cssmin-bundled', 'Colors.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'yui-php-cssmin-bundled', 'Utils.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'yui-php-cssmin-bundled', 'Minifier.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'jsmin.php' ) );
        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'vendor', 'minify', 'minify-html.php' ) );

        require_once join( DIRECTORY_SEPARATOR, array( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR, 'admin', 'exopite-simple-options','exopite-simple-options-framework-class.php' ) );

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Exopite_Combiner_Minifier_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Exopite_Combiner_Minifier_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Exopite_Combiner_Minifier_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        // Save/Update our plugin options
        $this->loader->add_action( 'init', $plugin_admin, 'create_menu' );

	}

	/**
     * Checks if the current request is a WP REST API request.
     *
     * Case #1: After WP_REST_Request initialisation
     * Case #2: Support "plain" permalink settings
     * Case #3: URL Path begins with wp-json/ (your REST prefix)
     *          Also supports WP installations in subfolders
     *
     * @returns boolean
     * @author matzeeable
	 * @link https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist/317041#317041
	 * @link https://gist.github.com/matzeeable/dfd82239f48c2fedef25141e48c8dc30
     */
    function is_rest() {
        $prefix = rest_get_url_prefix( );
        if (defined('REST_REQUEST') && REST_REQUEST // (#1)
            || isset($_GET['rest_route']) // (#2)
                && strpos( trim( $_GET['rest_route'], '\\/' ), $prefix , 0 ) === 0)
            return true;

        // (#3)
        $rest_url = wp_parse_url( site_url( $prefix ) );
        $current_url = wp_parse_url( add_query_arg( array( ) ) );
        return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
    }

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Exopite_Combiner_Minifier_Public( $this->get_plugin_name(), $this->get_version() );

        if ( ! is_admin() && ! $this->is_rest() ) {

            $options = get_option($this->plugin_name);
            $method = ( isset( $options['method'] ) ) ? $options['method'] : 'method-1';

            switch ( $method ) {

                case 'method-1':

                    $process_scripts = ( isset( $options['process_scripts'] ) ) ? $options['process_scripts'] : 'yes';
                    $process_styles = ( isset( $options['process_styles'] ) ) ? $options['process_styles'] : 'yes';

                    if ( $process_scripts == 'yes' ) $this->loader->add_action( 'wp_print_scripts', $plugin_public, 'scripts_handler', 999999 );
                    if ( $process_styles == 'yes' ) $this->loader->add_action( 'wp_print_styles', $plugin_public, 'styles_handler', 999999 );

                    break;

                case 'method-2':

                    /**
                     * Start buffering when wp_loaded hook called
                     * end buffering when showdown hook called
                     *
                     * In this case we can process the whole HTML just before it is send it to the browser.
                     * Need to be disabled in admin, doing JSON, REST, XMLRPC or AJAX request, also in this cases
                     * LazyLoad in unnecessarily.
                     *
                     */
                    if ( ! ( ( defined( 'JSON_REQUEST' ) && JSON_REQUEST ) ||
                             ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
                             ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) ||
                             ( defined('DOING_AJAX') && DOING_AJAX )
                        ) ) {

						if ( apply_filters( 'exopite_ob_status', 'off' ) != 'on' ) {

							$this->loader->add_filter( 'wp_loaded', $plugin_public, 'buffer_start', 12 );
							$this->loader->add_filter( 'shutdown', $plugin_public, 'buffer_end', 12 );

						}

						$this->loader->add_filter( 'exopite_ob_content', $plugin_public, 'process_html', 12 );

                    }

                    break;

            }

        }

        // The wp_ajax_ is telling wordpress to use ajax and the prefix_ajax_first is the hook name to use in JavaScript.
        // The ajax_first is the callback function.
        $this->loader->add_action('wp_ajax_exopite_cam_delete_cache', $plugin_public, 'delete_cache');

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Exopite_Combiner_Minifier_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}

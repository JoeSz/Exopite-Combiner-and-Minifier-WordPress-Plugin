<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://joe.szalai.org
 * @since      1.0.0
 *
 * @package    Exopite_Combiner_Minifier
 * @subpackage Exopite_Combiner_Minifier/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Exopite_Combiner_Minifier
 * @subpackage Exopite_Combiner_Minifier/admin
 * @author     Joe Szalai <joe@szalai.org>
 */
class Exopite_Combiner_Minifier_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    20180622
     * @var object      The main class.
     */
    public $main;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_main ) {

        $this->main = $plugin_main;
		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Exopite_Combiner_Minifier_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Exopite_Combiner_Minifier_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/exopite-combiner-minifier-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Exopite_Combiner_Minifier_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Exopite_Combiner_Minifier_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/exopite-combiner-minifier-admin.js', array( 'jquery' ), $this->version, false );

	}

    public function create_menu() {

        /*
         * Create a submenu page under Plugins.
         * Framework also add "Settings" to your plugin in plugins list.
         */
        $config_submenu = array(

            'type'              => 'menu',                          // Required, menu or metabox
            'id'                => $this->plugin_name,              // Required, meta box id, unique per page, to save: get_option( id )
            'parent'            => 'plugins.php',                   // Required, sub page to your options page
            'submenu'           => true,                            // Required for submenu
            'title'             => 'Exopite Combiner Minifier',     // The name of this page
            'capability'        => 'manage_options',                // The capability needed to view the page
            'plugin_basename'   =>  plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' ),
            // 'tabbed'            => false,
            'multilang'         => false,

        );

        $fields[] = array(
            'title'  => 'General',
            'icon'   => 'dashicons-admin-settings',
            'name'   => $this->plugin_name,
            'fields' => array(

                array(
                    'type'    => 'card',
                    'content' => '<p>' .
                        '<p><a href="https://joe.szalai.org/exopite/exopite-combiner-and-minifier/" target="_blank">Exopite Combiner and Minifier</a></p>' .
                        esc_html__( 'I wrote this plugin, because I did try several one to achieve this, but unfortunately non of them did the job. Mostly because of this plugins wonking witt WordPress registered scripts and some developer enqueue they styles and/or scripts in the footer, too late to process them.', 'exopite-combiner-minifier' ) . '</p><p>' .
                        esc_html__( 'This plugin also does that as well as process HTML after WordPress render it. If one method not working perfectly please try the other one.', 'exopite-combiner-minifier' ) . '</p><p>' .
                        esc_html__( 'Minify and Combine Javascripts and CSS resources for better SEO and page speed. Merges/combine Style Sheets & Javascript files into one file (jQuery and external resources will be ignored), then minifies it the generated files using CssMin for CSS and JSMinPlus for JS. Minification is done only the first time the site is displayed, so that it does not slow down your website. When JS or CSS changes based on the last modified time, files are regenerate. No need to empty cache! It has to different methodes, please select method to display further information.', 'exopite-combiner-minifier' ) .
                        '</p>',
                    'header' => esc_html__( 'Information', 'exopite-combiner-minifier' ),
                ),

                array(
                    'id'            => 'delete_cache',
                    'type'          => 'button',
                    'title'         => esc_html__( 'Delete Cache', 'exopite-combiner-minifier' ),
                    'options'       => array(
                        'value'         => esc_html__( 'Delete Cache', 'exopite-combiner-minifier' ),
                    ),
                    'class'         => 'exopite-cam-delete-cache-js',
                    'attributes'    => array(
                        'data-confirm'  => 'Are you sure, you want to delete cache?',
                        'data-ajaxurl'  => site_url( 'wp-admin/admin-ajax.php' ),
                    ),
                ),

            ),
        );

        $fields[] = array(
            'title'  => 'Methods',
            'icon'   => 'dashicons-admin-generic',
            'name'   => 'methods',
            'fields' => array(

                array(
                    'id'      => 'method',
                    'type'    => 'button_bar',
                    'title'   => esc_html__( 'Method', 'exopite-combiner-minifier' ),
                    'options' => array(
                        'method-1'   => 'Method 1',
                        'method-2'   => 'Method 2',
                        'method-3'   => 'Method 3',
                    ),
                    'default' => 'method-2',
                ),

                array(
                    'type'    => 'card',
                    'content' => '<p>' .
                        esc_html__( 'This method go through all WordPress registered scripts and combine and minify them.', 'exopite-combiner-minifier' ) .'</p><p><b>' .
                        esc_html__( 'Pros: ', 'exopite-combiner-minifier' ) . '</b><ul class="list-arguments pros">' .
                        '<li>' . esc_html__( 'easy to the user', 'exopite-combiner-minifier' ) . '</li>' .
                        '<li>' . esc_html__( 'checking much faster (~0.005s)', 'exopite-combiner-minifier' ) . '</li>' .
                        '</ul></p><p><b>' .
                        esc_html__( 'Cons: ', 'exopite-combiner-minifier' ) . '</b><ul class="list-arguments cons">' .
                        '<li>' . esc_html__( 'may have dependency issues if/for scripts enqueued in footer', 'exopite-combiner-minifier' ) . '</li>' .
                        '<li style="font-weight: bold;color: red;">' . esc_html__( 'if site has different styles and/or srcipts per page, will be regenerate every time if other page will displyed, in this case, please use method 2', 'exopite-combiner-minifier' ) . '</li>' .
                        '<li>' . esc_html__( 'can generate only one file per type (Css/JS)', 'exopite-combiner-minifier' ) . '</li>' .
                        '</ul>' .
                        '</p>',
                    'header'  => esc_html__( 'Method 1', 'exopite-combiner-minifier' ),
                    'dependency' => array( 'method_method-1', '==', 'true' ),
                ),

                array(
                    'type'    => 'card',
                    'content' => '<p>' .
                        esc_html__( 'This method process the HTML source after WordPress render them and before sent to browser. It will create a separate Css/JS file for each page, make sure, all "in the footer" enqueued scripts are correctly processed. This method uses PHP native DOMDocument and Output Buffering.', 'exopite-combiner-minifier' ) .'</p><p><b>' .
                        esc_html__( 'Pros: ', 'exopite-combiner-minifier' ) . '</b><ul class="list-arguments pros">' .
                        '<li>' . esc_html__( 'easy to the user', 'exopite-combiner-minifier' ) . '</li>' .
                        '<li>' . esc_html__( 'no dependency issues', 'exopite-combiner-minifier' ) . '</li>' .
                        '<li>' . esc_html__( 'can create a separate Css/JS file for each page', 'exopite-combiner-minifier' ) . '</li>' .
                        '</ul></p><p><b>' .
                        esc_html__( 'Cons: ', 'exopite-combiner-minifier' ) . '</b><ul class="list-arguments cons">' .
                        '<li>' . esc_html__( 'checking is slower (~0.1s)', 'exopite-combiner-minifier' ) . '</li>' .
                        '</ul>' .
                        '</p>',
                    'header'  => esc_html__( 'Method 2', 'exopite-combiner-minifier' ),
                    'dependency' => array( 'method_method-2', '==', 'true' ),
                ),

                array(
                    'type'    => 'card',
                    'content' => '<p>' .
                        esc_html__( 'This method process the HTML source after WordPress render them and before sent to browser. It will create a separate Css/JS file for each page, make sure, all "in the footer" enqueued scripts are correctly processed. This method uses PHP Simple DOM Parser and Output Buffering.', 'exopite-combiner-minifier' ) .'</p><p><b>' .
                        '</p>',
                    'header'  => esc_html__( 'Method 3', 'exopite-combiner-minifier' ),
                    'dependency' => array( 'method_method-3', '==', 'true' ),
                ),

            ),
        );

        $fields[] = array(
            'title'  => esc_html__( 'Styles', 'exopite-combiner-minifier' ),
            'icon'   => 'fa fa-css3',
            'name'   => 'styles',
            'fields' => array(

                array(
                    'id'      => 'process_styles',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Process styles', 'exopite-combiner-minifier' ),
                    'default' => 'no',
                ),

                array(
                    'id'      => 'ignore_process_styles',
                    'type'    => 'textarea',
                    'title'   => esc_html__( 'Ignore styles', 'exopite-combiner-minifier' ),
                    'default' => 'dashicons.css' . PHP_EOL . 'admin-bar.css' . PHP_EOL . 'admin-menu.min.css' . PHP_EOL . 'dashicons.min.css' . PHP_EOL . 'admin-bar.min.css',
                    'after'   => esc_html__( 'Only style name with extension. One per line.', 'exopite-combiner-minifier' ),
                    // 'dependency' => array( 'method_method-1', '==', 'false' ),
                ),

                array(
                    'id'      => 'combine_only_styles',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Combine only (styles)', 'exopite-combiner-minifier' ),
                    'default' => 'no',
                ),

                array(
                    'id'      => 'enqueue_head_styles',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Enqueue style in header', 'exopite-combiner-minifier' ),
                    'default' => 'yes',
                ),

                array(
                    'id'         => 'generate_head_styles',
                    'type'       => 'switcher',
                    'title'      => esc_html__( 'Inject styles as inline to head', 'exopite-combiner-minifier' ),
                    'default'    => 'no',
                    'dependency' => array( 'method_method-1', '==', 'false' ),
                ),

                array(
                    'id'      => 'create_separate_css_files',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Separate files for each page.', 'exopite-combiner-minifier' ),
                    'default' => 'no',
                    'dependency' => array( 'method_method-1', '==', 'false' ),
                ),

            ),
        );

        $fields[] = array(
            'title'  => esc_html__( 'Scripts', 'exopite-combiner-minifier' ),
            'icon'   => 'fa fa-code',
            'name'   => 'scripts',
            'fields' => array(

                array(
                    'id'      => 'process_scripts',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Process scripts', 'exopite-combiner-minifier' ),
                    'default' => 'no',
                ),

                array(
                    'id'      => 'ignore_process_scripts',
                    'type'    => 'textarea',
                    'title'   => esc_html__( 'Ignore scripts', 'exopite-combiner-minifier' ),
                    'default' => 'jquery.js' . PHP_EOL . 'jquery-migrate.min.js' . PHP_EOL . 'admin-bar.min.js',
                    'after'   => esc_html__( 'Only script name with extension. One per line.', 'exopite-combiner-minifier' ),
                    // 'dependency' => array( 'method_method-1', '==', 'false' ),
                ),

                array(
                    'id'         => 'process_inline_scripts',
                    'type'       => 'switcher',
                    'title'      => esc_html__( 'Process inline scripts', 'exopite-combiner-minifier' ),
                    'default'    => 'no',
                    'dependency' => array( 'method_method-1', '==', 'false' ),
                ),

                array(
                    'id'      => 'scripts_try_catch',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Add try catch', 'exopite-combiner-minifier' ),
                    'default' => 'yes',
                    'after'   => esc_html__( 'To avoid crashes on falsy JavaScripts.', 'exopite-combiner-minifier' ),
                ),

                array(
                    'id'      => 'combine_only_scripts',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Combine only (scripts)', 'exopite-combiner-minifier' ),
                    'default' => 'no',
                ),

                array(
                    'id'      => 'create_separate_js_files',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Separate files for each page.', 'exopite-combiner-minifier' ),
                    'default' => 'yes',
                    'dependency' => array( 'method_method-1', '==', 'false' ),
                ),

            ),
        );

        $fields[] = array(
            'title'  => esc_html__( 'HTML', 'exopite-combiner-minifier' ),
            'icon'   => 'fa fa-html5',
            'name'   => 'html',
            'dependency' => array( 'method_method-1', '==', 'false' ),
            'fields' => array(

                array(
                    'id'      => 'process_html',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Minify HTML output', 'exopite-combiner-minifier' ),
                    'default' => 'no',
                    'dependency' => array( 'method_method-1', '==', 'false' ),
                ),

            ),
        );

        $options_panel = new Exopite_Simple_Options_Framework( $config_submenu, $fields );

    }



}

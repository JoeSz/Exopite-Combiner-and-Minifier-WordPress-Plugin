<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://joe.szalai.org
 * @since      1.0.0
 *
 * @package    Exopite_Combiner_Minifier
 * @subpackage Exopite_Combiner_Minifier/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Exopite_Combiner_Minifier
 * @subpackage Exopite_Combiner_Minifier/public
 * @author     Joe Szalai <joe@szalai.org>
 */
class Exopite_Combiner_Minifier_Public {

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

    private $site_url;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		// wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/exopite-combiner-minifier-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/exopite-combiner-minifier-public.js', array( 'jquery' ), $this->version, false );

	}

    /**
     * [startsWith description]
     * @param  [string] $haystack
     * @param  [string] $needle
     * @return [bool]
     * @link https://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php/834355#834355
     */
    public function starts_with( $haystack, $needle ) {
        $length = strlen( $needle );
        return ( substr( $haystack, 0, $length ) === $needle );
    }

    /*
     * Get path from url
     * Oly work with local urls.
     */
    public function get_path( $url = '' ) {

        $path = str_replace(
            site_url(),
            wp_normalize_path( untrailingslashit( ABSPATH ) ),
            $url
        );

        return $path;
    }

    public function get_file_last_modified_time( $path ) {

        if ( file_exists( $path ) ) {
            return filemtime( $path );
        }

        return false;

    }

    /**
     * Converting Relative URLs to Absolute URLs in PHP
     * @param  [string] $rel  relative item in css
     * @param  [string] $base the css file url
     * @return [string]       absolute url
     *
     * @link http://www.gambit.ph/converting-relative-urls-to-absolute-urls-in-php/
     *
     * Usage
     *
     * rel2abs( '../images/image.jpg', 'http://gambit.ph/css/style.css' );
     * Outputs http://gambit.ph/images/image.jpg
     */
    public function rel2abs( $rel, $base ) {

        // parse base URL  and convert to local variables: $scheme, $host,  $path
        extract( parse_url( $base ) );

        if ( strpos( $rel,"//" ) === 0 ) {
            return $scheme . ':' . $rel;
        }

        // return if already absolute URL
        if ( parse_url( $rel, PHP_URL_SCHEME ) != '' ) {
            return $rel;
        }

        // queries and anchors
        if ( $rel[0] == '#' || $rel[0] == '?' ) {
            return $base . $rel;
        }

        // remove non-directory element from path
        $path = preg_replace( '#/[^/]*$#', '', $path );

        // destroy path if relative url points to root
        if ( $rel[0] ==  '/' ) {
            $path = '';
        }

        // dirty absolute URL
        $abs = $host . $path . "/" . $rel;

        // // replace '//' or  '/./' or '/foo/../' with '/'
        // $abs = preg_replace( "/(\/\.?\/)/", "/", $abs );
        // $abs = preg_replace( "/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs );

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

        // absolute URL is ready!
        return $scheme . '://' . $abs;
    }

    public function check_last_modified_time( $filename, $timestamp ) {

        $file_last_modified_time = $this->get_file_last_modified_time( $filename );

        if ( ! $file_last_modified_time || $file_last_modified_time < $timestamp ) return true;

        return false;

    }

    /*************************************************************\
     *                                                           *
     *                         METHOD 1                          *
     *                                                           *
    \*************************************************************/

    public function get_enqueued( $list, $type = 'wp_scripts' ) {

        global ${$type};
        $site_url = get_site_url();
        $script_last_modified = false;
        $result = [];

        foreach( $list as $handle ) {

            /*
             * exopite-combiner-minifier-skip-wp_scripts
             * exopite-combiner-minifier-skip-wp_styles
             */
            switch ( $type ) {

                case 'wp_scripts':
                    $to_skip = apply_filters( 'exopite-combiner-minifier-skip-' . $type, array( 'jquery' ) );
                    break;

                case 'wp_styles':
                    $to_skip = apply_filters( 'exopite-combiner-minifier-skip-' . $type, array() );
                    break;

            }


            // Skip jQuery, this is an empty handle, jQuerys handle is jquery-core
            if ( in_array( $handle, $to_skip ) ) continue;

            /*
             * Clean up url, for example wp-content/themes/wdc/main.js?v=1.2.4
             * become wp-content/themes/wdc/main.js
             */
            $src = strtok( ${$type}->registered[$handle]->src, '?' );

            /*
             * To "fix" styles, scripts start with /wp-includes, like jQuery
             * If you activate this, ALL not external will be processed.
             * This can produce error, because some styles, scripts are enqueued
             * very late that we won’t able to catch it earlier and they may have a depency.
             */
            if ( apply_filters( 'exopite-combiner-minifier-' . $type . '-process-wp_includes', false ) ) {
                $wp_content_url = str_replace( $site_url, '', includes_url() );
                if ( $this->starts_with( $src, $wp_content_url ) ) {
                    $src = $site_url . $src;
                }
            }

            /*
             * Skip external resources, plugin and theme author use CDN for a reason.
             */
            if ( apply_filters( 'exopite-combiner-minifier-' . $type . '-ignore-external', true ) ) {

                if ( ! $this->starts_with( $src, $site_url ) ) continue;

            }

            /*
             * Get path from src
             */
            $path = $this->get_path( $src );

            /*
             * Get last modified item datetime stamp
             */
            $file_last_modified_time = $this->get_file_last_modified_time( $path );
            if ( ! $script_last_modified || ( $file_last_modified_time && $file_last_modified_time > $script_last_modified ) ) {
                $script_last_modified = $file_last_modified_time;
            }


            // $data = ${$type}->registered[$handle]->extra['data'];
            $data = ( isset( ${$type}->registered[$handle]->extra['data'] ) ) ? ${$type}->registered[$handle]->extra['data'] : '';

            $result[] =  array(
                'src'       => $src,
                'handle'    => $handle,
                'data'      => $data,
                'path'      => $path,
            );
        }

        $result['last-modified'] = $script_last_modified;

        return $result;

    }

    public function get_combined( $list, $data_only = false ) {

        $result = [];
        $result['data'] = '';
        $result['content'] = '';

        foreach ( $list as $item ) {

            // Process css files
            if ( ! $data_only && substr( $item['path'], strrpos( $item['path'], '.' ) + 1 ) == 'css' ) {

                if ( file_exists( $item['path'] ) ) {

                    /*
                     * Replace all relative url() to absoulte
                     * Need to do this, because our combined css has a different path.
                     * Ignore already absoulte urls, start with "http" and "//",
                     * also ignore "data".
                     */
                    $result['content'] .= preg_replace_callback(
                        '/url\(\s*[\'"]?\/?(.+?)[\'"]?\s*\)/i',
                        function ( $matches ) use( $item ) {

                            if ( ! $this->starts_with( $matches[1], 'http' ) &&
                                 ! $this->starts_with( $matches[1], '//' ) &&
                                 ! $this->starts_with( $matches[1], 'data' ) ) {
                            }
                            return "url('" . $this->rel2abs( $matches[1], $item['src'] ) . "')";
                        },
                        file_get_contents( $item['path'] )
                    );

                }

            } else {

                /*
                 * We can collect "data" only in scripts
                 */
                if ( isset( $item['data'] ) ) {
                    $result['data'] .= $item['data'];
                }

                if ( ! $data_only && file_exists( $item['path'] ) ) {

                    $result['content'] .= file_get_contents( $item['path'] );

                }

            }

        }

        return $result;

    }

    public function denqueue( $list, $type = 'scripts' ) {

        foreach ( $list as $item ) {

            switch ( $type ) {

                case 'scripts':

                    wp_deregister_script( $item['handle'] );

                    break;

                case 'styles':

                    wp_deregister_style( $item['handle'] );

                    break;

            }

        }

    }

    public function minify_styles( $combined_mifinited_filename, $contents ) {

        $startTime = microtime(true);
        file_put_contents( $combined_mifinited_filename, CssMin::minify( $contents['content'] ) );
        echo "<!-- Exopite Combiner and Minifier - minify and write CSS:  " . number_format(( microtime(true) - $startTime), 4) . "s. -->\n";

    }

    public function minify_scripts( $combined_mifinited_filename, $contents ) {

        $startTime = microtime(true);
        file_put_contents( $combined_mifinited_filename, $contents['data'] . JSMinPlus::minify( $contents['content'], array('flaggedComments' => false) ) );
        echo "<!-- Exopite Combiner and Minifier - minify and write JS:  " . number_format(( microtime(true) - $startTime), 4) . "s. -->\n";

    }

    /**
     * @param  [string] $type scripts, styles
     */
    public function execute( $type, $combined_file_name ) {

        $wp_type = 'wp_' . $type;

        global ${$wp_type};

        /*
         * Reorder the handles based on its dependency,
         * The result will be saved in the to_do property ($wp_scripts->to_do, $wp_styles->to_do)
         */
        ${$wp_type}->all_deps( ${$wp_type}->queue );
        $list = $this->get_enqueued( ${$wp_type}->to_do, $wp_type );

        $list = apply_filters( 'exopite-combiner-minifier-enqueued-' . $type . '-list', $list );

        /*
         * Check if enqueued list
         */
        $src_list = array();
        foreach ( $list as $value ) {
            $src_list[] = $value['src'];
        }

        // Get saved list from plugin options
        $plugin_options = get_option( $this->plugin_name );
        $list_saved = $plugin_options['list-saved-' . $type];

        $list_changed = ( $list_saved != $src_list );

        if ( $list_changed ) {

            // if list has been changed, update plugin options
            $plugin_options['list-saved-' . $type] = $src_list;
            update_option( $this->plugin_name, $plugin_options );

        }

        /*
         * Set minified and combined file name
         */
        $combined_mifinited_filename = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined' . DIRECTORY_SEPARATOR . $combined_file_name;
        $combined_mifinited_filename = apply_filters( 'exopite-combiner-minifier-' . $type . '-file-path', $combined_mifinited_filename );
        $combined_last_modified_times = $list['last-modified'];

        /*
         * Check if need to regenerate
         *
         * - if enqueued has been changed or
         * - if has a script with a newer modified time as combined file of
         * - if user override with filter
         *
         * then regenerate combined file, make sure it is up to date.
         *
         * With this, we do not have to generate the file every time, only if some changes occurred,
         * it is more convenient for the user, because it is automatic.
         */
        if ( $list_changed ||
             $this->check_last_modified_time( $combined_mifinited_filename, $list['last-modified'] ) ||
             apply_filters( 'exopite-combiner-minifier-force-generate-' . $type, false ) ) {

            $fn = 'minify_' . $type;

            $contents = $this->get_combined( $list );
            $contents = apply_filters( 'exopite-combiner-minifier-enqueued-' . $type . '-contents', $contents );

            $this->{$fn}( $combined_mifinited_filename, $contents );
            $combined_last_modified_times = time();

        }

        /*
         * Remove/denqeue styles which are already processed
         */
        $this->denqueue( $list, $type );

        return apply_filters( 'exopite-combiner-minifier-' . $type . '-last-modified', $combined_last_modified_times );

    }

    public function styles_handler() {

        if ( apply_filters( 'exopite-combiner-minifier-process-styles', true ) ) {

            $startTime = microtime(true);

            do_action( 'exopite-combiner-minifier-styles-before-process' );

            $combined_file_name = 'styles-combined.css';
            $combined_last_modified_times = $this->execute( 'styles', $combined_file_name );
            $combined_mifinited_file_url = EXOPITE_COMBINER_MINIFIER_PLUGIN_URL . 'combined/' . $combined_file_name;
            $combined_mifinited_file_url = apply_filters( 'exopite-combiner-minifier-styles-file-url', $combined_mifinited_file_url );

            /*
             * Enqeue combined and minified styles.
             */
            wp_enqueue_style( 'styles-combined', $combined_mifinited_file_url, null, $combined_last_modified_times );

            do_action( 'exopite-combiner-minifier-styles-after-process' );

            $time_styles = number_format( ( microtime(true) - $startTime ), 4 );

            echo '<!-- Exopite Combiner Minifier - JSMinPlus: '. $time_styles . 's. -->' . PHP_EOL;

        }

    }

    public function scripts_handler() {

        if ( apply_filters( 'exopite-combiner-minifier-process-scripts', true ) ) {

            $startTime = microtime(true);

            do_action( 'exopite-combiner-minifier-scripts-before-process' );

            $combined_file_name = 'scripts-combined.js';
            $combined_last_modified_times = $this->execute( 'scripts', $combined_file_name );
            $combined_mifinited_file_url = EXOPITE_COMBINER_MINIFIER_PLUGIN_URL . 'combined/' . $combined_file_name;
            $combined_mifinited_file_url = apply_filters( 'exopite-combiner-minifier-scripts-file-url', $combined_mifinited_file_url );

            /*
             * Enqeue combined and minified scripts with scripts date on the front.
             */
            add_filter('wp_footer', function( $content ) use( $combined_last_modified_times, $combined_mifinited_file_url ) {

                wp_enqueue_script( 'scripts-combined', $combined_mifinited_file_url, array( 'jquery' ), $combined_last_modified_times, true );

            });

            do_action( 'exopite-combiner-minifier-scripts-after-process' );

            $time_scripts = number_format( ( microtime(true) - $startTime ), 4 );

            echo '<!-- Exopite Combiner Minifier - JSMinPlus: '. $time_scripts . 's. -->' . PHP_EOL;

        }

    }

    /*************************************************************\
     *                                                           *
     *                         METHOD 2                          *
     *                                                           *
    \*************************************************************/

    public function buffer_start() {

        // Start output buffering with a callback function
        ob_start( array( $this, 'process_html' ) );

    }

    public function buffer_end() {

        // Display buffer
        if ( ob_get_length() ) ob_end_flush();

    }

    public function to_skip( $src, $path, $type, $media = '' ) {

        if ( ! $this->starts_with( $src, $this->site_url ) ) return true;

        switch ( $type ) {

            case 'scripts':
                $to_skip = array( 'jquery', 'jquery-migrate.min' );
                break;

            case 'styles':
                $allowed_media = array( 'all', 'screen' );
                if ( ! in_array( $media, $allowed_media ) ) return true;
                $to_skip = array( 'admin-bar.min', 'dashicons.min' );
                break;

        }

        $pathinfo = pathinfo( $path );
        if ( in_array( $pathinfo['filename'], $to_skip ) ) return true;

        return false;

    }

    public function get_last_modified( $items, $type ) {

        $file_last_modified_time = '';
        $script_last_modified = false;

        foreach( $items as $item ) {

            switch ( $type ) {

                case 'scripts':
                    $src = $item->src;
                    break;

                case 'styles':
                    $src = $item->href;
                    break;

            }

            if ( isset( $src ) ) {

                $src = strtok( $src, '?' );

                /*
                 * Get path from src
                 */
                $path = $this->get_path( $src );

                if ( $this->to_skip( $src, $path, $type, $item->media ) ) continue;

                /*
                 * Get last modified item datetime stamp
                 */
                $file_last_modified_time = $this->get_file_last_modified_time( $path );

                if ( ! $script_last_modified || ( $file_last_modified_time && $file_last_modified_time > $script_last_modified ) ) {
                    $script_last_modified = $file_last_modified_time;
                }

            }

        }

        return $script_last_modified;

    }

    public function check_list( $items, $type ) {

        $list_saved = get_post_meta( get_the_ID(), $this->plugin_name . '-' . $type, true );

        $list = array();

        foreach( $items as $item ) {

            switch ( $type ) {

                case 'scripts':
                    $src = $item->src;
                    break;

                case 'styles':
                    $src = $item->href;
                    break;

            }

            if ( isset( $src ) && $src ) {

                /*
                 * Get path from src
                 */
                $path = $this->get_path( $src );

                if ( $this->to_skip( $src, $path, $type, $item->media ) ) continue;

                $list[] = strtok( $src, '?' );

            }

        }

        if ( $list_saved != $list ) {

            update_post_meta( get_the_ID(), $this->plugin_name . '-' . $type, $list );
            return true;

        }

        return false;

    }

    public function process_scripts_styles( $content ) {

        $id = get_the_ID();

        if ( empty( $id ) ) $content;

        $log = true;

        if ( $log ) $time_scripts = 'NaN ';
        if ( $log ) $time_styles = 'NaN ';
        if ( $log ) $time_html = 'NaN ';

        $options = get_option( $this->plugin_name );
        $process_scripts = ( isset( $options['process_scripts'] ) ) ? $options['process_scripts'] : 'yes';
        $process_styles = ( isset( $options['process_styles'] ) ) ? $options['process_styles'] : 'yes';
        $process_html = ( isset( $options['process_html'] ) ) ? $options['process_html'] : 'yes';

        if ( $process_scripts == 'yes' || $process_styles == 'yes' ) {

            $html = new simple_html_dom();

            // Load HTML from a string/variable
            $html->load( $content, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT );

            /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
             *                          Scripts                        *
            \* * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

            if ( $process_scripts == 'yes' ) {

                if ( $log ) $start_time = microtime(true);

                do_action( 'exopite-combiner-minifier-scripts-before-process' );

                $to_write = '';

                /*
                 * Set script file name
                 */
                $combined_scripts_file_name = 'scripts-combined-' . get_the_ID() . '.js';
                $combined_scripts_mifinited_file_url = EXOPITE_COMBINER_MINIFIER_PLUGIN_URL . 'combined/' . $combined_scripts_file_name;
                $combined_scripts_mifinited_file_url = apply_filters( 'exopite-combiner-minifier-scripts-file-url', $combined_scripts_mifinited_file_url );

                $combined_scripts_mifinited_filename = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined' . DIRECTORY_SEPARATOR . $combined_scripts_file_name;
                $combined_scripts_mifinited_filename = apply_filters( 'exopite-combiner-minifier-scripts-file-path', $combined_scripts_mifinited_filename );

                $items = $html->find( 'script' );

                $last_modified = $this->get_last_modified( $items, 'scripts' );

                $create_file = false;

                // If combined and minified files are different then the enqueued files or
                // the last modified time is different or
                // override it via filter
                // then need to regenerate file.
                if ( $this->check_list( $items, 'scripts' ) ||
                     $this->check_last_modified_time( $combined_scripts_mifinited_filename, $last_modified ) ||
                     apply_filters( 'exopite-combiner-minifier-force-generate-' . $type, false ) ) {

                    $create_file = true;

                }

                foreach( $items as $item ) {

                    /*
                     * If item has scr then get file content
                     * if not, get inline scripts
                     */
                    if ( isset( $item->src ) ) {

                        // Get item url and remove attributes.
                        $src = $item->src;
                        $src = strtok( $src, '?' );

                        // Get path from url
                        $path = $this->get_path( $src );

                        // Skip admin scripts, jQuery, ...
                        if ( $this->to_skip( $src, $path, 'scripts' ) )  continue;

                        if ( $create_file ) $to_write .= file_get_contents( $path );

                    } else {

                        if ( $create_file ) $to_write .= $item->innertext;

                    }

                    // Remove processed
                    $item->outertext = '';

                }

                if ( $create_file ) {

                    file_put_contents( $combined_scripts_mifinited_filename, JSMinPlus::minify( $to_write ) );

                }

                /*
                 * Add generated file to the end of the body
                 */
                $html->find( 'body', 0)->innertext .= '<script type="text/javascript" src="' . $combined_scripts_mifinited_file_url . '?ver=' . $this->get_file_last_modified_time( $combined_scripts_mifinited_filename ) . '" defer></script>';

                do_action( 'exopite-combiner-minifier-scripts-after-process' );

                if ( $log ) $time_scripts = number_format( ( microtime(true) - $start_time ), 4 );

            }

            /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
             *                          Styles                         *
            \* * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

            if ( $process_styles == 'yes' ) {

                if ( $log ) $start_time = microtime(true);

                do_action( 'exopite-combiner-minifier-styles-before-process' );

                $to_write = '';

                /*
                 * Set styles file name
                 */
                $combined_styles_file_name = 'styles-combined-' . get_the_ID() . '.css';
                $combined_styles_mifinited_file_url = EXOPITE_COMBINER_MINIFIER_PLUGIN_URL . 'combined/' . $combined_styles_file_name;
                $combined_styles_mifinited_file_url = apply_filters( 'exopite-combiner-minifier-scripts-file-url', $combined_styles_mifinited_file_url );

                $combined_styles_mifinited_filename = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined' . DIRECTORY_SEPARATOR . $combined_styles_file_name;
                $combined_styles_mifinited_filename = apply_filters( 'exopite-combiner-minifier-styles-file-path', $combined_styles_mifinited_filename );

                // Get all styles
                $items = $html->find( 'link[rel=stylesheet]' );

                // Get last "last modified time" from enqueued items
                $last_modified = $this->get_last_modified( $items, 'styles' );

                $create_file = false;

                // If combined and minified files are different then the enqueued files or
                // the last modified time is different or
                // override it via filter
                // then need to regenerate file.
                if ( $this->check_list( $items, 'styles' ) ||
                     $this->check_last_modified_time( $combined_styles_mifinited_filename, $last_modified ) ||
                     apply_filters( 'exopite-combiner-minifier-force-generate-' . $type, false ) ) {

                    $create_file = true;

                }

                // Loop items
                foreach( $items as $item ) {

                    // Get item url and remove attributes.
                    $src = $item->href;
                    $src = strtok( $src, '?' );

                    // Get path from url
                    $path = $this->get_path( $src );

                    // Skip admin styles
                    if ( $this->to_skip( $src, $path, 'styles', $item->media ) )  continue;

                    if ( $create_file ) {

                        if ( file_exists( $path ) ) {

                            /*
                             * Replace all relative url() to absoulte
                             * Need to do this, because our combined css has a different path.
                             * Ignore already absoulte urls, start with "http" and "//",
                             * also ignore "data".
                             */
                            $to_write .= preg_replace_callback(
                                '/url\(\s*[\'"]?\/?(.+?)[\'"]?\s*\)/i',
                                function ( $matches ) use( $src ) {

                                    if ( ! $this->starts_with( $matches[1], 'http' ) &&
                                         ! $this->starts_with( $matches[1], '//' ) &&
                                         ! $this->starts_with( $matches[1], 'data' ) ) {
                                    }
                                    return "url('" . $this->rel2abs( $matches[1], $src ) . "')";
                                },
                                file_get_contents( $path )
                            );

                        }

                    }

                    // Remove processed element from DOM
                    $item->outertext = '';

                }

                // Find inline styles
                $items = $html->find( 'style' );

                // Process only styles assigend for all media or screens
                $allowed_media = array( 'all', 'screen' );

                foreach( $items as $item ) {

                    if ( ! in_array( $item->media, $allowed_media ) ) {

                        // Minify inline style element
                        $item->innertext = CSSMin::minify( $item->innertext );

                        // Remove empty
                        if ( empty( $item->innertext ) ) {
                            $item->outertext = '';
                        }

                        continue;
                    }

                    // Skip admin inline style
                    if ( strpos( $item->innertext, 'margin-top: 32px !important;' ) ) continue;

                    // Add to processing if need to generate file
                    if ( $create_file ) $to_write .= $item->innertext;

                    // Remove them
                    $item->outertext = '';

                }

                /*
                 * Minify combined css
                 * Write it out
                 */
                if ( $create_file ) {

                    file_put_contents( $combined_styles_mifinited_filename, CssMin::minify( $to_write ) );

                }

                // Insert generated the end of the head tag
                $html->find( 'head', 0)->innertext .= '<link rel="stylesheet" href="' . $combined_styles_mifinited_file_url . '?ver=' . $this->get_file_last_modified_time( $combined_styles_mifinited_filename ) . '" type="text/css" media="all" />';

                do_action( 'exopite-combiner-minifier-styles-after-process' );

                if ( $log ) $time_styles = number_format( ( microtime(true) - $start_time ), 4 );

            }

            $content = $html->save();

            $html->clear();
            unset($html);

        }

        if ( $process_html == 'yes' ) {

            if ( $log ) $startTime = microtime(true);

            $content = $this->fn_minify_html( $content );

            if ( $log ) $time_html = number_format( ( microtime(true) - $startTime ), 4 );

        }

        $times = ( ! $log ) ? '' : PHP_EOL
            . '<!-- Exopite Combiner Minifier - JSMinPlus: '. $time_scripts . 's. -->' . PHP_EOL
            . '<!-- Exopite Combiner Minifier - CssMin: '. $time_styles . 's. -->' . PHP_EOL
            . '<!-- Exopite Combiner Minifier - HTML: '. $time_html . 's. -->' . PHP_EOL
            ;

        return $content . $times;

    }

    public function process_html( $content ) {

        if ( is_admin() ) return $content;

        $this->site_url = get_site_url();

        /**
         * PHP Simple HTML DOM Parser
         */
        if( ! class_exists( 'simple_html_dom' ) ) {

            require EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'includes/libraries/simple_html_dom.php';

        }

        if ( apply_filters( 'exopite-combiner-minifier-process-styles', true ) ) {

            $startTime = microtime(true);

            $content = $this->process_scripts_styles( $content );

            $time_scripts_styles = number_format( ( microtime(true) - $startTime ), 4 );

        }

        return $content
            . '<!-- Exopite Combiner Minifier - TOTAL: '. $time_scripts_styles . 's. -->' . PHP_EOL
            ;

    }

    /*************************************************************\
     *                                                           *
     *                       Minify HTML                         *
     *                                                           *
    \*************************************************************/
    /**
     * tovic/php-html-css-js-minifier.php
     * @link https://gist.github.com/tovic/d7b310dea3b33e4732c0
     */
    // escape character
    // define('X', "\x1A");
    // normalize line–break(s)
    public function n($s) {
        return str_replace(["\r\n", "\r"], "\n", $s);
    }

    // trim once
    public function t($a, $b) {
        if ($a && strpos($a, $b) === 0 && substr($a, -strlen($b)) === $b) {
            return substr(substr($a, strlen($b)), 0, -strlen($b));
        }
        return $a;
    }

    public function fn_minify($pattern, $input) {
        return preg_split('#(' . implode('|', $pattern) . ')#', $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    }

    public function fn_minify_html($input, $comment = 2, $quote = 1) {
        if (!is_string($input) || !$input = $this->n(trim($input))) return $input;
        $output = $prev = "";
        // define('MINIFY_COMMENT_CSS', '/\*[\s\S]*?\*/');
        define('MINIFY_STRING', '"(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\'');
        define('MINIFY_COMMENT_HTML', '<!\-{2}[\s\S]*?\-{2}>');
        define('MINIFY_HTML', '<[!/]?[a-zA-Z\d:.-]+[\s\S]*?>');
        define('MINIFY_HTML_ENT', '&(?:[a-zA-Z\d]+|\#\d+|\#x[a-fA-F\d]+);');
        define('MINIFY_HTML_KEEP', '<pre(?:\s[^<>]*?)?>[\s\S]*?</pre>|<code(?:\s[^<>]*?)?>[\s\S]*?</code>|<script(?:\s[^<>]*?)?>[\s\S]*?</script>|<style(?:\s[^<>]*?)?>[\s\S]*?</style>|<textarea(?:\s[^<>]*?)?>[\s\S]*?</textarea>');

        foreach ($this->fn_minify([MINIFY_COMMENT_HTML, MINIFY_HTML_KEEP, MINIFY_HTML, MINIFY_HTML_ENT], $input) as $part) {
            if ($part === "\n") continue;
            if ($part !== ' ' && trim($part) === "" || $comment !== 1 && strpos($part, '<!--') === 0) {
                // Detect IE conditional comment(s) by its closing tag …
                if ($comment === 2 && substr($part, -12) === '<![endif]-->') {
                    $output .= $part;
                }
                continue;
            }
            if ($part[0] === '<' && substr($part, -1) === '>') {
                $output .= $this->fn_minify_html_union($part, $quote);
            } else if ($part[0] === '&' && substr($part, -1) === ';' && $part !== '&lt;' && $part !== '&gt;' && $part !== '&amp;') {
                $output .= html_entity_decode($part); // Evaluate HTML entit(y|ies)
            } else {
                $output .= preg_replace('#\s+#', ' ', $part);
            }
            $prev = $part;
        }
        $output = str_replace(' </', '</', $output);
        // Force space with `&#x0020;` and line–break with `&#x000A;`
        $output = str_ireplace(['&#x0020;', '&#x20;', '&#x000A;', '&#xA;'], [' ', ' ', "\n", "\n"], trim($output));
        // return str_ireplace(['&#x0020;', '&#x20;', '&#x000A;', '&#xA;'], [' ', ' ', "\n", "\n"], trim($output));

        return $output;
    }

    public function fn_minify_html_union($input, $quote) {
        if (
            strpos($input, ' ') === false &&
            strpos($input, "\n") === false &&
            strpos($input, "\t") === false
        ) return $input;
        global $url;
        return preg_replace_callback('#<\s*([^\/\s]+)\s*(?:>|(\s[^<>]+?)\s*>)#', function($m) use($quote, $url) {
            if (isset($m[2])) {
                $a = 'a(sync|uto(focus|play))|c(hecked|ontrols)|d(efer|isabled)|hidden|ismap|loop|multiple|open|re(adonly|quired)|s((cop|elect)ed|pellcheck)';
                $a = '<' . $m[1] . preg_replace([
                    // From `a="a"`, `a='a'`, `a="true"`, `a='true'`, `a=""` and `a=''` to `a` [^1]
                    '#\s(' . $a . ')(?:=([\'"]?)(?:true|\1)?\2)#i',
                    // Remove extra white–space(s) between HTML attribute(s) [^2]
                    '#\s*([^\s=]+?)(=(?:\S+|([\'"]?).*?\3)|$)#',
                    // From `<img />` to `<img/>` [^3]
                    '#\s+\/$#'
                ], [
                    // [^1]
                    ' $1',
                    // [^2]
                    ' $1$2',
                    // [^3]
                    '/'
                ], str_replace("\n", ' ', $m[2])) . '>';
                return $quote !== 1 ? $this->fn_minify_html_union_attr($a) : $a;
            }
            return '<' . $m[1] . '>';
        }, $input);
    }

    public function fn_minify_html_union_attr($input) {
        if (strpos($input, '=') === false) return $input;
        return preg_replace_callback('#=(' . MINIFY_STRING . ')#', function($m) {
            $q = $m[1][0];
            if (strpos($m[1], ' ') === false && preg_match('#^' . $q . '[a-zA-Z_][\w-]*?' . $q . '$#', $m[1])) {
                return '=' . t($m[1], $q);
            }
            return $m[0];
        }, $input);
    }


    /*
     * Delete cache folder via AJAX
     */
    public function delete_cache() {

        if ( ! current_user_can( 'manage_options' ) ) return;

        //The name of the folder.
        $folder = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined';

        //Get a list of all of the file names in the folder.
        $files = glob( $folder . '/*' );

        //Loop through the file list.
        foreach( $files as $file ) {

            //Make sure that this is a file and not a directory.
            if( is_file( $file ) ) {

                //Use the unlink function to delete the file.
                unlink( $file );
            }
        }

        die( 'done' );

    }

}

/*
 * Filters:
 *
 * exopite-combiner-minifier-process-styles                         true
 * exopite-combiner-minifier-process-scripts                        true
 * exopite-combiner-minifier-skip-wp_scripts                        array( 'jquery' )
 * exopite-combiner-minifier-skip-wp_styles                         array()
 * exopite-combiner-minifier-wp_scripts-process-wp_includes         false
 * exopite-combiner-minifier-wp_styles-process-wp_includes          false
 * exopite-combiner-minifier-wp_scripts-ignore-external             true
 * exopite-combiner-minifier-wp_styles-ignore-external              true
 * exopite-combiner-minifier-enqueued-scripts-list
 * exopite-combiner-minifier-enqueued-styles-list
 * exopite-combiner-minifier-enqueued-scripts-contents
 * exopite-combiner-minifier-enqueued-styles-contents
 * exopite-combiner-minifier-scripts-file-path
 * exopite-combiner-minifier-styles-file-path
 * exopite-combiner-minifier-force-generate-scripts
 * exopite-combiner-minifier-force-generate-styles
 * exopite-combiner-minifier-scripts-last-modified
 * exopite-combiner-minifier-styles-last-modified
 * exopite-combiner-minifier-styles-file-url
 * exopite-combiner-minifier-scripts-file-url
 *
 * Actions:
 *
 * exopite-combiner-minifier-styles-before-process
 * exopite-combiner-minifier-styles-after-process
 * exopite-combiner-minifier-scripts-before-process
 * exopite-combiner-minifier-scripts-after-process
 *
 */

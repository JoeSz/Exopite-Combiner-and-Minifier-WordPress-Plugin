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

    /**
     * Store plugin main class to allow public access.
     *
     * @since    20180622
     * @var object      The main class.
     */
    public $main;

    private $site_url;

    public $debug;
    public $showinfo;
    public $options;
    public $options_lists;

    public $js_templates = array();

    public $create_separate_js_files = 'yes';
    public $create_separate_css_files = 'no';

    public $searched_script_types = array(
        'type="text/template"',
        "type=\'text/template\'",
        'type="text/x-template"',
        "type=\'text/x-template\'",
    );

    public $regex_js_template = '/<script(.*?)>([\s\S]*?)<\/script>/i';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_main ) {

        $this->main = $plugin_main;
		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->debug = false;
        $this->showinfo = false;
        // $this->showinfo = true;

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

            /**
             * exopite-combiner-minifier-skip-wp_scripts
             * exopite-combiner-minifier-skip-wp_styles
             */
            switch ( $type ) {

                case 'wp_scripts':
                    $to_skip = apply_filters( 'exopite-combiner-minifier-skip-' . $type, array( 'jquery', 'jquery-core' ) );
                    break;

                case 'wp_styles':
                    $to_skip = apply_filters( 'exopite-combiner-minifier-skip-' . $type, array() );
                    break;

            }

            // Skip jQuery, this is an empty handle, jQuerys handle is jquery-core
            if ( in_array( $handle, $to_skip ) ) continue;

            /**
             * Clean up url, for example wp-content/themes/wdc/main.js?v=1.2.4
             * become wp-content/themes/wdc/main.js
             */
            $src = strtok( ${$type}->registered[$handle]->src, '?' );

            /**
             * To "fix" styles, scripts start with /wp-includes, like jQuery
             * If you activate this, ALL not external will be processed.
             * This can produce error, because some styles, scripts are enqueued
             * very late that we won’t able to catch it earlier and they may have a depency.
             */
            // if ( apply_filters( 'exopite-combiner-minifier-' . $type . '-process-wp_includes', false ) ) {
            //     $wp_content_url = str_replace( $site_url, '', includes_url() );
            //     if ( $this->main->util->starts_with( $src, $wp_content_url ) ) {
            //         $src = $site_url . $src;
            //     }
            // }

            $wp_content_url = str_replace( $site_url, '', includes_url() );
            $src = $this->main->util->normalize_url( $src, $site_url, $wp_content_url, $type );

            /**
             * Skip external resources, plugin and theme author use CDN for a reason.
             */
            if ( apply_filters( 'exopite-combiner-minifier-' . $type . '-ignore-external', true ) ) {

                if ( ! $this->main->util->starts_with( $src, $site_url ) ) continue;

            }

            /**
             * Get path from src
             */
            $path = $this->main->util->get_path( $src );

            if ( $this->main->util->to_skip( $src, $path, $type, '' ) ) {
                continue;
            }

            /**
             * Get last modified item datetime stamp
             */
            $file_last_modified_time = $this->main->util->get_file_last_modified_time( $path );
            if ( ! $script_last_modified || ( $file_last_modified_time && $file_last_modified_time > $script_last_modified ) ) {
                $script_last_modified = $file_last_modified_time;
            }

            switch ( $type ) {
                case 'wp_scripts':
                    // Get wp_localize_script data
                    $data = ( isset( ${$type}->registered[$handle]->extra['data'] ) ) ? ${$type}->registered[$handle]->extra['data'] : '';
                    break;

                case 'wp_styles':
                    // Get wp_add_inline_style data
                    $data = ( isset( ${$type}->registered[$handle]->extra['after'] ) ) ? ${$type}->registered[$handle]->extra['after'] : '';
                    break;
            }

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

        $options = get_option( $this->plugin_name );
        $scripts_try_catch = ( isset( $options['scripts_try_catch'] ) ) ? $options['scripts_try_catch'] : 'yes';
        $result = [];
        $result['data'] = '';
        $result['content'] = '';

        /**
         * Some JavaScript files are broken and cause problems.
         * Better to have a little extra code to prevent this then break JavaScripts.
         * Still small enough.
         */
        $debug_variable = '(e)';
        $debug_function = ( $this->debug ) ? 'console.log(e)' : '';

        if ( $scripts_try_catch == 'yes' ) {
            $before = 'try{';
            $after = '}catch' . $debug_variable . '{' . $debug_function . '};';
        } else {
            $before = $after = '';
        }

        foreach ( $list as $item ) {

            // Process css files
            if ( ! $data_only && substr( $item['path'], strrpos( $item['path'], '.' ) + 1 ) == 'css' ) {

                if ( file_exists( $item['path'] ) ) {

                    /**
                     * Replace all relative url() to absoulte
                     * Need to do this, because our combined css has a different path.
                     * Ignore already absoulte urls, start with "http" and "//",
                     * also ignore "data".
                     */
                    $inline_css = ( isset( $item['data'] ) && ! empty( $item['data'] ) ) ? implode( ';', $item['data'] ) : '';

                    $rel2abs = $this->main->util->normalize_css( file_get_contents( $item['path'] ) . $inline_css, $item['src'] );
                    // $rel2abs = $this->main->util->fix_style_urls( file_get_contents( $item['path'] ) . $inline_css, $item['src'] );

                    // if ( strpos( $rel2abs, '@import' ) ) {

                    //     $rel2abs = $this->main->util->include_css_import( $rel2abs );

                    // }
                    // $rel2abs = $this->main->util->include_css_import( $rel2abs );


                    $result['content'] .= $rel2abs;

                }

            } else {

                /**
                 * Collect "data"
                 */
                if ( isset( $item['data'] ) && ! empty( $item['data'] ) ) {
                    $result['data'] .= $before . $item['data'] . $after;
                }

                if ( ! $data_only && file_exists( $item['path'] ) ) {
                    $file_content = $before . file_get_contents( $item['path'] ) . $after;
                    $result['content'] .= $file_content;
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

        $options = get_option( $this->plugin_name );

        if ( ! isset( $options['combine_only_styles'] ) || $options['combine_only_styles'] == 'no' ) {
            // $css_compressor = new Autoptimize\tubalmartin\CssMin\Minifier;
            // $css_compressor->removeImportantComments();
            // $contents['content'] = $css_compressor->run( $contents['content'] );

            $contents['content'] = $this->main->compressor->css( $contents['content'] );

            // $contents['content'] = CssMin::minify( $contents['content'] );
            // $contents['content'] = ( new Minify\CSS( $contents['content'] ) )->minify();

            echo "<!-- Exopite Combiner and Minifier - minify and write CSS:  " . number_format(( microtime(true) - $startTime), 4) . "s. -->\n";
        } else {
            echo "<!-- Exopite Combiner and Minifier - write CSS:  " . number_format(( microtime(true) - $startTime), 4) . "s. -->\n";
        }

        file_put_contents( $combined_mifinited_filename, apply_filters( 'exopite_combiner_minifier_styles_before_write_to_file', $contents['content'] ) );

    }

    public function minify_scripts( $combined_mifinited_filename, $contents ) {

        $startTime = microtime(true);

        $options = get_option( $this->plugin_name );

        if ( ! isset( $options['combine_only_scripts'] ) || $options['combine_only_scripts'] == 'no' ) {
            $contents['content'] = $this->main->compressor->js( $contents['content'], true, false );
            // $contents['content'] = JSMin::minify( $contents['content'], array('flaggedComments' => false) );
            // $contents['content'] = JSMinPlus::minify( $contents['content'], array('flaggedComments' => false) );
            // $contents['content'] = ( new Minify\JS( $contents['content'] ) )->minify();
            echo "<!-- Exopite Combiner and Minifier - minify and write JavaScript:  " . number_format(( microtime(true) - $startTime), 4) . "s. -->\n";
        } else {
            echo "<!-- Exopite Combiner and Minifier - write JavaScript:  " . number_format(( microtime(true) - $startTime), 4) . "s. -->\n";
        }

        file_put_contents( $combined_mifinited_filename, apply_filters( 'exopite_combiner_minifier_scripts_before_write_to_file', $contents['data'] . $contents['content'] ) );

    }

    /**
     * @param  [string] $type scripts, styles
     */
    public function execute( $type, $combined_file_name ) {

        $wp_type = 'wp_' . $type;

        global ${$wp_type};

        /**
         * Reorder the handles based on its dependency,
         * The result will be saved in the to_do property ($wp_scripts->to_do, $wp_styles->to_do)
         */
        ${$wp_type}->all_deps( ${$wp_type}->queue );

        $list = $this->get_enqueued( ${$wp_type}->to_do, $wp_type );
        $list = apply_filters( 'exopite-combiner-minifier-enqueued-' . $type . '-list', $list );

        /**
         * Check if enqueued list
         */
        $src_list = array();
        foreach ( $list as $value ) {
            $src_list[] = $value['src'];
        }

        /**
         * Set minified and combined file name
         */
        $combined_mifinited_filename = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined' . DIRECTORY_SEPARATOR . $combined_file_name;
        $combined_mifinited_filename = apply_filters( 'exopite-combiner-minifier-' . $type . '-file-path', $combined_mifinited_filename );
        $combined_last_modified_times = $list['last-modified'];

        // Get saved list from plugin options
        $plugin_options = get_option( $this->plugin_name );
        $list_saved = $plugin_options['list-saved-' . $type];

        $list_changed = ( $list_saved != $src_list );

        if ( $list_changed ) {

            // if list has been changed, update plugin options
            $plugin_options['list-saved-' . $type] = $src_list;
            update_option( $this->plugin_name, $plugin_options );

        }

        /**
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
             $this->main->util->check_last_modified_time( $combined_mifinited_filename, $combined_last_modified_times ) ||
             apply_filters( 'exopite-combiner-minifier-force-generate-' . $type, false ) ) {

            $fn = 'minify_' . $type;

            $contents = $this->get_combined( $list );
            $contents = apply_filters( 'exopite-combiner-minifier-enqueued-' . $type . '-contents', $contents );

            $this->{$fn}( $combined_mifinited_filename, $contents );
            $combined_last_modified_times = time();

        }

        /**
         * Remove/denqeue styles which are already processed
         */
        $this->denqueue( $list, $type );

        return apply_filters( 'exopite-combiner-minifier-' . $type . '-last-modified', $combined_last_modified_times );

    }

    public function scripts_handler_infos() {

        if ( $this->showinfo ) {
            echo PHP_EOL . '<!-- Exopite Combiner Minifier - You see thin infos, beacuse the debug mode is true. -->' . PHP_EOL;
            echo '<!-- Exopite Combiner Minifier - Selected methode: 1 -->' . PHP_EOL;
        }

    }

    public function styles_handler() {

        if ( apply_filters( 'exopite-combiner-minifier-process-styles', true ) ) {

            $startTime = microtime(true);

            do_action( 'exopite-combiner-minifier-styles-before-process' );

            $combined_file_name = 'styles-combined.css';
            $combined_last_modified_times = $this->execute( 'styles', $combined_file_name );
            $combined_mifinited_file_url = EXOPITE_COMBINER_MINIFIER_PLUGIN_URL . 'combined/' . $combined_file_name;
            $combined_mifinited_file_url = apply_filters( 'exopite-combiner-minifier-styles-file-url', $combined_mifinited_file_url );

            /**
             * Enqeue combined and minified styles.
             */
            wp_enqueue_style( 'styles-combined', $combined_mifinited_file_url, null, $combined_last_modified_times );

            do_action( 'exopite-combiner-minifier-styles-after-process' );

            $time_styles = number_format( ( microtime(true) - $startTime ), 4 );

            if ( $this->showinfo ) echo '<!-- Exopite Combiner Minifier - Styles total time: '. $time_styles . 's. -->' . PHP_EOL;

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

            /**
             * Enqeue combined and minified scripts with scripts date on the front.
             */
            add_filter('wp_footer', function( $content ) use( $combined_last_modified_times, $combined_mifinited_file_url ) {

                wp_enqueue_script( 'scripts-combined', $combined_mifinited_file_url, array( 'jquery' ), $combined_last_modified_times, true );

            });

            do_action( 'exopite-combiner-minifier-scripts-after-process' );

            $time_scripts = number_format( ( microtime(true) - $startTime ), 4 );

            if ( $this->showinfo ) echo '<!-- Exopite Combiner Minifier - Scripts total time: '. $time_scripts . 's. -->' . PHP_EOL;

        }

    }

    /*************************************************************\
     *                                                           *
     *                         METHOD 2                          *
     *                                                           *
    \*************************************************************/

    public function buffer_start() {

        // Start output buffering with a callback function
        add_filter( 'exopite_ob_status', 'on' );
        ob_start( array( $this, 'process_buffer' ) );

    }

    public function process_buffer( $content ) {

        return apply_filters( 'exopite_ob_content', $content );

    }

    public function buffer_end() {

        // Display buffer
        if ( ob_get_length() ) ob_end_flush();

    }

    /**
     * Check if any source files changed before last combine.
     */
    public function get_last_modified( $items, $type ) {

        $file_last_modified_time = '';
        $script_last_modified = false;

        foreach( $items as $item ) {

            switch ( $type ) {

                case 'scripts':
                    $src = $item->getAttribute("src");
                    break;

                case 'styles':
                    $src = $item->getAttribute("href");
                    break;

            }

            if ( isset( $src ) ) {

                $src = strtok( $src, '?' );

                /**
                 * Get path from src
                 */
                $path = $this->main->util->get_path( $src );

                if ( $this->main->util->to_skip( $src, $path, $type, $item->getAttribute("media") ) ) continue;

                /**
                 * Get last modified item datetime stamp
                 */
                $file_last_modified_time = $this->main->util->get_file_last_modified_time( $path );

                if ( ! $script_last_modified || ( $file_last_modified_time && $file_last_modified_time > $script_last_modified ) ) {
                    $script_last_modified = $file_last_modified_time;
                }

            }

        }

        return $script_last_modified;

    }

    /**
     * - if single file, need to save to options, not in post meta.
     * - this could do for archives too?
     * - save in separate option? -> ( $this->plugin_name . '_lists' )['styles'] -> 'styles', 'scripts', 'generated'
     *   ( '404', 'archive', search' ) <- those have always the same list? I think yes.
     */
    public function is_single_file_to_save( $type ) {

        if (
            ( is_singular() && $type == 'styles' && $this->create_separate_css_files == 'no' ) ||
            ( is_singular() && $type == 'scripts' && $this->create_separate_js_files == 'no' ) ||
            is_404() ||
            ! is_singular()
        ) {
            return true;
        }

        return false;
    }

    public function get_single_file_type( $type ) {

        return $type;

        // if ( is_singular() ) {
        //     return $type;
        // } elseif ( is_404() ) {
        //     return '404_' . $type;
        // } else {
        //     return 'not_singluar_' . $type;
        // }

    }

    /**
     * Check if any source file added or removed since last combine.
     */
    public function check_list( $items, $type ) {

        if ( is_search() ) {
            return false;
        }

        $list_type = false;
        if ( $this->is_single_file_to_save( $type ) ) {

            $list_type = $this->get_single_file_type( $type );
            $list_saved_options = get_option( $this->plugin_name . '_lists' );
            $list_saved = $list_saved_options['_' . $list_type];

        } else {
            $list_saved = get_post_meta( get_the_ID(), $this->plugin_name . '-' . $type, true );
        }

        $list = array();

        foreach( $items as $item ) {

            switch ( $type ) {

                case 'scripts':
                    $src = $item->getAttribute("src");
                    break;

                case 'styles':
                    $src = $item->getAttribute("href");
                    break;

            }

            if ( isset( $src ) && $src ) {

                /**
                 * Get path from src
                 */
                $path = $this->main->util->get_path( $src );
                $src = strtok( $src, '?' );

                if ( $this->main->util->to_skip( $src, $path, $type, $item->getAttribute("media") ) ) continue;

                $list[] = $src;

            }

        }

        /**
         * Sometimes it is empty (if blog or category), in this case should not be emptied.
         * It make no sence.
         */
        if ( $list_saved != $list && ! empty( $list ) ) {

            if ( $list_type !== false ) {

                $list_saved_options['_' . $list_type] = $list;
                $debug_fn = '/logs/exopite-combiner-minifier-test-' . date('Y-m-d') . '.log';
                update_option( $this->plugin_name . '_lists', $list_saved_options );

            } else {
                update_post_meta( get_the_ID(), $this->plugin_name . '-' . $type, $list );
            }

            return true;

        }

        return false;

    }

    public function check_create_file( $id, $type, $fn, $items, $last_modified ) {

        // If combined and minified files are different then the enqueued files or
        // the last modified time is different or
        // override it via filter
        // then need to regenerate file.
        // $array_to_skip = array( '-archives', '-search', '-404' );
        $array_to_skip = array();
        if (
            (
                // $this->check_list( $items, $type )
                $this->check_list( $items, $type ) ||
                $this->main->util->check_last_modified_time( $fn, $last_modified )
            ) &&
            ! in_array( $id, $array_to_skip )
        ) {

            return true;

        }

        return false;

    }

    public function process_scripts( $content, $html, $xpath ) {

        $process_scripts = ( isset( $this->options['process_scripts'] ) ) ? $this->options['process_scripts'] : 'no';
        $process_inline_scripts = ( isset( $this->options['process_inline_scripts'] ) ) ? $this->options['process_inline_scripts'] : 'no';
        $combine_only_scripts = ( isset( $this->options['combine_only_scripts'] ) ) ? $this->options['combine_only_scripts'] : 'no';
        $scripts_try_catch = ( isset( $this->options['scripts_try_catch'] ) ) ? $this->options['scripts_try_catch'] : 'yes';
        $this->create_separate_js_files = ( isset( $this->options['create_separate_js_files'] ) ) ? $this->options['create_separate_js_files'] : 'yes';
        $this->create_separate_js_files = apply_filters( 'exopite-combiner-minifier-scripts-create-separate-files', $this->create_separate_js_files );

        $process_scripts = apply_filters( 'exopite-combiner-minifier-process-scripts', $process_scripts );
        $process_inline_scripts = apply_filters( 'exopite-combiner-minifier-process-inline-scripts', $process_inline_scripts );

        if ( $process_scripts == 'yes' && apply_filters( 'exopite-combiner-minifier-process-scripts', true ) ) {

            if ( $this->debug ) $start_time = microtime(true);

            do_action( 'exopite-combiner-minifier-scripts-before-process' );

            $to_write = '';

            /**
             * Set script file name
             */
            $id = '';
            if ( $this->is_single_file_to_save( 'scripts' ) === false ) {
                    $id = $this->get_type_name();

                    if ( empty( $id ) ) {
                        return $content;
                    }
            }

            $combined_scripts_file_name = 'scripts-combined' . $id . '.js';
            $combined_scripts_mifinited_file_url = EXOPITE_COMBINER_MINIFIER_PLUGIN_URL . 'combined/' . $combined_scripts_file_name;
            $combined_scripts_mifinited_file_url = apply_filters( 'exopite-combiner-minifier-scripts-file-url', $combined_scripts_mifinited_file_url );

            $combined_scripts_mifinited_filename = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined' . DIRECTORY_SEPARATOR . $combined_scripts_file_name;
            $combined_scripts_mifinited_filename = apply_filters( 'exopite-combiner-minifier-scripts-file-path', $combined_scripts_mifinited_filename );

            $site_url = get_site_url();
            $wp_content_url = str_replace( $site_url, '', includes_url() );

            $items_all = $xpath->query("*/script");
            $items = array();

            foreach( $items_all as $item ) {

                // Get item src.
                $src = $item->getAttribute( 'src' );

                if ( ! empty( $src ) ) {

                    // Remove src attributes.
                    $src = strtok( $src, '?' );

                    /**
                     * To "fix" styles, scripts start with /wp-includes, like jQuery
                     * If you activate this, ALL not external will be processed.
                     * This can produce error, because some styles, scripts are enqueued
                     * very late that we won’t able to catch it earlier and they may have a depency.
                     */
                    // if ( apply_filters( 'exopite-combiner-minifier-scripts-process-wp_includes', false ) ) {
                    //     if ( $this->main->util->starts_with( $src, $wp_content_url ) ) {
                    //         $src = $site_url . $src;
                    //     }
                    // }
                    $src = $this->main->util->normalize_url( $src, $site_url, $wp_content_url, 'scripts' );


                    /**
                     * Skip external resources, plugin and theme author use CDN for a reason.
                     */
                    if ( apply_filters( 'exopite-combiner-minifier-scripts-ignore-external', true ) ) {

                        if ( ! $this->main->util->starts_with( $src, $site_url ) ) continue;

                    }

                    // Get path from url
                    $path = $this->main->util->get_path( $src );

                    // Skip admin scripts, jQuery, ...
                    if ( $this->main->util->to_skip( $src, $path, 'scripts' ) ) {
                        continue;
                    }

                    if ( ! file_exists( $path ) ) continue;

                }

                $items[] = $item;

            }

            if ( empty( $items ) ) return $content;

            $last_modified = $this->get_last_modified( $items, 'scripts' );

            $create_file = apply_filters(
                'exopite-combiner-minifier-force-generate-scripts',
                $this->check_create_file( $id, 'scripts', $combined_scripts_mifinited_filename, $items, $last_modified )
            );

            /**
             * Some JavaScript files are broken and cause problems.
             * Better to have a little extra code to prevent this then break JavaScripts.
             * Still small enough.
             */
            $debug_variable = '(e)';
            $debug_function = ( $this->debug ) ? 'console.log(e)' : '';

            if ( $scripts_try_catch == 'yes' ) {
                $before = 'try{';
                $after = '}catch' . $debug_variable . '{' . $debug_function . '};';
            } else {
                $before = $after = '';
            }

            if ( is_null( $items ) ) return $content;

            foreach( $items as $item ) {

                $process = true;

                // Get item src.
                $src = $item->getAttribute( 'src' );

                /**
                 * If item has src then get file content
                 * if not, get inline scripts
                 */
                if ( ! empty( $src ) ) {

                    // Remove src attributes.
                    $src = strtok( $src, '?' );

                    /**
                     * To "fix" styles, scripts start with /wp-includes, like jQuery
                     * If you activate this, ALL not external will be processed.
                     * This can produce error, because some styles, scripts are enqueued
                     * very late that we won’t able to catch it earlier and they may have a depency.
                     */
                    // if ( apply_filters( 'exopite-combiner-minifier-scripts-process-wp_includes', false ) ) {
                    //     if ( $this->main->util->starts_with( $src, $wp_content_url ) ) {
                    //         $src = $site_url . $src;
                    //     }
                    // }
                    $src = $this->main->util->normalize_url( $src, $site_url, $wp_content_url, 'scripts' );

                    // Get path from url
                    $path = $this->main->util->get_path( $src );

                    /**
                     * Minify file induvidually, because some large file cause problems.
                     * Still fast enough.
                     */
                    if ( $create_file ) {

                        $js_file_content = file_get_contents( $path );

                        // Append a semicolon at the end of js files if it's missing.
                        // $last_char = substr( $js_file_content, -1, 1 );
                        // if ( ';' !== $last_char && '}' !== $last_char ) {
                        //     $js_file_content .= ';';
                        // }

                        /**
                         * Removing source map URLs in js files to avoid catastrophic breaking.
                         *
                         * @link https://stackoverflow.com/questions/36629224/how-to-improve-regex-for-removing-source-map-urls-in-js-files-to-avoid-catastrop
                         */
                        // $regex_source_mapping = '~//[#@]\s(source(?:Mapping)?URL)=\s*(\S+)~';
                        // if ( preg_match( $regex_source_mapping , $js_file_content ) ) {
                        //     $js_file_content = preg_replace( $regex_source_mapping, '', $js_file_content );
                        // }

                        if ( $combine_only_scripts == 'no' && false === ( strpos( $path, 'min.js' ) ) ) {

                            $js_file_content = $this->main->compressor->js( $js_file_content, true, true );
                            // $js_file_content = JSMin::minify( $js_file_content );
                            // $js_file_content = ( new Minify\JS( $js_file_content ) )->minify();

                        }

                        $to_write .= $before . $js_file_content . $after;

                    }

                } elseif ( $process_inline_scripts == 'yes' ) {

                    $type = $item->getAttribute( 'type' );

                    /**
                     * Do not process inline JavaScript if contain "<![CDATA["
                     * Process inline script without type or 'text/javascript' type
                     */
                    $process = (
                        ( strpos( $item->textContent, "woocommerce" ) === false ) &&
                        ( strpos( $item->textContent, "<![CDATA[" ) === false ) &&
                        ( ! isset( $type ) || empty( $type ) || $type == 'text/javascript' )
                    );

                    /**
                     * In place minification.
                     * This will leave script in place, only minify them.
                     * Still dangerous!
                     */
                    if ( $process ) {

                        $item->nodeValue = $this->main->compressor->js( $item->textContent, false, true );
                        // $item->nodeValue = JSMin::minify( $item->textContent );
                        // $item->nodeValue = ( new Minify\JS( $item->textContent ) )->minify();

                    }

                    /**
                     * This will insert inline <script> element values to combined file and remove them.
                     */
                    // if ( $create_file && $process ) {

                    //     if ( $combine_only_scripts == 'no' ) {
                    //         $to_write .= $before . JSMin::minify( $item->textContent ) . $after;
                    //         // $to_write .= $before . ( new Minify\JS( $item->textContent ) )->minify() . $after;
                    //     } else {
                    //         $to_write .= $before . $item->textContent . $after;
                    //     }
                    // }

                    // file_put_contents( EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . '/scripts.log', PHP_EOL, FILE_APPEND );

                }

                if ( ! empty( $src ) ) {
                /**
                 *  this will remove inline <script> elements too.
                 */
                // if ( ! empty( $src ) || ( empty( $src ) && $process_inline_scripts == 'yes' ) ) {

                    // Remove processed
                    $item->parentNode->removeChild( $item );

                }

            }

            if ( $create_file ) {
                file_put_contents( $combined_scripts_mifinited_filename, apply_filters( 'exopite_combiner_minifier_scripts_before_write_to_file', $to_write ) );
            }

            $head = $html->getElementsByTagName('head')->item(0);

                $script_url = $combined_scripts_mifinited_file_url . '?ver=' . $this->main->util->get_file_last_modified_time( $combined_scripts_mifinited_filename );
            /**
             * Preload
             *
             * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
             */
            $preload = $html->createElement('link');
            $preload->setAttribute( 'rel', 'preload' );
            // $preload->setAttribute( 'crossorigin', 'anonymous' );
            $preload->setAttribute( 'href', $script_url );
            $preload->setAttribute( 'as', 'script' );
            if ( ! is_null( $head ) ) $head->appendChild( $preload );

            /**
             * Add generated file to the end of the body.
             * I'm not sure, defer is relevant here, it is relevant only if scripts is in the header.
             * I can not insert ot header, because of the inline scripts in the page, which are inserted
             * by other pugins.
             */
            $body = $html->getElementsByTagName('body')->item(0);
            $script = $html->createElement('script');
            $script->setAttribute( 'type', 'text/javascript' );
            $script->setAttribute( 'src', $script_url );
            // $script->setAttribute( 'defer', 'defer' );
            if ( ! is_null( $body ) ) $body->appendChild( $script );

            $content = $html->saveHTML();

            do_action( 'exopite-combiner-minifier-scripts-after-process' );

        }

        return $content;

    }

    public function get_type_name() {

        if ( is_home() ) {
            return '-blog';
        } elseif ( is_archive() ) {

            $post_type_slug = get_queried_object()->name;

            if ( ! empty( $post_type_slug ) ) {
                $post_type_slug = '-' . $post_type_slug;
                return $post_type_slug . '-archive';
            } else {
                return '-archives';
            }

        } elseif ( is_search() ) {
            return '-search';
        } elseif ( is_404() ) {
            return '-404';
        } elseif ( is_singular() ) {
            return '-' . get_the_ID();
        }

    }

    public function process_styles( $content, $html, $xpath ) {

        $process_styles = ( isset( $this->options['process_styles'] ) ) ? $this->options['process_styles'] : 'no';

        $process_styles = apply_filters( 'exopite-combiner-minifier-process-styles', $process_styles );

        $generate_head_styles = ( isset( $this->options['generate_head_styles'] ) ) ? $this->options['generate_head_styles'] : 'no';
        $combine_only_styles = ( isset( $this->options['combine_only_styles'] ) ) ? $this->options['combine_only_styles'] : 'no';
        $enqueue_head_styles = ( isset( $this->options['enqueue_head_styles'] ) ) ? $this->options['enqueue_head_styles'] : 'no';
        $this->create_separate_css_files = ( isset( $this->options['create_separate_css_files'] ) ) ? $this->options['create_separate_css_files'] : 'yes';
        $this->create_separate_css_files = apply_filters( 'exopite-combiner-minifier-styles-create-separate-files', $this->create_separate_css_files );

        if ( $process_styles == 'yes' && apply_filters( 'exopite-combiner-minifier-process-styles', true ) ) {

            do_action( 'exopite-combiner-minifier-styles-before-process' );

            $to_write = '';

            /**
             * Set styles file name
             */
            $id = '';
            if ( $this->is_single_file_to_save( 'styles' ) === false ) {
                $id = $this->get_type_name();

                if ( empty( $id ) ) {
                    return $content;
                }
            }

            $combined_styles_file_name = 'styles-combined' . $id . '.css';
            $combined_styles_mifinited_file_url = EXOPITE_COMBINER_MINIFIER_PLUGIN_URL . 'combined/' . $combined_styles_file_name;
            $combined_styles_mifinited_file_url = apply_filters( 'exopite-combiner-minifier-styles-file-url', $combined_styles_mifinited_file_url );
            $combined_styles_mifinited_filename = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined' . DIRECTORY_SEPARATOR . $combined_styles_file_name;
            $combined_styles_mifinited_filename = apply_filters( 'exopite-combiner-minifier-styles-file-path', $combined_styles_mifinited_filename );

            $site_url = get_site_url();
            $wp_content_url = str_replace( $site_url, '', includes_url() );

            /**
             * Preprocess, make sure check_create_file() has the list to work on.
             * If we do not do this, then some js/css files like "admin-bar.css", etc... make css/js always recreate.
             * (different list if logged in and not logged in)
             * This made some check run twice, not ideal, maybe possible somehow to refactor?
             */
            $items_all = $xpath->query("*/link[@rel='stylesheet']");
            $items = array();
            foreach( $items_all as $item ) {

                // remove external, and to ignore

                // Get item url and remove attributes.
                $src = $item->getAttribute("href");
                $src = strtok( $src, '?' );

                /**
                 * To "fix" styles, scripts start with /wp-includes, like jQuery
                 * If you activate this, ALL not external will be processed.
                 * This can produce error, because some styles, scripts are enqueued
                 * very late that we won’t able to catch it earlier and they may have a depency.
                 */
                // if ( apply_filters( 'exopite-combiner-minifier-styles-process-wp_includes', false ) ) {
                //     if ( $this->main->util->starts_with( $src, $wp_content_url ) ) {
                //         $src = $site_url . $src;
                //     }
                // }
                $src = $this->main->util->normalize_url( $src, $site_url, $wp_content_url, 'styles' );

                /**
                 * Skip external resources, plugin and theme author use CDN for a reason.
                 */
                if ( apply_filters( 'exopite-combiner-minifier-styles-ignore-external', true ) ) {

                    if ( ! $this->main->util->starts_with( $src, $site_url ) ) continue;

                }

                // Get path from url
                $path = $this->main->util->get_path( $src );

                $media = $item->getAttribute("media");

                // Skip admin styles
                if ( $this->main->util->to_skip( $src, $path, 'styles', $media ) )  continue;

                $items[] = $item;

            }

            if ( empty( $items ) ) return $content;

            /**
             * first need to filter
             */
            $last_modified = $this->get_last_modified( $items, 'styles' );

            $create_file = apply_filters(
                'exopite-combiner-minifier-force-generate-styles',
                $this->check_create_file( $id, 'styles', $combined_styles_mifinited_filename, $items, $last_modified )
            );

            // $css_compressor = new Autoptimize\tubalmartin\CssMin\Minifier;
            // $css_compressor->removeImportantComments();

            // Loop items
            foreach( $items as $item ) {

                // Get item url and remove attributes.
                $src = $item->getAttribute("href");
                $src = strtok( $src, '?' );

                /**
                 * To "fix" styles, scripts start with /wp-includes, like jQuery
                 * If you activate this, ALL not external will be processed.
                 * This can produce error, because some styles, scripts are enqueued
                 * very late that we won’t able to catch it earlier and they may have a depency.
                 */
                // if ( apply_filters( 'exopite-combiner-minifier-styles-process-wp_includes', false ) ) {
                //     if ( $this->main->util->starts_with( $src, $wp_content_url ) ) {
                //         $src = $site_url . $src;
                //     }
                // }
                $src = $this->main->util->normalize_url( $src, $site_url, $wp_content_url, 'styles' );

                // Get path from url
                $path = $this->main->util->get_path( $src );

                if ( $generate_head_styles == 'yes' || $create_file ) {

                    if ( file_exists( $path ) ) {

                        /**
                         * Replace all relative url() to absoulte
                         * Need to do this, because our combined css has a different path.
                         * Ignore already absoulte urls, start with "http" and "//",
                         * also ignore "data".
                         */
                        $converted_css = $this->main->util->normalize_css( file_get_contents( $path ), $src );
                        // $converted_css = $this->main->util->fix_style_urls( file_get_contents( $path ), $src );

                        /**
                         * Need to check import and if any then insert.
                         *
                         * Possible variants:
                         * @link https://developer.mozilla.org/en-US/docs/Web/CSS/@import
                         * Also in url without ' or ".
                         */
                        /**
                         * '/@import.*?["\']([^"\']+)["\'].*?(\r\n|\r|\n|;)/i'
                         * '/@import.*?url\(([^"|^'].+)\).*?(\r\n|\r|\n|;)/i'
                         *
                         * '/(@import.*?["\']([^"\']+)["\'].*?(\r\n|\r|\n|;)|@import.*?url\(([^"|^'].+)\).*?(\r\n|\r|\n|;))/i'
                         * @import.*?(["\']([^"\']+)["\'].*?(\r\n|\r|\n|;)|url\(([^"|^'].+)\).*?(\r\n|\r|\n|;))
                         */
                        // if ( strpos( $converted_css, '@import' ) ) {

                        //     $converted_css = $this->main->util->include_css_import( $converted_css );

                        // }
                        // $converted_css = $this->main->util->include_css_import( $converted_css );


                        if ( $combine_only_styles == 'no' && false === ( strpos( $path, 'min.css' ) ) ) {

                            $to_write .= $this->main->compressor->css( $converted_css );
                            // $to_write .= $css_compressor->run( $converted_css );

                        } else {
                            $to_write .= $converted_css;
                        }

                    }

                }

                /**
                 * Remove processed element from DOM
                 *
                 * https://stackoverflow.com/questions/15272726/how-to-delete-element-with-domdocument
                 */
                $item->parentNode->removeChild( $item );

            }

            /**
             * Process inline <styles> elements.
             */
            // $items = $html->getElementsByTagName('style');
            $items = $xpath->query("*/style");

            /**
             * If remove and include in combined file.
             */
            // Process only styles assigend for all media or screens
            // $allowed_media = array( 'all', 'screen' );

            foreach( $items as $item ) {

                /**
                 * If remove and include in combined file.
                 */
                // $media = $item->getAttribute("media");
                // if ( empty( $media ) || in_array( $media, $allowed_media ) ) {

                //     $inner_text = $item->textContent;

                //     if ( $combine_only_styles == 'no' ) {
                //         // Minify inline style element

                //         /**
                //          * If remove and include in combined file.
                //          */
                //         $inner_text .= $css_compressor->run( $item->textContent );
                //         // $inner_text .= ( new Minify\CSS( $item->textContent ) )->minify();
                //     }

                //     // Remove empty
                //     if ( empty( $item->textContent ) ) {
                //         $item->parentNode->removeChild( $item );
                //     }

                //     continue;
                // }

                if ( $combine_only_styles == 'no' ) {
                    // Minify inline style element
                    $item->textContent = $this->main->compressor->css( $item->textContent );
                    // $item->textContent = $css_compressor->run( $item->textContent );
                    // $item->innertext = ( new Minify\CSS( $item->textContent ) )->minify();
                }

                // Remove empty
                if ( empty( $item->textContent ) ) {
                    $item->parentNode->removeChild( $item );
                }

                continue;

                /**
                 * If remove and include in combined file.
                 */
                // Skip admin inline style
                // if ( strpos( $item->textContent, 'margin-top: 32px !important;' ) ) continue;

                // Add to processing if need to generate file
                // if ( $create_file ) $to_write .= $inner_text;

                // Remove them
                // $item->parentNode->removeChild( $item );
                // $item->outertext = '';

            }

            if ( $generate_head_styles == 'yes' ) {

                // $html->find( 'head', 0)->innertext .= '<style type="text/css" media="all">' . $to_write .  '</style>';

                $head = $html->getElementsByTagName('head')->item(0);
                $style = $html->createElement('style');
                $style->setAttribute( 'type', 'text/css' );
                $style->setAttribute( 'media', 'all' );
                $style->textContent = $to_write;
                $head->appendChild( $style );

            } else {
                /**
                 * ToDos:
                 * - file.css?ver=34535234
                 * - file-hash.css
                 * - put in cache dir?
                 * - add .htaccess to cache folder for gzip?
                 */
                /**
                 * Minify combined css
                 * Write it out
                 */
                if ( $create_file ) {

                    // file_put_contents( $combined_styles_mifinited_filename, gzencode( $to_write, 9, FORCE_GZIP ) );
                    file_put_contents( $combined_styles_mifinited_filename, apply_filters( 'exopite_combiner_minifier_styles_before_write_to_file', $to_write ) );

                }

                $style_url = $combined_styles_mifinited_file_url . '?ver=' . $this->main->util->get_file_last_modified_time( $combined_styles_mifinited_filename );

                $head = $html->getElementsByTagName('head')->item(0);

                $link = $html->createElement('link');
                $link->setAttribute( 'href', $style_url );
                // $link->setAttribute( 'href', $combined_styles_mifinited_file_url . '?ver=' . hash('md5', $this->main->util->get_file_last_modified_time( $combined_styles_mifinited_filename ) ) );
                $link->setAttribute( 'rel', 'stylesheet' );
                $link->setAttribute( 'type', 'text/css' );
                $link->setAttribute( 'media', 'all' );

                if ( $enqueue_head_styles === 'yes' ) {

                    $head->appendChild( $link );

                } else {

                    $preload = $html->createElement('link');
                    $preload->setAttribute( 'rel', 'preload' );
                    // $preload->setAttribute( 'crossorigin', 'anonymous' );
                    $preload->setAttribute( 'href', $style_url );
                    $preload->setAttribute( 'as', 'style' );
                    if ( ! is_null( $head ) ) $head->appendChild( $preload );

                    $body = $html->getElementsByTagName('body')->item(0);
                    if ( ! is_null( $body ) ) $body->appendChild( $link );

                }



            }

            $content = $html->saveHTML();

            do_action( 'exopite-combiner-minifier-styles-after-process' );

            if ( $this->debug ) $time_styles = number_format( ( microtime(true) - $start_time ), 4 );

        }

        return $content;

    }

    public function remove_script_templates( $content ) {

        /**
         * get script with type text/template and text/x-template with regex
         * save tags content in an array
         * remove tags content
         *
         * https://stackoverflow.com/questions/43495620/php-how-to-match-all-script-tags-except-with-specific-type
         */

        return preg_replace_callback(
            $this->regex_js_template,
            function ( $matches ) use ( &$index ) {

                if ( $this->strpos_array( $matches[1], $this->searched_script_types ) ) {

                    if ( isset( $matches[2] ) ) {
                        $this->js_templates[] = $matches[2];
                    }
                    $script_item = '<script' . $matches[1] . '>TEMPLATE</script>';
                    return $script_item;

                } else {
                    return $matches[0];
                }

            },
            $content
        );

    }

    public function add_script_templates_back( $content ) {

        $index = 0;

        return preg_replace_callback(
            $this->regex_js_template,
            function ( $matches ) use ( &$index ) {

                if ( $this->strpos_array( $matches[1], $this->searched_script_types ) ) {

                    $retval = '<script' . $matches[1] . '>' . $this->js_templates[$index] . '</script>';
                    $index++;
                    return $retval;

                } else {
                    return $matches[0];
                }

            },
            $content
        );

    }

    public function strpos_array( $haystack, $needle ) {
        if ( ! is_array( $needle ) ) {
            $needle = array( $needle );
        }

        foreach ( $needle as $what ) {
            if ( ( $pos = strpos( $haystack, $what ) ) !== false ) {
                return $pos;
            }

        }
        return false;
    }


    public function process_scripts_styles( $content ) {

        if ( $this->showinfo ) $remove_script_templates = 'NaN ';
        if ( $this->showinfo ) $remove_script_templates_back = 'NaN ';
        if ( $this->showinfo ) $time_scripts = 'NaN ';
        if ( $this->showinfo ) $time_styles = 'NaN ';
        if ( $this->showinfo ) $time_html = 'NaN ';

        /**
         * DomDocument sometimes remove some elements from <script type="text/template">
         * Remove all template content and add back after processing JSs and CSSs.
         */
        if ( $this->showinfo ) $start_time = microtime(true);

        $content = $this->remove_script_templates( $content );

        if ( $this->showinfo ) $remove_script_templates = number_format( ( microtime(true) - $start_time ), 4 );

        $process_scripts = ( isset( $this->options['process_scripts'] ) ) ? $this->options['process_scripts'] : 'no';
        $process_styles = ( isset( $this->options['process_styles'] ) ) ? $this->options['process_styles'] : 'no';
        $combine_only_scripts = ( isset( $this->options['combine_only_scripts'] ) ) ? $this->options['combine_only_scripts'] : 'no';
        $combine_only_styles = ( isset( $this->options['combine_only_styles'] ) ) ? $this->options['combine_only_styles'] : 'no';
        $process_html = ( isset( $this->options['process_html'] ) ) ? $this->options['process_html'] : 'no';

        if ( $process_scripts == 'yes' || $process_styles == 'yes' ) {

            /**
             * Swich to DomDocument for performance gain.
             *
             * Other parsers:
             * @link https://stackoverflow.com/questions/3577641/how-do-you-parse-and-process-html-xml-in-php/3577662#3577662
             */
            $html = new DOMDocument();

            /**
             * Convert all the non-ascii characters to html entities before loading the html.
             *
             * @link https://stackoverflow.com/questions/2236889/why-does-dom-change-encoding/2238149#2238149
             */
            if ( mb_detect_encoding( $content, 'UTF-8' ) !== 'UTF-8' ) {
                $content = mb_convert_encoding( $content, 'HTML-ENTITIES', "UTF-8" );
            }

            @$html->loadHTML( $content );
            // $html->formatOutput = true;
            // $html->preserveWhiteSpace = false;

            // This not allow remove element.
            // $items = $html->getElementsByTagName( 'script' );
            $xpath = new DOMXpath( $html );

            /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
             *                          Styles                         *
            \* * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

            if ( $this->showinfo ) $start_time = microtime(true);

            /**
             * Filter to disable process styles. Eg.: on certain pages.
             */
            if ( apply_filters( 'exopite-combiner-minifier-process-styles', true ) ) {
                $content = $this->process_styles( $content, $html, $xpath );
            }

            if ( $this->showinfo ) $time_styles = number_format( ( microtime(true) - $start_time ), 4 );

            /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
             *                          Scripts                        *
            \* * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

            if ( $this->showinfo ) $start_time = microtime(true);

            if ( apply_filters( 'exopite-combiner-minifier-process-scripts', true ) ) {
                $content = $this->process_scripts( $content, $html, $xpath );
            }

            if ( $this->showinfo ) $time_scripts = number_format( ( microtime(true) - $start_time ), 4 );

        }

        if ( apply_filters( 'exopite-combiner-minifier-process-html', $process_html ) == 'yes' ) {

            if ( $this->showinfo ) $startTime = microtime(true);

            // Preparing options for Minify_HTML.
            // $options = array();
            // $content = Minify_HTML::minify( $content, $options );
            $content = $this->main->compressor->html( $content, array() );

            if ( $this->showinfo ) $time_html = number_format( ( microtime(true) - $startTime ), 4 );

        }

        if ( $this->showinfo && ( $process_scripts == 'yes' || $process_styles == 'yes' || $process_html == 'yes' ) ) {

            if ( $process_scripts == 'yes' ) {
                $this->debug_infos .= ( $process_scripts == 'yes' && $combine_only_scripts == 'no' ) ?  '<!-- Exopite Combiner Minifier - JavaScript: '. $time_scripts . 's. -->' : '<!-- Exopite Combiner Minifier - Combine JavaScript: '. $time_scripts . 's. -->';
                $this->debug_infos .= PHP_EOL;
            }

            if ( $process_styles == 'yes' ) {
                $this->debug_infos .= ( $process_styles == 'yes' && $combine_only_styles == 'no' ) ?  '<!-- Exopite Combiner Minifier - CSS: '. $time_styles . 's. -->' : '<!-- Exopite Combiner Minifier - Combine styles: '. $time_styles . 's. -->';
                $this->debug_infos .= PHP_EOL;
            }

            if ( $process_html == 'yes' ) {
                $this->debug_infos .= '<!-- Exopite Combiner Minifier - Minify inline HTML: '. $time_html . 's. -->' . PHP_EOL;
            }

            // $content .= $times;
        }

        if ( ! empty( $this->js_templates ) ) {

            if ( $this->showinfo ) $start_time = microtime(true);

            $content = $this->add_script_templates_back( $content );

            if ( $this->showinfo ) $remove_script_templates_back = number_format( ( microtime(true) - $start_time ), 4 );

        }

        if( $this->showinfo ) {

            $this->debug_infos .= '<!-- Exopite Combiner Minifier - Templates amount: ' . count( $this->js_templates ) . ' -->' . PHP_EOL;

            if ( ! empty( $this->js_templates ) ) {
                $this->debug_infos .= '<!-- Exopite Combiner Minifier - Remove script templates: '. $remove_script_templates . 's. -->' . PHP_EOL;
                $this->debug_infos .= '<!-- Exopite Combiner Minifier - Put script templates back '. $remove_script_templates_back . 's. -->' . PHP_EOL;
            }

        }

        return $content;

    }

    //https://stackoverflow.com/questions/5266945/wordpress-how-detect-if-current-page-is-the-login-page/5892694#5892694
    public function is_login_page() {

        return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );

    }

    public $debug_infos = PHP_EOL;

    public function process_html( $content ) {

        if ( is_admin() || ( ! is_singular() && ! is_archive() && ! is_404() ) ) {
            return $content;
        }

        $already_rant = apply_filters( 'exopite_combiner_minifier_rant', false );
        if ( $already_rant == true ) {
            if ( $this->showinfo ) $content .= PHP_EOL . '<!-- Exopite Combiner Minifier - already rant! -->';
            return $content;
        }

        if ( $this->showinfo ) $this->debug_infos = '<!-- Exopite Combiner Minifier - You see thin infos, beacuse the debug mode is true. -->' . PHP_EOL;
        if ( $this->showinfo ) $this->debug_infos .= '<!-- Exopite Combiner Minifier - Selected methode: 2 - Time: ' . date( 'Y-m-d H:i:s').substr( fmod( microtime( true ), 1), 1 ) . ' -->' . PHP_EOL;

        // if ( is_admin() || $this->is_login_page() || is_robots() ) return $content;
        // if( ( isset( $_SERVER["REQUEST_URI"] ) && substr( $_SERVER["REQUEST_URI"], -4 ) === '.xml' ) ) return $content;

        $this->options = get_option( $this->plugin_name );
        $this->options_lists = get_option( $this->plugin_name . '_lists' );
        $this->site_url = get_site_url();

        if ( apply_filters( 'exopite-combiner-minifier-process-scripts-styles', true ) ) {

            add_filter( 'exopite_combiner_minifier_rant', '__return_true' );

            $startTime = microtime(true);

            $content = $this->process_scripts_styles( $content );

            $time_scripts_styles = number_format( ( microtime(true) - $startTime ), 4 );

        }

        if ( $this->showinfo ) $this->debug_infos .= '<!-- Exopite Combiner Minifier - TOTAL: '. $time_scripts_styles . 's. -->';

        if ( $this->showinfo ) $content .= $this->debug_infos;

        return $content;

    }

    /**
     * Delete cache folder via AJAX
     */
    public function delete_cache() {

        if ( ! current_user_can( 'manage_options' ) ) return;

        //The name of the folder.
        $folder = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined';

        //Get a list of all of the file names in the folder.
        $files = glob( $folder . '/**' );

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

/**
 * Filters:
 *
 * exopite-combiner-minifier-process-styles                         true
 * exopite-combiner-minifier-process-scripts                        true
 * exopite-combiner-minifier-process-inline-scripts
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
 * exopite_combiner_minifier_styles_before_write_to_file
 * exopite_combiner_minifier_scripts_before_write_to_file
 *
 * Actions:
 *
 * exopite-combiner-minifier-styles-before-process
 * exopite-combiner-minifier-styles-after-process
 * exopite-combiner-minifier-scripts-before-process
 * exopite-combiner-minifier-scripts-after-process
 *
 */

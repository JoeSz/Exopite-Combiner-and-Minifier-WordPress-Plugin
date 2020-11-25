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

// use MatthiasMullie\Minify;
// use MatthiasMullie\PathConverter\Converter;

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

    // from autoptimize
    public $dont_move = array(
        'document.write','html5.js','show_ads.js','google_ad','histats.com/js','statcounter.com/counter/counter.js',
        'ws.amazon.com/widgets','media.fastclick.net','/ads/','comment-form-quicktags/quicktags.php','edToolbar',
        'intensedebate.com','scripts.chitika.net/','_gaq.push','jotform.com/','admin-bar.min.js','GoogleAnalyticsObject',
        'plupload.full.min.js','syntaxhighlighter','adsbygoogle','gist.github.com','_stq','nonce','post_id','data-noptimize'
        ,'logHuman','dontmove'
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

    function ends_with( $haystack, $needle ) {
        $length = strlen( $needle );
        if ( $length == 0 ) return true;
        return ( substr( $haystack, -$length ) === $needle );
    }

    /**
     * Get path from url
     * Only work with local urls.
     */
    public function get_path( $url = '' ) {

        // Compatibility for local urls start with //
        $site_url = site_url();
        if ( $this->starts_with( $url, '//' ) ) {
            $site_url = strstr( $site_url, '//' );
        }

        $path = str_replace(
            $site_url,
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

        if ( strpos( $rel, 'data:') === 0 ) {
           return $rel;
        }

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

        /** replace '//' or '/./' or '/foo/../' with '/' */
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
            if ( apply_filters( 'exopite-combiner-minifier-' . $type . '-process-wp_includes', false ) ) {
                $wp_content_url = str_replace( $site_url, '', includes_url() );
                if ( $this->starts_with( $src, $wp_content_url ) ) {
                    $src = $site_url . $src;
                }
            }

            /**
             * Skip external resources, plugin and theme author use CDN for a reason.
             */
            if ( apply_filters( 'exopite-combiner-minifier-' . $type . '-ignore-external', true ) ) {

                if ( ! $this->starts_with( $src, $site_url ) ) continue;

            }

            /**
             * Get path from src
             */
            $path = $this->get_path( $src );

            if ( $this->to_skip( $src, $path, $type, '' ) ) continue;

            /**
             * Get last modified item datetime stamp
             */
            $file_last_modified_time = $this->get_file_last_modified_time( $path );
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

                    $rel2abs = $this->fix_style_urls( file_get_contents( $item['path'] )  . $inline_css, $item['src'] );

                    if ( strpos( $rel2abs, '@import' ) ) {

                        $rel2abs = $this->include_css_import( $rel2abs );

                    }

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
            $css_compressor = new Autoptimize\tubalmartin\CssMin\Minifier;
            $css_compressor->removeImportantComments();
            $contents['content'] = $css_compressor->run( $contents['content'] );
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
            $contents['content'] = JSMin::minify( $contents['content'], array('flaggedComments' => false) );
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
         * Set minified and combined file name
         */
        $combined_mifinited_filename = EXOPITE_COMBINER_MINIFIER_PLUGIN_DIR . 'combined' . DIRECTORY_SEPARATOR . $combined_file_name;
        $combined_mifinited_filename = apply_filters( 'exopite-combiner-minifier-' . $type . '-file-path', $combined_mifinited_filename );
        $combined_last_modified_times = $list['last-modified'];

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
             $this->check_last_modified_time( $combined_mifinited_filename, $list['last-modified'] ) ||
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

            echo '<!-- Exopite Combiner Minifier - Styles total time: '. $time_styles . 's. -->' . PHP_EOL;

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

            echo '<!-- Exopite Combiner Minifier - Scripts total time: '. $time_scripts . 's. -->' . PHP_EOL;

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
     * Check if file need to process.
     */
    public function to_skip( $src, $path, $type, $media = '' ) {

        if ( ( $this->starts_with( $src, '//' ) && ! $this->starts_with( $src, strstr( $this->site_url, '//' ) ) ) && ! $this->starts_with( $src, $this->site_url ) ) return true;

        /**
         * Ignore some scripts, which should not be moved.
         *
         * str_replace is considerably faster compare to strpos with an array.
         * @link https://stackoverflow.com/questions/6284553/using-an-array-as-needles-in-strpos/42311760#42311760
         */
        if ( str_replace( $this->dont_move, '', $src ) != $src ) return true;

        switch ( $type ) {

            case 'scripts':
                $to_skip = array(
                    'jquery.js',
                    'jquery-migrate.min.js',
                    'admin-bar.min.js',
                );

                $plugin_options = get_option( $this->plugin_name );
                if ( isset( $plugin_options['ignore_process_scripts'] ) ) {
                    $to_skip_user = preg_split( '/\r\n|[\r\n]/', $plugin_options['ignore_process_scripts'] );
                    $to_skip_user = array_map( 'esc_attr', $to_skip_user );
                }

                $to_skip = array_filter( array_merge( $to_skip, $to_skip_user ) );

                break;

            case 'styles':
                $allowed_media = array( 'all', 'screen', '' );
                if ( ! in_array( $media, $allowed_media ) ) return true;
                $to_skip = array(
                    'admin-bar.min.css',
                    'dashicons.min.css'
                );

                $plugin_options = get_option( $this->plugin_name );
                if ( isset( $plugin_options['ignore_process_styles'] ) ) {
                    $to_skip_user = preg_split( '/\r\n|[\r\n]/', $plugin_options['ignore_process_styles'] );
                    $to_skip_user = array_map( 'esc_attr', $to_skip_user );
                }

                $to_skip = array_filter( array_merge( $to_skip, $to_skip_user ) );

                break;

        }

        $pathinfo = pathinfo( $path );

        $to_skip = apply_filters( 'exopite-combiner-minifier-skip-wp_' . $type, $to_skip );

        $ret = false;
        if ( is_array( $to_skip ) && in_array( $pathinfo['basename'], $to_skip ) ) {
            $ret = true;

        }

        return apply_filters( 'exopite-combiner-minifier-to-skip', $ret, $to_skip, $src, $path, $type, $media );

    }

    /**
     * Relative urls to absoulte.
     */
    public function fix_style_urls( $data, $src ) {

        return preg_replace_callback(
            '/url\(\s*[\'"]?(\/?.+?)[\'"]?\s*\)/i',
            function ( $matches ) use( $src ) {
                if ( ! $this->starts_with( $matches[1], 'http' ) &&
                        ! $this->starts_with( $matches[1], '//' ) &&
                        ! $this->starts_with( $matches[1], 'data' )
                    ) {
                    return 'url(' . $this->rel2abs( $matches[1], $src ) . ')';
                }
                return $matches[0];
            },
            $data
        );

    }

    /**
     * Insert first level @import to file.
     *
     * Possivle variants:
     * @link https://developer.mozilla.org/en-US/docs/Web/CSS/@import
     *
     * + url without ' and "
     */
    public function include_css_import( $data ) {

        $regex = '/@import.*?(["\']([^"\']+)["\'].*?(\r\n|\r|\n|;)|url\(([^"|^\'].+)\).*?(\r\n|\r|\n|;))/i';

        return preg_replace_callback(
            $regex,
            function ( $matches ) {

                $fn = '';
                if ( isset( $matches[4] ) ) {
                    $fn = $matches[4];
                } elseif ( isset( $matches[2] ) && ! empty( isset( $matches[2] ) ) ) {
                    $fn = $matches[2];
                }
                if ( ! empty( $fn ) ) {
                    return $this->fix_style_urls( file_get_contents( $fn ), $fn );
                }
                return $matches[0];

            },
            $data
        );
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
                $path = $this->get_path( $src );

                if ( $this->to_skip( $src, $path, $type, $item->getAttribute("media") ) ) continue;

                /**
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
                $path = $this->get_path( $src );
                $src = strtok( $src, '?' );

                if ( $this->to_skip( $src, $path, $type, $item->getAttribute("media") ) ) continue;

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
                $this->check_last_modified_time( $fn, $last_modified )
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
                    if ( apply_filters( 'exopite-combiner-minifier-scripts-process-wp_includes', false ) ) {
                        if ( $this->starts_with( $src, $wp_content_url ) ) {
                            $src = $site_url . $src;
                        }
                    }

                    /**
                     * Skip external resources, plugin and theme author use CDN for a reason.
                     */
                    if ( apply_filters( 'exopite-combiner-minifier-scripts-ignore-external', true ) ) {

                        if ( ! $this->starts_with( $src, $site_url ) ) continue;

                    }

                    // Get path from url
                    $path = $this->get_path( $src );

                    // Skip admin scripts, jQuery, ...
                    if ( $this->to_skip( $src, $path, 'scripts' ) ) continue;

                    if ( ! file_exists( $path ) ) continue;

                }

                $items[] = $item;

            }


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
                    if ( apply_filters( 'exopite-combiner-minifier-scripts-process-wp_includes', false ) ) {
                        if ( $this->starts_with( $src, $wp_content_url ) ) {
                            $src = $site_url . $src;
                        }
                    }

                    // Get path from url
                    $path = $this->get_path( $src );

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
                        $regex_source_mapping = '~//[#@]\s(source(?:Mapping)?URL)=\s*(\S+)~';
                        if ( preg_match( $regex_source_mapping , $js_file_content ) ) {
                            $js_file_content = preg_replace( $regex_source_mapping, '', $js_file_content );
                        }

                        if ( $combine_only_scripts == 'no' && false === ( strpos( $path, 'min.js' ) ) ) {

                            $js_file_content = JSMin::minify( $js_file_content );
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

                        $item->nodeValue = JSMin::minify( $item->textContent );
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

                $script_url = $combined_scripts_mifinited_file_url . '?ver=' . $this->get_file_last_modified_time( $combined_scripts_mifinited_filename );
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
                if ( apply_filters( 'exopite-combiner-minifier-styles-process-wp_includes', false ) ) {
                    if ( $this->starts_with( $src, $wp_content_url ) ) {
                        $src = $site_url . $src;
                    }
                }

                /**
                 * Skip external resources, plugin and theme author use CDN for a reason.
                 */
                if ( apply_filters( 'exopite-combiner-minifier-styles-ignore-external', true ) ) {

                    if ( ! $this->starts_with( $src, $site_url ) ) continue;

                }

                // Get path from url
                $path = $this->get_path( $src );

                $media = $item->getAttribute("media");

                // Skip admin styles
                if ( $this->to_skip( $src, $path, 'styles', $media ) )  continue;

                $items[] = $item;

            }

            /**
             * first need to filter
             */
            $last_modified = $this->get_last_modified( $items, 'styles' );

            $create_file = apply_filters(
                'exopite-combiner-minifier-force-generate-styles',
                $this->check_create_file( $id, 'styles', $combined_styles_mifinited_filename, $items, $last_modified )
            );

            if ( is_null( $items ) ) return $content;

            $css_compressor = new Autoptimize\tubalmartin\CssMin\Minifier;
            $css_compressor->removeImportantComments();

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
                if ( apply_filters( 'exopite-combiner-minifier-styles-process-wp_includes', false ) ) {
                    if ( $this->starts_with( $src, $wp_content_url ) ) {
                        $src = $site_url . $src;
                    }
                }


                // Get path from url
                $path = $this->get_path( $src );

                if ( $generate_head_styles == 'yes' || $create_file ) {

                    if ( file_exists( $path ) ) {

                        /**
                         * Replace all relative url() to absoulte
                         * Need to do this, because our combined css has a different path.
                         * Ignore already absoulte urls, start with "http" and "//",
                         * also ignore "data".
                         */
                        $converted_css = $this->fix_style_urls( file_get_contents( $path ), $src );

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
                        if ( strpos( $converted_css, '@import' ) ) {

                            $converted_css = $this->include_css_import( $converted_css );

                        }

                        if ( $combine_only_styles == 'no' && false === ( strpos( $path, 'min.css' ) ) ) {

                            // $to_write .= ( new Minify\CSS( $converted_css ) )->minify();
                            $to_write .= $css_compressor->run( $converted_css );

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
                    $item->textContent = $css_compressor->run( $item->textContent );
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

                $style_url = $combined_styles_mifinited_file_url . '?ver=' . $this->get_file_last_modified_time( $combined_styles_mifinited_filename );

                $head = $html->getElementsByTagName('head')->item(0);

                $link = $html->createElement('link');
                $link->setAttribute( 'href', $style_url );
                // $link->setAttribute( 'href', $combined_styles_mifinited_file_url . '?ver=' . hash('md5', $this->get_file_last_modified_time( $combined_styles_mifinited_filename ) ) );
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

        /**
         * DomDocument sometimes remove some elements from <script type="text/template">
         * Remove all template content and add back after processing JSs and CSSs.
         */
        $content = $this->remove_script_templates( $content );

        if ( $this->showinfo ) $time_scripts = 'NaN ';
        if ( $this->showinfo ) $time_styles = 'NaN ';
        if ( $this->showinfo ) $time_html = 'NaN ';

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
            $options = array();
            $content = Minify_HTML::minify( $content, $options );

            if ( $this->showinfo ) $time_html = number_format( ( microtime(true) - $startTime ), 4 );

        }

        if ( $this->showinfo && ( $process_scripts == 'yes' || $process_styles == 'yes' || $process_html == 'yes' ) ) {

            $times = PHP_EOL;

            if ( $process_scripts == 'yes' ) {
                $times .= ( $process_scripts == 'yes' && $combine_only_scripts == 'no' ) ?  '<!-- Exopite Combiner Minifier - JavaScript: '. $time_scripts . 's. -->' : '<!-- Exopite Combiner Minifier - Combine JavaScript: '. $time_scripts . 's. -->';
                $times .= PHP_EOL;
            }

            if ( $process_styles == 'yes' ) {
                $times .= ( $process_styles == 'yes' && $combine_only_styles == 'no' ) ?  '<!-- Exopite Combiner Minifier - CSS: '. $time_styles . 's. -->' : '<!-- Exopite Combiner Minifier - Combine styles: '. $time_styles . 's. -->';
                $times .= PHP_EOL;
            }

            if ( $process_html == 'yes' ) {
                $times .= '<!-- Exopite Combiner Minifier - Minify inline HTML: '. $time_html . 's. -->' . PHP_EOL;
            }

            $content .= $times;
        }

        if ( ! empty( $this->js_templates ) ) {
            $content = $this->add_script_templates_back( $content );
        }

        return $content;

    }

    //https://stackoverflow.com/questions/5266945/wordpress-how-detect-if-current-page-is-the-login-page/5892694#5892694
    public function is_login_page() {

        return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );

    }

    public function process_html( $content ) {

        if ( is_admin() || ( ! is_singular() && ! is_archive() && ! is_404() ) ) {
            return $content;
        }

        // if ( is_admin() || $this->is_login_page() || is_robots() ) return $content;
        // if( ( isset( $_SERVER["REQUEST_URI"] ) && substr( $_SERVER["REQUEST_URI"], -4 ) === '.xml' ) ) return $content;

        $this->options = get_option( $this->plugin_name );
        $this->options_lists = get_option( $this->plugin_name . '_lists' );
        $this->site_url = get_site_url();

        if ( apply_filters( 'exopite-combiner-minifier-process-scripts-styles', true ) ) {

            $startTime = microtime(true);

            $content = $this->process_scripts_styles( $content );

            $time_scripts_styles = number_format( ( microtime(true) - $startTime ), 4 );

        }

        if ( $this->showinfo ) $content .= '<!-- Exopite Combiner Minifier - TOTAL: '. $time_scripts_styles . 's. -->';

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

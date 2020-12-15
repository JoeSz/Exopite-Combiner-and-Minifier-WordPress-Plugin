<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       https://joe.szalai.org
 * @since      1.0.0
 *
 * @package    Exopite_Combiner_Minifier
 * @subpackage Exopite_Combiner_Minifier/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Exopite_Combiner_Minifier
 * @subpackage Exopite_Combiner_Minifier/includes
 * @author     Joe Szalai <joe@szalai.org>
 */
class Exopite_Combiner_Minifier_Utilities {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
    private $plugin_name;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name ) {

		$this->plugin_name = $plugin_name;

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

    // not used
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

    public function normalize_css(  $data, $src ) {

        $data = $this->fix_style_urls( $data, $src );
        $data = $this->include_css_import( $data );

        return $data;

    }

    /**
     * Relative urls to absoulte.
     */
    /**
     * Replace all relative url() to absoulte
     * Need to do this, because our combined css has a different path.
     * Ignore already absoulte urls, start with "http" and "//",
     * also ignore "data".
     */
     public function fix_style_urls( $data, $src ) {

        return preg_replace_callback(
            '/url\(\s*[\'"]?(\/?.+?)[\'"]?\s*\)/i',
            function ( $matches ) use( $src ) {
                if ( ! $this->starts_with( $matches[1], 'http' ) &&
                        ! $this->starts_with( $matches[1], '//' ) &&
                        ! $this->starts_with( $matches[1], 'data' )
                    ) {
                    return 'url("' . $this->rel2abs( $matches[1], $src ) . '")';
                }
                return $matches[0];
            },
            $data
        );

    }

    /**
     * Need to check import and if any then insert.
     *
     * Possible variants:
     * @link https://developer.mozilla.org/en-US/docs/Web/CSS/@import
     * Also in url without ' or ".
     *
     * '/@import.*?["\']([^"\']+)["\'].*?(\r\n|\r|\n|;)/i'
     * '/@import.*?url\(([^"|^'].+)\).*?(\r\n|\r|\n|;)/i'
     *
     * '/(@import.*?["\']([^"\']+)["\'].*?(\r\n|\r|\n|;)|@import.*?url\(([^"|^'].+)\).*?(\r\n|\r|\n|;))/i'
     * @import.*?(["\']([^"\']+)["\'].*?(\r\n|\r|\n|;)|url\(([^"|^'].+)\).*?(\r\n|\r|\n|;))
     *
     * Insert first level @import to file.
     *
     * Possivle variants:
     * @link https://developer.mozilla.org/en-US/docs/Web/CSS/@import
     *
     * + url without ' and "
     */
    public function include_css_import( $data ) {

        if ( strpos( $data, '@import' ) ) {

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

        return $data;
    }

    /**
     * To "fix" styles, scripts start with /wp-includes, like jQuery
     * If you activate this, ALL not external will be processed.
     * This can produce error, because some styles, scripts are enqueued
     * very late that we won’t able to catch it earlier and they may have a depency.
     */
    public function normalize_url( $url, $site_url, $wp_content_url, $type ) {

        if ( apply_filters( 'exopite-combiner-minifier-' . $type . '-process-wp_includes', false ) ) {
            if ( $this->starts_with( $url, $wp_content_url ) ) {
                $url = $site_url . $url;
            }
        }

        return $url;

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
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST // (#1)
            || isset( $_GET['rest_route'] ) // (#2)
            	&& strpos( trim( $_GET['rest_route'], '\\/' ), $prefix , 0 ) === 0) {
				return true;
			}

        // (#3)
        $rest_url = wp_parse_url( site_url( $prefix ) );
        $current_url = wp_parse_url( add_query_arg( array( ) ) );
        return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
    }

    public function is_api_request() {

        return (
            ( defined( 'JSON_REQUEST' ) && JSON_REQUEST ) ||
            ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ||
            ( defined( 'DOING_AJAX' ) && DOING_AJAX )
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

    //https://stackoverflow.com/questions/5266945/wordpress-how-detect-if-current-page-is-the-login-page/5892694#5892694
    public function is_login_page() {

        return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );

    }

}

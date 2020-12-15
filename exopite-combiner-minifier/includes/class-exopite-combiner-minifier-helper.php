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

// use MatthiasMullie\Minify;
// use MatthiasMullie\PathConverter\Converter;

class Exopite_Combiner_Minifier_Helper {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	public $main;

    // from autoptimize
    public $dont_move = array(
		'document.write',
		'html5.js',
		'show_ads.js',
		'google_ad',
		'histats.com/js',
		'statcounter.com/counter/counter.js',
		'ws.amazon.com/widgets',
		'media.fastclick.net',
		'/ads/',
		'comment-form-quicktags/quicktags.php',
		'edToolbar',
		'intensedebate.com',
		'scripts.chitika.net/',
		'_gaq.push',
		'jotform.com/',
		'admin-bar.min.js',
		'GoogleAnalyticsObject',
		'plupload.full.min.js',
		'syntaxhighlighter',
		'adsbygoogle',
		'gist.github.com',
		'_stq',
		'nonce',
		'post_id',
		'data-noptimize',
		'logHuman',
		'dontmove',
		'adminbar-',
    );

    public $searched_script_types = array(
        'type="text/template"',
        "type=\'text/template\'",
        'type="text/x-template"',
        "type=\'text/x-template\'",
    );

    public $regex_js_template = '/<script(.*?)>([\s\S]*?)<\/script>/i';

    public $js_templates = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $main ) {

		$this->main = $main;
		$this->plugin_name = $plugin_name;

	}

	/**
	 * Do not process inline JavaScript if contain "<![CDATA["
	 * Process inline script without type or 'text/javascript' type
	 */
	public function to_skip_inline( $content, $type ) {

		return (
			( strpos( $content, "woocommerce" ) === false ) &&
			( strpos( $content, "<![CDATA[" ) === false ) &&
			( ! isset( $type ) || empty( $type ) || $type == 'text/javascript' )
		);

	}

    /**
     * Check if file need to process.
     */
    public function to_skip( $src, $path, $type, $media = '' ) {

		if ( empty( $src ) ) {
			return false;
		}

        if ( ( $this->main->util->starts_with( $src, '//' ) && ! $this->main->util->starts_with( $src, strstr( $this->main->public->site_url, '//' ) ) ) && ! $this->main->util->starts_with( $src, $this->main->public->site_url ) ) return true;

        /**
         * Ignore some scripts, which should not be moved.
         *
         * str_replace is considerably faster compare to strpos with an array.
         * @link https://stackoverflow.com/questions/6284553/using-an-array-as-needles-in-strpos/42311760#42311760
         */
        if ( str_replace( $this->dont_move, '', $src ) != $src ) return true;

        $to_skip = array();

        switch ( $type ) {

            case 'wp_scripts':
                // no break
            case 'scripts':

                $plugin_options = get_option( $this->plugin_name );
                if ( isset( $plugin_options['ignore_process_scripts'] ) ) {
                    $to_skip_user = preg_split( '/\r\n|[\r\n]/', $plugin_options['ignore_process_scripts'] );
                    $to_skip_user = array_map( 'esc_attr', $to_skip_user );
                }

				$to_skip = array_filter( array_merge( $to_skip, $to_skip_user ) );

                break;

            case 'wp_styles':
                // no break
            case 'styles':
                $allowed_media = array( 'all', 'screen', '' );
                if ( ! in_array( $media, $allowed_media ) ) return true;

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
        if ( is_array( $to_skip ) && in_array( $pathinfo['basename'], $to_skip ) || $to_skip == $pathinfo['basename'] ) {
            $ret = true;

        }

        return apply_filters( 'exopite-combiner-minifier-to-skip', $ret, $to_skip, $src, $path, $type, $media );

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

                if ( $this->to_skip( $src, $path, $type, $item->getAttribute("media") ) ) continue;

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

	/**
	 * DomDocument sometimes remove some elements from <script type="text/template">
	 * Remove all template content and add back after processing JSs and CSSs.
	 */
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

                if ( $this->main->util->strpos_array( $matches[1], $this->searched_script_types ) ) {

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

		if ( empty( $this->js_templates ) ) {
			return $content;
		}

        $index = 0;

        return preg_replace_callback(
            $this->regex_js_template,
            function ( $matches ) use ( &$index ) {

                if ( $this->main->util->strpos_array( $matches[1], $this->searched_script_types ) ) {

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

    /**
     * - if single file, need to save to options, not in post meta.
     * - this could do for archives too?
     * - save in separate option? -> ( $this->plugin_name . '_lists' )['styles'] -> 'styles', 'scripts', 'generated'
     *   ( '404', 'archive', search' ) <- those have always the same list? I think yes.
     */
    public function is_single_file_to_save( $type ) {

        if (
            ( is_singular() && $type == 'styles' && $this->main->public->create_separate_css_files == 'no' ) ||
            ( is_singular() && $type == 'scripts' && $this->main->public->create_separate_js_files == 'no' ) ||
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
                $this->check_list( $items, $type ) ||
                $this->main->util->check_last_modified_time( $fn, $last_modified )
            ) &&
            ! in_array( $id, $array_to_skip )
        ) {

            return true;

        }

        return false;

    }

	public function get_id( $type ) {

        $id = '';
        if ( $this->is_single_file_to_save( $type ) === false ) {
                $id = $this->get_type_name();

                if ( empty( $id ) ) {
                    return false;
                }
		}

		return $id;

	}

}

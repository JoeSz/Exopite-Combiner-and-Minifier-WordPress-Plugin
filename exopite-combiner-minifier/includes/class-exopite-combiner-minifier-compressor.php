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

class Exopite_Combiner_Minifier_Compressor {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	public $main;

	public $css_compressor = false;

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
	 * If we do not remove charsets, this could be appier multiple times in the combined css.
	 *
	 * We could do this also after the combination of csses, but it is faster in this way.
	 * But it is also possible, multiple csses has multiple charset version.
	 * If I have problem with this later, may consider to try this variant.
	 */
	public function css_remove_charset( $css ) {

		// @charset[ A-Za-z0-9-"]+;
		$regex_charset = '~@charset[^;]+;\s*~';
		if ( strpos( $css, '@charset' ) !== false  ) {
			$css = preg_replace( $regex_charset, '', $css );
		}

		return $css;

	}

	public function css_remove_important_comments( $css ) {

		// /*!rtl:begin:ignore*/direction:ltr;
  		// /*!rtl:end:ignore*/
		// Performance: http://maettig.com/code/php/php-performance-benchmarks.php
		$to_replace = array(
			array(
				'string' =>  'rtl:begin:ignore',
				'pattern' => '~\/\*!(\s+)?rtl:begin:ignore(\s+)?\*\/[^\/]+\/\*!(\s+)?rtl:end:ignore(\s+)?\*\/~',
			),
			array(
				'string' =>  '/*!',
				'pattern' => '~\/\*![^*]*\*+([^\/][^*]*\*+)*\/~',
			),
		);

		$regexes = array();

		foreach ( $to_replace as $item ) {

			if ( strpos( $css, $item['string'] ) !== false  ) {
				$regexes[] = $item['pattern'];
			}

		}

		if ( ! empty( $regexes ) ) {

			$css = preg_replace( $regexes, '', $css );

		}

		return $css;

	}

	public function css( $css ) {

		if ( ! $this->css_compressor ) {

			$this->css_compressor = new Autoptimize\tubalmartin\CssMin\Minifier;
			$this->css_compressor->removeImportantComments();

		}

		$css = $this->css_remove_charset( $css );

        // return = CssMin::minify( $css );
		// return ( new Minify\CSS( $css ) )->minify();

		return $this->css_compressor->run( $css );
	}

	/**
	 * Removing source map URLs in js files to avoid catastrophic breaking.
	 *
	 * @link https://stackoverflow.com/questions/36629224/how-to-improve-regex-for-removing-source-map-urls-in-js-files-to-avoid-catastrop
	 */
	public function remove_source_mapping( $js_file_content ) {

		$regex_source_mapping = '~//[#@]\s(source(?:Mapping)?URL)=\s*(\S+)~';
		if ( preg_match( $regex_source_mapping , $js_file_content ) ) {
			$js_file_content = preg_replace( $regex_source_mapping, '', $js_file_content );
		}

		return $js_file_content;
	}

	public function js( $js_file_content, $flagged_comments = true ) {

		// Append a semicolon at the end of js files if it's missing.
		// $last_char = substr( $js_file_content, -1, 1 );
		// if ( ';' !== $last_char && '}' !== $last_char ) {
		//     $js_file_content .= ';';
		// }

		// return JSMinPlus::minify( $js_file_content, array('flaggedComments' => false) );
		// return ( new Minify\JS( $js_file_content ) )->minify();

		return JSMin::minify( $js_file_content, array( 'flaggedComments' => $flagged_comments ) );
	}

	public function html( $content, $options ) {

		return Minify_HTML::minify( $content, $options );
	}

}

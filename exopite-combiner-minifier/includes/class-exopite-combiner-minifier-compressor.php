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
	public function __construct( $plugin_name ) {

		$this->main = $this;
		$this->plugin_name = $plugin_name;

	}

	public function css( $css ) {

		if ( ! $this->css_compressor ) {

			$this->css_compressor = new Autoptimize\tubalmartin\CssMin\Minifier;
			$this->css_compressor->removeImportantComments();

		}

        // $to_write .= ( new Minify\CSS( $converted_css ) )->minify();

		return $this->css_compressor->run( $css );
	}

	public function js( $js_file_content, $source_mapping = false, $flagged_comments = true ) {

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
		if ( $source_mapping ) {

			$regex_source_mapping = '~//[#@]\s(source(?:Mapping)?URL)=\s*(\S+)~';
			if ( preg_match( $regex_source_mapping , $js_file_content ) ) {
				$js_file_content = preg_replace( $regex_source_mapping, '', $js_file_content );
			}

		}

		// $contents['content'] = JSMin::minify( $js, array('flaggedComments' => false) );
		// $contents['content'] = JSMinPlus::minify( $contents['content'], array('flaggedComments' => false) );
		// $contents['content'] = ( new Minify\JS( $contents['content'] ) )->minify();

		return JSMin::minify( $js_file_content, array( 'flaggedComments' => $flagged_comments ) );
	}

	public function html( $content, $options ) {
		return Minify_HTML::minify( $content, $options );
	}

}

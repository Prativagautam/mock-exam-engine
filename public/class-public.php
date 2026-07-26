<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.acmeit.org/
 * @since      1.0.0
 *
 * @package    Mock_Exam_Engine
 * @subpackage Mock_Exam_Engine/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Mock_Exam_Engine
 * @subpackage Mock_Exam_Engine/public
 * @author     codersantosh <codersantosh@gmail.com>
 */
class Mock_Exam_Engine_Public {

	/**
	 * Gets an instance of this object.
	 * Prevents duplicate instances which avoid artefacts and improves performance.
	 *
	 * @static
	 * @access public
	 * @return object
	 * @since 1.0.0
	 */
	public static function get_instance() {
		// Store the instance locally to avoid private static replication.
		static $instance = null;

		// Only run these methods if they haven't been ran previously.
		if ( null === $instance ) {
			$instance = new self();
		}

		// Always return the instance.
		return $instance;
	}

	/**
	 * Register the JavaScript and stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_public_resources() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mock_Exam_Engine_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mock_Exam_Engine_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		/* Atomic CSS */
		wp_enqueue_style( 'atomic' );
		wp_style_add_data( 'atomic', 'rtl', 'replace' );

		$version = MOCK_EXAM_ENGINE_VERSION;

		wp_enqueue_style( MOCK_EXAM_ENGINE_PLUGIN_NAME, MOCK_EXAM_ENGINE_URL . 'build/public/index.css', array(), $version );
		wp_style_add_data( MOCK_EXAM_ENGINE_PLUGIN_NAME, 'rtl', 'replace' );

		/*Scripts dependency files*/
		$deps_file = MOCK_EXAM_ENGINE_PATH . 'build/public/index.asset.php';

		/*Fallback dependency array*/
		$dependency = array();

		/*Set dependency and version*/
		if ( file_exists( $deps_file ) ) {
			$deps_file  = require $deps_file;
			$dependency = $deps_file['dependencies'];
			$version    = $deps_file['version'];
		}

		wp_enqueue_script( MOCK_EXAM_ENGINE_PLUGIN_NAME, MOCK_EXAM_ENGINE_URL . 'build/public/index.js', $dependency, $version, true );
		wp_set_script_translations( MOCK_EXAM_ENGINE_PLUGIN_NAME, MOCK_EXAM_ENGINE_PLUGIN_NAME );

		$localize = apply_filters(
			'mock_exam_engine_public_localize',
			array(
				'MOCK_EXAM_ENGINE_URL' => MOCK_EXAM_ENGINE_URL,
				'site_url'                        => esc_url( home_url() ),
				'rest_url'                        => get_rest_url(),
				'nonce'                           => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_add_inline_script(
			MOCK_EXAM_ENGINE_PLUGIN_NAME,
			sprintf(
				"var MockExamEngineLocalize = JSON.parse( decodeURIComponent( '%s' ) );",
				rawurlencode(
					wp_json_encode(
						$localize
					)
				),
			),
			'before'
		);
	}
}

if ( ! function_exists( 'mock_exam_engine_public' ) ) {
	/**
	 * Return instance of  Mock_Exam_Engine_Public class
	 *
	 * @since 1.0.0
	 *
	 * @return Mock_Exam_Engine_Public
	 */
	function mock_exam_engine_public() {//phpcs:ignore
		return Mock_Exam_Engine_Public::get_instance();
	}
}

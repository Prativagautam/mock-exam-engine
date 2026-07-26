<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.acmeit.org/
 * @since      1.0.0
 *
 * @package    Mock_Exam_Engine
 * @subpackage Mock_Exam_Engine/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Mock_Exam_Engine
 * @subpackage Mock_Exam_Engine/includes
 * @author     codersantosh <codersantosh@gmail.com>
 */
class Mock_Exam_Engine_Deactivator {

	/**
	 * Fired during plugin deactivation.
	 *
	 * Removing options and all data related to plugin if user select remove data on deactivate.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		if ( mock_exam_engine_get_options( 'deleteAll' ) ) {
			delete_option( MOCK_EXAM_ENGINE_OPTION_NAME );
		}
	}
}

<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.acmeit.org/
 * @since             1.0.0
 * @package           Mock_Exam_Engine
 *
 * @wordpress-plugin
 * Plugin Name:       Mock Exam Engine
 * Plugin URI:        https://www.addonspress.com/wordpress-starter-plugins/mock-exam-engine
 * Description:       WordPress MCQ Practice Plugin.
 * Version:           1.0.0
 * Author:            codersantosh
 * Author URI:        https://www.acmeit.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mock-exam-engine
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin path.
 * Current plugin url.
 * Current plugin version.
 * Current plugin name.
 * Current plugin option name.
 */
define( 'MOCK_EXAM_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MOCK_EXAM_ENGINE_URL', plugin_dir_url( __FILE__ ) );
define( 'MOCK_EXAM_ENGINE_VERSION', '1.0.0' );
define( 'MOCK_EXAM_ENGINE_PLUGIN_NAME', 'mock-exam-engine' );
define( 'MOCK_EXAM_ENGINE_OPTION_NAME', 'mock-exam-engine' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-activator.php
 */
function mock_exam_engine_activate() {
	require_once MOCK_EXAM_ENGINE_PATH . 'includes/class-activator.php';
	Mock_Exam_Engine_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-deactivator
 */
function mock_exam_engine_deactivate() {
	require_once MOCK_EXAM_ENGINE_PATH . 'includes/class-deactivator.php';
	Mock_Exam_Engine_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'mock_exam_engine_activate' );
register_deactivation_hook( __FILE__, 'mock_exam_engine_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require MOCK_EXAM_ENGINE_PATH . 'includes/main.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function mock_exam_engine_run() {

	$plugin = new Mock_Exam_Engine();
	$plugin->run();
}
mock_exam_engine_run();

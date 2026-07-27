<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Custom Post Types and Taxonomies for the plugin.
 *
 * @package    Mock_Exam_Engine
 * @subpackage Mock_Exam_Engine/includes
 */
class Mock_Exam_Engine_Post_Types {

	/**
	 * Gets an instance of this object.
	 *
	 * @static
	 * @access public
	 * @return object
	 * @since 1.0.0
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Register all custom post types.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_post_types() {
		$this->register_question_post_type();
		$this->register_mock_test_post_type();
		$this->register_attempt_post_type();
	}

	/**
	 * Register the 'question' post type.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_question_post_type() {
		$labels = array(
			'name'          => __( 'Questions', 'mock-exam-engine' ),
			'singular_name' => __( 'Question', 'mock-exam-engine' ),
			'add_new_item'  => __( 'Add New Question', 'mock-exam-engine' ),
			'edit_item'     => __( 'Edit Question', 'mock-exam-engine' ),
			'all_items'     => __( 'All Questions', 'mock-exam-engine' ),
			'search_items'  => __( 'Search Questions', 'mock-exam-engine' ),
		);

		register_post_type(
			'question',
			array(
				'labels'       => $labels,
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'custom-fields' ),
				'menu_icon'    => 'dashicons-editor-help',
				'has_archive'  => false,
			)
		);
	}

	/**
	 * Register the 'mock_test' post type.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_mock_test_post_type() {
		$labels = array(
			'name'          => __( 'Mock Tests', 'mock-exam-engine' ),
			'singular_name' => __( 'Mock Test', 'mock-exam-engine' ),
			'add_new_item'  => __( 'Add New Mock Test', 'mock-exam-engine' ),
			'edit_item'     => __( 'Edit Mock Test', 'mock-exam-engine' ),
			'all_items'     => __( 'All Mock Tests', 'mock-exam-engine' ),
			'search_items'  => __( 'Search Mock Tests', 'mock-exam-engine' ),
		);

		register_post_type(
			'mock_test',
			array(
				'labels'       => $labels,
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'custom-fields' ),
				'menu_icon'    => 'dashicons-clipboard',
				'has_archive'  => false,
			)
		);
	}

	/**
	 * Register the 'attempt' post type.
	 *
	 * Not public — a student's attempt should never be independently
	 * browsable/readable by URL; access is only via our custom REST routes,
	 * which will enforce ownership checks themselves.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_attempt_post_type() {
		$labels = array(
			'name'          => __( 'Attempts', 'mock-exam-engine' ),
			'singular_name' => __( 'Attempt', 'mock-exam-engine' ),
			'all_items'     => __( 'All Attempts', 'mock-exam-engine' ),
		);

		register_post_type(
			'attempt',
			array(
				'labels'       => $labels,
				'public'       => false,
				'show_ui'      => true, // visible to admins in wp-admin for review, but not on the public site.
				'show_in_rest' => true, // still needed so our custom REST controller can read/write it internally.
				'supports'     => array( 'title', 'custom-fields', 'author' ),
				'capability_type' => 'post',
				'menu_icon'    => 'dashicons-clipboard',
			)
		);
	}

	/**
	 * Register all taxonomies.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_taxonomies() {
		$this->register_exam_type_taxonomy();
		$this->register_subject_taxonomy();
	}

	/**
	 * Register the 'exam_type' taxonomy (flat — CEE, IOE, CSIT, etc.)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_exam_type_taxonomy() {
		register_taxonomy(
			'exam_type',
			array( 'question', 'mock_test' ),
			array(
				'labels'            => array(
					'name'          => __( 'Exam Types', 'mock-exam-engine' ),
					'singular_name' => __( 'Exam Type', 'mock-exam-engine' ),
				),
				'hierarchical'      => false,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);
	}

	/**
	 * Register the 'subject' taxonomy — hierarchical, chapters as child terms
	 * under each subject (e.g. Physics -> Mechanics, Physics -> Optics).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_subject_taxonomy() {
		register_taxonomy(
			'subject',
			array( 'question', 'mock_test' ),
			array(
				'labels'            => array(
					'name'          => __( 'Subjects', 'mock-exam-engine' ),
					'singular_name' => __( 'Subject', 'mock-exam-engine' ),
				),
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);
	}


	public function register_meta_fields() {
		$this->register_question_meta_fields();
		$this->register_mock_test_meta_fields();
		$this->register_attempt_meta_fields();
	}

	public function register_question_meta_fields() {
		$string_fields = array( 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option', 'difficulty' );
		foreach ( $string_fields as $field ) {
			register_post_meta(
				'question',
				$field,
				array(
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}

		$number_fields = array( 'marks', 'negative_marks' );
		foreach ( $number_fields as $field ) {
			register_post_meta(
				'question',
				$field,
				array(
					'type'         => 'number',
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}
	}

	public function register_mock_test_meta_fields() {
		register_post_meta(
			'mock_test',
			'duration_minutes',
			array(
				'type'         => 'number',
				'single'       => true,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'mock_test',
			'question_ids',
			array(
				'type'         => 'array',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
			)
		);

		register_post_meta(
			'mock_test',
			'subject_weightage',
			array(
				'type'         => 'object',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => array( 'type' => 'number' ),
					),
				),
			)
		);
	}

	public function register_attempt_meta_fields() {
		register_post_meta(
			'attempt',
			'mock_test_id',
			array(
				'type'         => 'integer',
				'single'       => true,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'attempt',
			'status',
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
			)
		);

		foreach ( array( 'started_at', 'submitted_at' ) as $field ) {
			register_post_meta(
				'attempt',
				$field,
				array(
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}

		register_post_meta(
			'attempt',
			'answers',
			array(
				'type'         => 'object',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => array( 'type' => 'string' ),
					),
				),
			)
		);

		register_post_meta(
			'attempt',
			'score',
			array(
				'type'         => 'number',
				'single'       => true,
				'show_in_rest' => true,
			)
		);

		register_post_meta(
			'attempt',
			'subject_breakdown',
			array(
				'type'         => 'object',
				'single'       => true,
				'show_in_rest' => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => array( 'type' => 'object' ),
					),
				),
			)
		);
	}
}


if ( ! function_exists( 'mock_exam_engine_post_types' ) ) {
	/**
	 * Return instance of Mock_Exam_Engine_Post_Types class
	 *
	 * @since 1.0.0
	 * @return Mock_Exam_Engine_Post_Types
	 */
	function mock_exam_engine_post_types() { //phpcs:ignore
		return Mock_Exam_Engine_Post_Types::get_instance();
	}
}

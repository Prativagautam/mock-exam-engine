<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the attempt lifecycle (start, answer, submit, result) via REST API.
 *
 * @package    Mock_Exam_Engine
 * @subpackage Mock_Exam_Engine/includes/api
 */
if ( ! class_exists( 'Mock_Exam_Engine_Api_Attempts' ) ) {

	class Mock_Exam_Engine_Api_Attempts extends Mock_Exam_Engine_Api {

		public function run() {
			$this->type      = 'mock_exam_engine_api_attempts';
			$this->rest_base = 'attempts';

			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		public function register_routes() {
			$namespace = $this->namespace . $this->version;

			register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/start',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'start_attempt' ),
					'permission_callback' => array( $this, 'start_attempt_permissions_check' ),
					'args'                => array(
						'mock_test_id' => array(
							'required' => true,
							'type'     => 'integer',
						),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/(?P<id>\d+)/answer',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_answer' ),
					'permission_callback' => array( $this, 'owner_permissions_check' ),
					'args'                => array(
						'question_id'     => array(
							'required' => true,
							'type'     => 'integer',
						),
						'selected_option' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/(?P<id>\d+)/submit',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'submit_attempt' ),
					'permission_callback' => array( $this, 'owner_permissions_check' ),
				)
			);

			register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/(?P<id>\d+)/result',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_result' ),
					'permission_callback' => array( $this, 'owner_or_admin_permissions_check' ),
				)
			);
		}

		/**
		 * Anyone logged in can start an attempt.
		 */
		public function start_attempt_permissions_check( $request ) {
			return is_user_logged_in();
		}

		/**
		 * Only the attempt's own author can answer/submit it.
		 */
		public function owner_permissions_check( $request ) {
			$attempt = get_post( (int) $request['id'] );

			if ( ! $attempt || 'attempt' !== $attempt->post_type ) {
				return new WP_Error( 'rest_not_found', __( 'Attempt not found.', 'mock-exam-engine' ), array( 'status' => 404 ) );
			}

			if ( (int) $attempt->post_author !== get_current_user_id() ) {
				return new WP_Error( 'rest_forbidden', __( 'You do not own this attempt.', 'mock-exam-engine' ), array( 'status' => 403 ) );
			}

			return true;
		}

		/**
		 * Owner or an admin (manage_options) can read the result.
		 */
		public function owner_or_admin_permissions_check( $request ) {
			$attempt = get_post( (int) $request['id'] );

			if ( ! $attempt || 'attempt' !== $attempt->post_type ) {
				return new WP_Error( 'rest_not_found', __( 'Attempt not found.', 'mock-exam-engine' ), array( 'status' => 404 ) );
			}

			if ( (int) $attempt->post_author === get_current_user_id() || current_user_can( 'manage_options' ) ) {
				return true;
			}

			return new WP_Error( 'rest_forbidden', __( 'You cannot view this attempt.', 'mock-exam-engine' ), array( 'status' => 403 ) );
		}

		/**
		 * POST /attempts/start
		 * Creates an attempt, server-stamps started_at, returns questions WITHOUT correct_option.
		 */
		public function start_attempt( $request ) {
			$mock_test_id = (int) $request['mock_test_id'];
			$mock_test    = get_post( $mock_test_id );

			if ( ! $mock_test || 'mock_test' !== $mock_test->post_type ) {
				return new WP_Error( 'rest_invalid_mock_test', __( 'Invalid mock test.', 'mock-exam-engine' ), array( 'status' => 404 ) );
			}

			$attempt_id = wp_insert_post(
				array(
					'post_type'   => 'attempt',
					'post_status' => 'private',
					'post_title'  => sprintf( 'Attempt #%d - %s', $mock_test_id, current_time( 'mysql' ) ),
					'post_author' => get_current_user_id(),
				),
				true
			);

			if ( is_wp_error( $attempt_id ) ) {
				return $attempt_id;
			}

			// Server is authoritative on start time — never trust a client-supplied timestamp.
			update_post_meta( $attempt_id, 'mock_test_id', $mock_test_id );
			update_post_meta( $attempt_id, 'status', 'in-progress' );
			update_post_meta( $attempt_id, 'started_at', current_time( 'mysql', true ) ); // GMT, for reliable comparisons later.
			update_post_meta( $attempt_id, 'answers', array() );

			$question_ids = get_post_meta( $mock_test_id, 'question_ids', true );
			$questions    = array();

			foreach ( (array) $question_ids as $qid ) {
				$questions[] = array(
					'id'            => $qid,
					'question_text' => get_post_meta( $qid, 'question_text', true ),
					'option_a'      => get_post_meta( $qid, 'option_a', true ),
					'option_b'      => get_post_meta( $qid, 'option_b', true ),
					'option_c'      => get_post_meta( $qid, 'option_c', true ),
					'option_d'      => get_post_meta( $qid, 'option_d', true ),
					// correct_option deliberately omitted — must never reach the client before submission.
				);
			}

			return rest_ensure_response(
				array(
					'attempt_id'       => $attempt_id,
					'duration_minutes' => get_post_meta( $mock_test_id, 'duration_minutes', true ),
					'questions'        => $questions,
				)
			);
		}

		/**
		 * POST /attempts/{id}/answer
		 * Incremental save — one answer at a time.
		 */
		public function save_answer( $request ) {
			$attempt_id = (int) $request['id'];
			$status     = get_post_meta( $attempt_id, 'status', true );

			if ( 'in-progress' !== $status ) {
				return new WP_Error( 'rest_attempt_closed', __( 'This attempt is no longer in progress.', 'mock-exam-engine' ), array( 'status' => 400 ) );
			}

			$answers                                    = (array) get_post_meta( $attempt_id, 'answers', true );
			$answers[ (string) $request['question_id'] ] = sanitize_text_field( $request['selected_option'] );

			update_post_meta( $attempt_id, 'answers', $answers );

			return rest_ensure_response( array( 'saved' => true ) );
		}

		/**
		 * POST /attempts/{id}/submit
		 * Grades the attempt. Server checks the time window — not the client.
		 */
		public function submit_attempt( $request ) {
			$attempt_id = (int) $request['id'];
			$status     = get_post_meta( $attempt_id, 'status', true );

			if ( 'in-progress' !== $status ) {
				return new WP_Error( 'rest_attempt_closed', __( 'This attempt has already been submitted.', 'mock-exam-engine' ), array( 'status' => 400 ) );
			}

			$mock_test_id     = (int) get_post_meta( $attempt_id, 'mock_test_id', true );
			$duration_minutes = (int) get_post_meta( $mock_test_id, 'duration_minutes', true );
			$started_at       = get_post_meta( $attempt_id, 'started_at', true );

			$started_timestamp = strtotime( $started_at . ' GMT' );
			$deadline           = $started_timestamp + ( $duration_minutes * 60 );

			// NOTE: not hard-rejecting late submits yet — grading proceeds either way for v1,
			// but we record whether it was late. A cron-based auto-submit (closing attempts
			// that never got a manual submit call) is a deliberate v2 item, not built yet.
			$is_late = time() > $deadline;

			$answers      = (array) get_post_meta( $attempt_id, 'answers', true );
			$question_ids = get_post_meta( $mock_test_id, 'question_ids', true );

			$score             = 0;
			$subject_breakdown = array();

			foreach ( (array) $question_ids as $qid ) {
				$correct_option = get_post_meta( $qid, 'correct_option', true );
				$marks          = (float) get_post_meta( $qid, 'marks', true );
				$negative_marks = (float) get_post_meta( $qid, 'negative_marks', true );
				$subject_terms  = wp_get_post_terms( $qid, 'subject', array( 'fields' => 'names' ) );
				$subject        = ! empty( $subject_terms ) ? $subject_terms[0] : 'Unknown';

				if ( ! isset( $subject_breakdown[ $subject ] ) ) {
					$subject_breakdown[ $subject ] = array(
						'correct' => 0,
						'wrong'   => 0,
						'skipped' => 0,
					);
				}

				$given = isset( $answers[ (string) $qid ] ) ? $answers[ (string) $qid ] : null;

				if ( null === $given ) {
					$subject_breakdown[ $subject ]['skipped'] += 1;
				} elseif ( $given === $correct_option ) {
					$score += $marks;
					$subject_breakdown[ $subject ]['correct'] += 1;
				} else {
					$score -= $negative_marks;
					$subject_breakdown[ $subject ]['wrong'] += 1;
				}
			}

			update_post_meta( $attempt_id, 'status', 'graded' );
			update_post_meta( $attempt_id, 'submitted_at', current_time( 'mysql', true ) );
			update_post_meta( $attempt_id, 'score', $score );
			update_post_meta( $attempt_id, 'subject_breakdown', $subject_breakdown );

			return rest_ensure_response(
				array(
					'score'             => $score,
					'subject_breakdown' => $subject_breakdown,
					'was_late'          => $is_late,
				)
			);
		}

		/**
		 * GET /attempts/{id}/result
		 */
		public function get_result( $request ) {
			$attempt_id = (int) $request['id'];
			$status     = get_post_meta( $attempt_id, 'status', true );

			if ( 'graded' !== $status ) {
				return new WP_Error( 'rest_not_graded', __( 'This attempt has not been graded yet.', 'mock-exam-engine' ), array( 'status' => 400 ) );
			}

			return rest_ensure_response(
				array(
					'score'             => get_post_meta( $attempt_id, 'score', true ),
					'subject_breakdown' => get_post_meta( $attempt_id, 'subject_breakdown', true ),
				)
			);
		}

		public static function get_instance() {
			static $instance = null;
			if ( null === $instance ) {
				$instance = new self();
			}
			return $instance;
		}
	}
}

function mock_exam_engine_api_attempts() { //phpcs:ignore
	return Mock_Exam_Engine_Api_Attempts::get_instance();
}
mock_exam_engine_api_attempts()->run();
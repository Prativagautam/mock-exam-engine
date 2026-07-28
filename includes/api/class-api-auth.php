<?php // phpcs:ignore Class file names should be based on the class name with "class-" prepended.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Registration, Login, and Password Reset via REST API.
 *
 * @package    Mock_Exam_Engine
 * @subpackage Mock_Exam_Engine/includes/api
 */
if ( ! class_exists( 'Mock_Exam_Engine_Api_Auth' ) ) {

	class Mock_Exam_Engine_Api_Auth extends Mock_Exam_Engine_Api {

		public function run() {
			$this->type      = 'mock_exam_engine_api_auth';
			$this->rest_base = 'auth';

			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		public function register_routes() {
			$namespace = $this->namespace . $this->version;

			register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/register',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'register_user' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'username' => array(
							'required' => true,
							'type'     => 'string',
						),
						'email'    => array(
							'required' => true,
							'type'     => 'string',
						),
						'password' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				)
			);
		}

		/**
		 * POST /auth/register
		 * Creates a new user, always as a 'subscriber' (placeholder role
		 * until Phase 4 introduces a real 'student' role). The role is
		 * hardcoded here, never taken from client input — a public
		 * registration endpoint must never let the caller choose their
		 * own role.
		 */
		public function register_user( $request ) {
			$username = sanitize_user( $request['username'] );
			$email    = sanitize_email( $request['email'] );
			$password = $request['password']; // Deliberately NOT sanitized — sanitizing would corrupt the password itself. wp_insert_user() hashes it safely.

			if ( username_exists( $username ) ) {
				return new WP_Error( 'rest_username_exists', __( 'That username is already taken.', 'mock-exam-engine' ), array( 'status' => 400 ) );
			}

			if ( email_exists( $email ) ) {
				return new WP_Error( 'rest_email_exists', __( 'That email is already registered.', 'mock-exam-engine' ), array( 'status' => 400 ) );
			}

			if ( ! is_email( $email ) ) {
				return new WP_Error( 'rest_invalid_email', __( 'That email address is not valid.', 'mock-exam-engine' ), array( 'status' => 400 ) );
			}

			$user_id = wp_insert_user(
				array(
					'user_login' => $username,
					'user_email' => $email,
					'user_pass'  => $password,
					'role'       => 'subscriber',
				)
			);

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			return rest_ensure_response(
				array(
					'user_id' => $user_id,
					'message' => __( 'Registration successful. You can now log in.', 'mock-exam-engine' ),
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

function mock_exam_engine_api_auth() { //phpcs:ignore
	return Mock_Exam_Engine_Api_Auth::get_instance();
}
mock_exam_engine_api_auth()->run();
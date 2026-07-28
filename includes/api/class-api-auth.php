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
            register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/login',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'login_user' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'username' => array(
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
            register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/forgot-password',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'forgot_password' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'email' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				)
			);
            register_rest_route(
				$namespace,
				'/' . $this->rest_base . '/reset-password',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reset_password' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'login'        => array(
							'required' => true,
							'type'     => 'string',
						),
						'key'          => array(
							'required' => true,
							'type'     => 'string',
						),
						'new_password' => array(
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
        /**
		 * POST /auth/login
		 * Verifies credentials and starts a real logged-in browser session.
		 *
		 * Uses wp_signon() to check the password, then wp_set_auth_cookie()
		 * to actually log the browser in — this is what a nonce-based
		 * request could never do, since a nonce requires already being
		 * logged in.
		 */
        public function login_user( $request ) {
			$creds = array(
				'user_login'    => sanitize_user( $request['username'] ),
				'user_password' => $request['password'],
				'remember'      => true,
			);

			$user = wp_signon( $creds, is_ssl() );

			if ( is_wp_error( $user ) ) {
				return new WP_Error( 'rest_login_failed', __( 'Invalid username or password.', 'mock-exam-engine' ), array( 'status' => 403 ) );
			}

			wp_set_current_user( $user->ID );

			$expiration = time() + apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user->ID, true );
			$manager    = WP_Session_Tokens::get_instance( $user->ID );
			$token      = $manager->create( $expiration );

			wp_set_auth_cookie( $user->ID, true, is_ssl(), $token );

			// wp_set_auth_cookie() only updates the cookie the BROWSER will send on its
			// NEXT request — it doesn't update PHP's own $_COOKIE for THIS request. Since
			// we want to generate a working nonce right now, in this same request, we
			// manually sync $_COOKIE so wp_create_nonce() computes against the same
			// session token we just wrote into the real cookie.
			$_COOKIE[ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie( $user->ID, $expiration, 'logged_in', $token );

			return rest_ensure_response(
				array(
					'user_id'  => $user->ID,
					'username' => $user->user_login,
					'roles'    => $user->roles,
					'nonce'    => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
        /**
		 * POST /auth/forgot-password
		 * Triggers WordPress's built-in password reset email flow.
		 *
		 * We deliberately return the SAME success message whether or not
		 * the email actually matches an account — this stops an attacker
		 * from using this endpoint to test which emails are registered.
		 */
		public function forgot_password( $request ) {
			$email = sanitize_email( $request['email'] );

			$user = get_user_by( 'email', $email );

			if ( $user ) {
				retrieve_password( $user->user_login );
			}

			return rest_ensure_response(
				array(
					'message' => __( 'If that email is registered, a password reset link has been sent.', 'mock-exam-engine' ),
				)
			);
		}
        /**
		 * POST /auth/reset-password
		 * Validates the reset key emailed to the user, then sets the new password.
		 */
		public function reset_password( $request ) {
			$login = sanitize_user( $request['login'] );
			$key   = sanitize_text_field( $request['key'] );

			$user = check_password_reset_key( $key, $login );

			if ( is_wp_error( $user ) ) {
				// Same "don't leak specifics" principle as Login — whether the
				// key was wrong, expired, or the login didn't match, the caller
				// gets one generic message either way.
				return new WP_Error( 'rest_invalid_reset_key', __( 'This password reset link is invalid or has expired.', 'mock-exam-engine' ), array( 'status' => 400 ) );
			}

			reset_password( $user, $request['new_password'] );

			return rest_ensure_response(
				array(
					'message' => __( 'Your password has been reset. You can now log in.', 'mock-exam-engine' ),
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
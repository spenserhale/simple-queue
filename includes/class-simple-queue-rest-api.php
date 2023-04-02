<?php
/**
 * Simple Queue REST API Class
 *
 * @package SimpleQueue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Simple_Queue_REST_API
 *
 * Handles the REST API functionality for the Simple Queue plugin.
 */
class Simple_Queue_REST_API {

	/**
	 * The namespace for the REST API routes.
	 */
	private const API_NAMESPACE = 'simple-queue/v1';

	/**
	 * The job manager instance.
	 */
	private Simple_Queue_Job_Manager $job_manager;

	/**
	 * Initializes the REST API class and sets up the hooks.
	 *
	 * @param Simple_Queue_Job_Manager $job_manager The job manager instance.
	 */
	public function __construct( Simple_Queue_Job_Manager $job_manager ) {
		$this->job_manager = $job_manager;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers the REST API routes.
	 */
	public function register_routes() {
		register_rest_route( self::API_NAMESPACE, '/job', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'hook' => [
					'required' => true,
				],
			],
		] );

		register_rest_route( self::API_NAMESPACE, '/job/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'status' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'id' => [
					'validate_callback' => 'is_numeric',
					'required'          => true,
				],
			],
		] );

		register_rest_route( self::API_NAMESPACE, '/job/(?P<id>\d+)/results', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'results' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'id' => [
					'validate_callback' => 'is_numeric',
					'required'          => true,
				],
			],
		] );

		register_rest_route( self::API_NAMESPACE, '/job/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'id' => [
					'validate_callback' => 'is_numeric',
					'required'          => true,
				],
			],
		] );
	}

	public function permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Starts a job.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return WP_REST_Response|WP_Error The REST API response object or an error object.
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$hook   = $request->get_param( 'hook' );
		$job_id = $this->job_manager->create( $hook );

		if ( $job_id instanceof WP_Error ) {
			return new WP_Error( 'create_job_failed', $job_id->get_error_message(), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( [ 'id' => $job_id ], 201 );
	}

	/**
	 * Gets the status of a job.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return WP_REST_Response|WP_Error The REST API response object or an error object.
	 */
	public function status( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$job_id = $request->get_param( 'id' );
		$job    = $this->job_manager->find( $job_id );

		if ( $job instanceof WP_Error ) {
			return new WP_Error( 'job_not_found', $job->get_error_message(), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( [ 'status' => $job->status ], 200 );
	}

	/**
	 * Gets the results of a job.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return WP_REST_Response|WP_Error The REST API response object or an error object.
	 */
	public function results( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$job_id = $request->get_param( 'id' );
		$job    = $this->job_manager->find( $job_id );

		if ( $job instanceof WP_Error ) {
			return new WP_Error( 'job_not_found', $job->get_error_message(), [ 'status' => 404 ] );
		}

		if ( empty( $job->results ) ) {
			return new WP_Error( 'no_results', 'No results available for the specified job.', [ 'status' => 404 ] );
		}

		return new WP_REST_Response( [ 'results' => $job->results ], 200 );
	}

	/**
	 * Deletes a job.
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return WP_REST_Response|WP_Error The REST API response object or an error object.
	 */
	public function delete( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$job_id  = $request->get_param( 'id' );
		$deleted = $this->job_manager->delete( $job_id );

		if ( $deleted instanceof WP_Error ) {
			return new WP_Error( 'delete_job_failed', $deleted->get_error_message(), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( null, 204 );
	}
}

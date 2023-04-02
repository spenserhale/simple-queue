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

class Simple_Queue_Job_Manager {
	private Simple_Queue_Job_Repository $repository;

	public function __construct( Simple_Queue_Job_Repository $repository ) {
		$this->repository = $repository;
		add_filter( 'simple_queue_process_job', [ $this, 'execute' ] );
	}

	private function validate_hook( string $hook ): bool|WP_Error {
		// Check if the hook is queueable.
		$queueable = apply_filters( 'simple_queue_is_hook_queueable', false, $hook );
		if ( ! $queueable ) {
			return new WP_Error( 'not_queueable', 'The provided hook is not queueable.' );
		}

		// Validate the hook.
		if ( ! has_filter( $hook ) ) {
			return new WP_Error( 'invalid_hook', 'No listeners found for the provided hook.' );
		}

		return true;
	}

	public function create( string $hook ): int|WP_Error {
		// Validate the hook.
		$validation = $this->validate_hook( $hook );
		if ( $validation instanceof WP_Error ) {
			return $validation;
		}

		$job_id = $this->repository->create( $hook );

		// If creating a job failed, return the error.
		if ( $job_id instanceof WP_Error ) {
			return $job_id;
		}

		// Schedule the background process with the job's ID.
		$event = wp_schedule_single_event( time(), 'simple_queue_process_job', [ 'job_id' => $job_id ], true );
		if ( $event instanceof WP_Error ) {
			$error = new WP_Error( 'schedule_failed', 'Failed to schedule the job.' );
			$error->add_data( $event, 'event' );
			return $error;
		}

		return $job_id;
	}

	public function execute( int $job_id ): mixed {
		$job = $this->repository->find( $job_id );
		if ( $job instanceof WP_Error ) {
			return $job;
		}

		// Revalidate the job's hook.
		$validation = $this->validate_hook( $job->hook );
		if ( $validation instanceof WP_Error ) {
			$updated = $this->repository->failed( $job->id, $validation );
			if ( $updated instanceof WP_Error ) {
				$validation->add_data( $updated, 'update' );
			}

			return $validation;
		}

		try {
			$result = apply_filters( $job->hook, null );

			if ( $result instanceof WP_Error ) {
				$updated = $this->repository->failed( $job->id, $result );
				if( $updated instanceof WP_Error ) {
					$result->add_data( $updated, 'update' );
				}
			} else {
				$updated = $this->repository->complete( $job->id, $result );
				if( $updated instanceof WP_Error ) {
					$updated->add_data( $result, 'result' );
					$result = $updated;
				}
			}

			return $result;
		} catch ( Throwable $e ) {
			$error = new WP_Error( 'job_execution_failed', $e->getMessage() );
			$updated = $this->repository->failed( $job->id, $error );
			if( $updated instanceof WP_Error ) {
				$error->add_data( $updated, 'update' );
			}
			return $error;
		}
	}

	/**
	 * Get the status of a job.
	 *
	 * @param int $job_id The Job ID.
	 * @return string|WP_Error The status of the job or a WP_Error if the job cannot be found or fetched.
	 */
	public function status( int $job_id ): string|WP_Error {
		$job = $this->repository->find( $job_id );
		if ( $job instanceof WP_Error ) {
			return $job;
		}

		return match ( $job->status ) {
			0 => 'pending',
			1 => 'processing',
			2 => 'completed',
			3 => 'failed',
			default => new WP_Error( 'invalid_status', 'The job has an invalid status.' ),
		};
	}
	/**
	 * Delete a job.
	 *
	 * @param int $job_id The Job ID.
	 * @return bool|WP_Error True if the job is deleted, or a WP_Error if the job cannot be deleted.
	 */
	public function delete( int $job_id ): bool|WP_Error {
		$job = $this->repository->find( $job_id );
		if ( $job instanceof WP_Error ) {
			return $job;
		}

		$deleted = $this->repository->delete( $job_id );
		if ( $deleted instanceof WP_Error ) {
			return $deleted;
		}

		return true;
	}

	/**
	 * Find a job by ID.
	 *
	 * @param  int  $job_id
	 * @return stdClass|WP_Error
	 */
	public function find( int $job_id ): stdClass|WP_Error {
		return $this->repository->find( $job_id );
	}
}

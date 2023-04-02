<?php
/**
 * Simple Queue Job Repository Class
 *
 * @package SimpleQueue
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Class Simple_Queue_Job_Repository
 *
 * Handles the CRUD operations for the jobs table.
 */
class Simple_Queue_Job_Repository {

	public function create(string $hook): int|WP_Error {
		// Insert the job into the database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'simple_queue_jobs';
		$inserted = $wpdb->insert(
			$table_name,
			[
				'status' => 0, // Pending status.
				'hook' => $hook,
				'results' => '', // Empty results.
			],
			['%d', '%s', '%s']
		);

		// Check if the insert was successful.
		if (!$inserted) {
			return new WP_Error('insert_failed', 'Failed to insert the job.');
		}

		// Return the new job's ID.
		return $wpdb->insert_id;
	}

	public function update(int $id, int $status, mixed $result): bool|WP_Error {
		// Update the status and result in the database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'simple_queue_jobs';
		$updated = $wpdb->update(
			$table_name,
			[
				'status' => $status,
				'results' => maybe_serialize($result),
			],
			['id' => $id],
			['%d', '%s'],
			['%d']
		);

		if (false === $updated) {
			return new WP_Error('update_failed', 'Failed to update the job status and result.');
		}

		return true;
	}

	public function complete(int $id, mixed $result): bool|WP_Error {
		return $this->update($id, 2, $result); // Mark the job as done.
	}

	public function failed(int $id, WP_Error $error): bool|WP_Error {
		return $this->update($id, 3, $error); // Mark the job as failed.
	}

	public function find(int $job_id): stdClass|WP_Error {
		global $wpdb;
		$table_name = $wpdb->prefix . 'simple_queue_jobs';
		$job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $job_id));

		if ($job) {
			$job->results = maybe_unserialize($job->results);
			return $job;
		}

		return new WP_Error('job_not_found', 'The specified job could not be found.');
	}

	public function delete(int $job_id): bool|WP_Error {
		global $wpdb;
		$table_name = $wpdb->prefix . 'simple_queue_jobs';
		$deleted = $wpdb->delete($table_name, ['id' => $job_id], ['%d']);

		if ($deleted) {
			return true;
		}

		return new WP_Error('job_delete_failed', 'Failed to delete the specified job.');
	}
}

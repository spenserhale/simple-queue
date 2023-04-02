<?php
/**
 * Simple Queue Activator Class
 *
 * @package SimpleQueue
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Class Simple_Queue_Activator
 *
 * Handles the plugin activation process, such as creating the necessary database table.
 */
class Simple_Queue_Activator {

	/**
	 * The activation method to be called on plugin activation.
	 */
	public static function activate() {
		global $wpdb;

		// Define the custom table name.
		$table_name = $wpdb->prefix . 'simple_queue_jobs';

		// Check if the table already exists.
		if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
			// Table doesn't exist, so let's create it.
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                status smallint(6) NOT NULL,
                hook varchar(255) NOT NULL,
                results longtext,
                PRIMARY KEY (id)
            ) {$charset_collate};";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			// Store the current plugin version as the database version.
			update_option('simple_queue_db_version', SIMPLE_QUEUE_VERSION);
		}
	}

	/**
	 * The maybe_activate method to be called conditionally.
	 */
	public static function maybe_activate() {
		if ((defined('WP_CLI') && WP_CLI) || (defined('DOING_CRON') && DOING_CRON) || (is_admin() && !wp_doing_ajax()) ) {
			$current_db_version = get_option('simple_queue_db_version');

			// Check if the database version needs to be updated.
			if ($current_db_version !== SIMPLE_QUEUE_VERSION) {
				self::activate();
				update_option('simple_queue_db_version', SIMPLE_QUEUE_VERSION);
			}
		}
	}
}

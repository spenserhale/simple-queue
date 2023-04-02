<?php
/** *
 * Plugin Name: Simple Queue
 * Description: A simple WordPress plugin to manage and process queued jobs from an API.
 * Version: 1.0.0
 * Author: Spenser Hale
 * Author URI: https://profiles.wordpress.org/spenserhale/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: simple-queue
 * Requires at least: 5.8
 * Requires PHP: 8.1
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Define plugin constants.
define('SIMPLE_QUEUE_VERSION', '1.0.0');
define('SIMPLE_QUEUE_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Require all classes.
require_once SIMPLE_QUEUE_PLUGIN_DIR . 'includes/class-simple-queue-activator.php';
require_once SIMPLE_QUEUE_PLUGIN_DIR . 'includes/class-simple-queue-job-repository.php';
require_once SIMPLE_QUEUE_PLUGIN_DIR . 'includes/class-simple-queue-job-manager.php';
require_once SIMPLE_QUEUE_PLUGIN_DIR . 'includes/class-simple-queue-rest-api.php';

/**
 * Initializes the Simple Queue plugin.
 */
function simple_queue_plugin_init() {

	// Instantiate objects.
	$repository = new Simple_Queue_Job_Repository();
	$job_manager = new Simple_Queue_Job_Manager($repository);
	$rest_api = new Simple_Queue_REST_API($job_manager);

}

// Register the plugin activation and deactivation hooks.
add_action('init', 'simple_queue_plugin_init');
add_action('wp_loaded', ['Simple_Queue_Activator', 'maybe_activate']);
register_activation_hook(__FILE__, ['Simple_Queue_Activator', 'activate']);

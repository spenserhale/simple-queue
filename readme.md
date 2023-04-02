# Simple Queue Plugin

The Simple Queue plugin provides a simple job queue system for running long-running tasks in the background. You can use it to schedule tasks that would otherwise timeout or exceed PHP's maximum execution time.

The plugin uses WordPress's built-in cron system to schedule tasks, and provides a REST API for managing jobs.

## Usage

To use the Simple Queue plugin, you need to follow these steps:

### 1) Register your custom hook as queueable:

```php
add_filter('simple_queue_is_hook_queueable', function ($queueable, $hook) {
    if ($hook === 'my_custom_hook') {
        return true;
    }
    return $queueable;
}, 10, 2);

```
This filter determines whether a hook is queueable. You need to filter the simple_queue_is_hook_queueable hook for your custom hook and return true for it to be added to the job queue.

### 2) Register a listener for your hook:
```php
add_filter('my_custom_hook', 'my_custom_hook_listener');

function my_custom_hook_listener() {    
    return my_custom_business_logic();
}
```
This is the listener function that runs when your hook is processed. You need to register a filter for your custom hook and define the listener function.

### 3) Create a job 
Run a POST request to the /wp-json/simple-queue/v1/job endpoint:
```bash
curl -X POST https://example.com/wp-json/simple-queue/v1/job \
     -d '{"hook": "my_custom_hook"}' \
     -H 'Content-Type: application/json'
     -H 'Authorization: Basic TOKEN'
```
This creates a job for your custom hook and returns the ID of the new job.

### 4) Check the status of a job
Run a GET request to the /wp-json/simple-queue/v1/job/{id} endpoint:
```bash
curl https://example.com/wp-json/simple-queue/v1/job/{id} \
     -H 'Authorization: Basic TOKEN'
```
This returns the status of the job of the given ID.

### 5) Get the results of a job
Run a GET request to the /wp-json/simple-queue/v1/job/{id}/results endpoint:
```bash
curl https://example.com/wp-json/simple-queue/v1/job/{id}/results \
     -H 'Authorization: Basic TOKEN'
```
This returns the results of the job of the given ID.

### 6) Delete a job 
Run a DELETE request to the /wp-json/simple-queue/v1/job/{id} endpoint:
```bash
curl -X DELETE https://example.com/wp-json/simple-queue/v1/job/{id} \
     -H 'Authorization: Basic TOKEN'
```
This deletes the job with the specified ID.

## Installation

1. Download the plugin from the WordPress Plugin Repository or GitHub.
2. Upload the plugin to your WordPress site's plugin directory.
3. Activate the plugin in your WordPress site's admin dashboard.

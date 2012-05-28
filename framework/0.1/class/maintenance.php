<?php

/***************************************************

	//--------------------------------------------------
	// Site config



	//--------------------------------------------------
	// Example setup



***************************************************/

// TODO: Add an admin interface somewhere which can show the tasks being run, can cancel them, or stack them up.

//--------------------------------------------------
// Is enabled

	if (config::get('maintenance.active') !== true) {
		exit_with_error('Maintenance tasks disabled.');
	}

//--------------------------------------------------
// Maintenance class

	class maintenance_base extends base {

		//--------------------------------------------------
		// Variables

			private $tasks_dir;
			private $tasks_run;
			private $task_paths;
			private $run_id = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Tasks

					$this->tasks_dir = APP_ROOT . '/tasks/';
					$this->tasks_run = array();
					$this->task_paths = array();

					if ($handle = opendir($this->tasks_dir)) {
						while (false !== ($file = readdir($handle))) {
							if (is_file($this->tasks_dir . $file) && preg_match('/^([0-9]+\.)?([a-zA-Z0-9_]+)\.php$/', $file, $matches)) {

								$this->task_paths[$matches[2]] = $this->tasks_dir . $file;

							}
						}
					}

					asort($this->task_paths);

			}

		//--------------------------------------------------
		// Run

			public function run() {

				//--------------------------------------------------
				// Hide debug output

					config::set('debug.show', false);

				//--------------------------------------------------
				// Make sure we have plenty of memory

					ini_set('memory_limit', '1024M');

				//--------------------------------------------------
				// Main include

					$include_path = APP_ROOT . DS . 'support' . DS . 'core' . DS . 'main.php';
					if (is_file($include_path)) {
						require_once($include_path);
					}

				//--------------------------------------------------
				// Clear old locks

					$db = $this->db_get();

					$db->query('SELECT
									id,
									run_start
								FROM
									' . DB_PREFIX . 'maintenance
								WHERE
									run_end = "0000-00-00 00:00:00" AND
									run_start < "' . $db->escape(date('Y-m-d H:i:s', strtotime('-2 hours'))) . '"');

					if ($row = $db->fetch_assoc()) {

						$db->query('DELETE FROM
										' . DB_PREFIX . 'maintenance
									WHERE
										id = "' . $db->escape($row['id']) . '" AND
										run_end = "0000-00-00 00:00:00"');

						report_add('Deleted old maintenance lock (' . $row['id'] . ' / ' . $row['run_start'] . ')');

					}

				//--------------------------------------------------
				// Create maintenance record (lock).

					$db->query('SELECT 1 FROM ' . DB_PREFIX . 'maintenance WHERE run_end = "0000-00-00 00:00:00"');
					if ($db->num_rows() > 0) {
						exit_with_error('Maintenance script is already running (A).');
					}

					$db->query('INSERT INTO ' . DB_PREFIX . 'maintenance (
									run_start,
									run_end
								) VALUES (
									"' . $db->escape(date('Y-m-d H:i:s')) . '",
									"0000-00-00 00:00:00"
								)');

					$this->run_id = $db->insert_id();

					$db->query('SELECT 1 FROM ' . DB_PREFIX . 'maintenance WHERE run_end = "0000-00-00 00:00:00"');
					if ($db->num_rows() != 1) {
						exit_with_error('Maintenance script is already running (B).');
					}

				//--------------------------------------------------
				// Cleanup

					$r = $db->query('SELECT
										id
									FROM
										' . DB_PREFIX . 'maintenance
									WHERE
										run_end != "0000-00-00 00:00:00" AND
										run_end < "' . $db->escape(date('Y-m-d H:i:s', strtotime('-1 month'))) . '"');

					while ($row = $db->fetch_assoc($r)) {

						$db->query('DELETE FROM
										' . DB_PREFIX . 'maintenance_task
									WHERE
										run_id = "' . $db->escape($row['id']) . '"');

						$db->query('DELETE FROM
										' . DB_PREFIX . 'maintenance
									WHERE
										id = "' . $db->escape($row['id']) . '"');

					}

				//--------------------------------------------------
				// Tasks

					foreach ($this->task_paths as $task_name => $task_path) {
						if (!in_array($task_name, $this->tasks_run)) {

							//--------------------------------------------------
							// Action object

								$task = new task($task_name, $this, $this->run_id);

								$success = $task->run_wrapper();

								if ($success !== false) {
									$this->tasks_run[] = $task_name;
								}

							//--------------------------------------------------
							// If we should halt this maintenance run

								$halt_maintenance_run = $task->halt_maintenance_run();
								if ($halt_maintenance_run) {
									break;
								}

						}
					}

				//--------------------------------------------------
				// Mark as done

					$db->query('UPDATE
									' . DB_PREFIX . 'maintenance
								SET
									run_end = "' . $db->escape(date('Y-m-d H:i:s')) . '"
								WHERE
									run_end = "0000-00-00 00:00:00"
								LIMIT
									1');

				//--------------------------------------------------
				// Return list of ran tasks

					return $this->tasks_run;

			}

			public function require_task_run($task_name) {

				if (!in_array($task_name, $this->tasks_run)) {

					$task = new task($task_name, $this, $this->run_id);

					$success = $task->run_wrapper(true);

					if ($success) {
						$this->tasks_run[] = $task_name;
					}

					return $success;

				}

				return true;

			}

			public function execute($task_name) {

				$task = $this->task_get($task_name);

				$success = $task->run_wrapper(true);

				if ($success) {
					$this->tasks_run[] = $task_name;
				}

				return $success;

			}

		//--------------------------------------------------
		// Return task

			public function task_get($task_name) {
				return new task($task_name, $this);
			}

			public function task_path_get($task_name) {
				if (isset($this->task_paths[$task_name])) {
					return $this->task_paths[$task_name];
				} else {
					return false;
				}
			}

		//--------------------------------------------------
		// Test support

			public function test() {

				//--------------------------------------------------
				// Only on stage

					if (SERVER != 'stage') {
						exit('Disabled');
					}

				//--------------------------------------------------
				// Execute task

					$task_name = request('execute');

					if (isset($this->task_paths[$task_name])) {

						$output = $this->execute($task_name);

						if ($output !== false && $output === '') {
							$output = '<p>No output.</p>';
						}

						exit($output);

					}

				//--------------------------------------------------
				// Create simple index of tasks

					$this->title_set('Maintenance tasks');

					$html = '
						<h2>Tasks</h2>
						<ul>';

					$url = url('./');

					foreach ($this->task_paths as $task_name => $task_path) {
						$html .= '
								<li><a href="' . html($url->get(array('execute' => $task_name))) . '">' . html(ucfirst(str_replace('_', ' ', $task_name))) . '</a></li>';
					}

					$html .= '
						</ul>';

					$view = new view();
					$view->render_html($html);

			}

	}

//--------------------------------------------------
// Action class

	class task_base extends base {

		//--------------------------------------------------
		// Variables

			protected $task_name = NULL;
			protected $maintenance = NULL;
			protected $run_id = NULL;
			protected $mode = NULL;
			protected $last_run = NULL;
			protected $halt_maintenance_run = false;

		//--------------------------------------------------
		// Setup

			public function __construct($task_name, $maintenance, $run_id = 0, $mode = 'wrapper') {

				//--------------------------------------------------
				// Details

					$this->task_name = $task_name;
					$this->maintenance = $maintenance;
					$this->run_id = $run_id;
					$this->mode = $mode;

				//--------------------------------------------------
				// Last run

					$db = $this->db_get();

					if ($this->run_id > 0) {

						$db->query('SELECT
										created
									FROM
										' . DB_PREFIX . 'maintenance_task
									WHERE
										task = "' . $db->escape($this->task_name) . '"
									ORDER BY
										created DESC
									LIMIT
										1');

						if ($row = $db->fetch_assoc()) {
							$this->last_run = strtotime($row['created']);
						}

					}

				//--------------------------------------------------
				// Init

					$this->init();

			}

		//--------------------------------------------------
		// Task setup functions

			public function email_addresses_get() {
				return array();
			}

			public function should_run() {
				return true; // This task should always run
			}

		//--------------------------------------------------
		// Task support functions

			public function halt_maintenance_run() {
				return $this->halt_maintenance_run;
			}

			protected function require_task_run($task_name) {
				return $this->maintenance->require_task_run($task_name);
			}

			protected function error_fatal($error = NULL) {
				$this->halt_maintenance_run = true;
				if ($error !== NULL) {

					report_add($error, 'error');

					if (config::get('output.mime') == 'text/plain') {
						echo ucfirst($this->task_name) . ' - Fatal Error:' . "\n";
						echo ' ' . $error . "\n\n";
					}

				}
				return false;
			}

			protected function error_harmless($error = NULL) {
				if ($error !== NULL) {

					report_add($error, 'error');

					if (config::get('output.mime') == 'text/plain') {
						echo ucfirst($this->task_name) . ' - Harmless Error:' . "\n";
						echo ' ' . $error . "\n\n";
					}

				}
				return false;
			}

		//--------------------------------------------------
		// Run

			protected function init() {
			}

			public function prep() {
				return true; // Success
			}

			public function run() {
				return '';
			}

			final public function run_wrapper($force = false) {

				//--------------------------------------------------
				// Only works in wrapper mode

					if ($this->mode != 'wrapper') {
						exit_with_error('Cannot call run_wrapper() at this time.');
					}

				//--------------------------------------------------
				// Include the script

					$gateway = $this;

					$task_object = $this->task_name . '_task';

					$task_path = $this->maintenance->task_path_get($this->task_name);

					if (is_file($task_path)) {

						ob_start();

						if (!class_exists($task_object)) {
							require_once($task_path);
						}

						$task_output_html = ob_get_clean();

					} else {

						return $this->error_fatal('Could not load task "' . $this->task_name . '"');

					}

				//--------------------------------------------------
				// Email title

					$email_title = ucfirst(str_replace('_', ' ', $this->task_name)) . ' @ ' . date('Y-m-d H:i:s');

					$email_addresses = array();

				//--------------------------------------------------
				// Object mode support

					if (class_exists($task_object)) {

						//--------------------------------------------------
						// Setup

							$task = new $task_object($this->task_name, $this->maintenance, $this->run_id, 'run');

						//--------------------------------------------------
						// Should run

							if (!$force && !$task->should_run()) {
								return false;
							}

						//--------------------------------------------------
						// Prep

							$prep_result = $task->prep();

						//--------------------------------------------------
						// Run

							if (is_string($prep_result) && strlen($prep_result) > 0) {

								$task_output_html = $prep_result;

							} else if ($prep_result !== false && $task->halt_maintenance_run() === false) {

								$task_output_html = trim($task->run());

							} else {

								$task_output_html = '';

							}

						//--------------------------------------------------
						// Email

							if (method_exists($task, 'email_title_get')) {
								$email_title = $task->email_title_get();
							}

							$email_addresses = $task->email_addresses_get();

						//--------------------------------------------------
						// Halt of maintenance run

							if ($task->halt_maintenance_run() == true) {
								$this->halt_maintenance_run = true;
							}

					}

				//--------------------------------------------------
				// Log

					$db = $this->db_get();

					if ($this->run_id > 0 && $this->halt_maintenance_run === false) {

						$db->query('INSERT INTO ' . DB_PREFIX . 'maintenance_task (
										id,
										task,
										run_id,
										output,
										created
									) VALUES (
										"",
										"' . $db->escape($this->task_name) . '",
										"' . $db->escape($this->run_id) . '",
										"' . $db->escape($task_output_html) . '",
										"' . $db->escape(date('Y-m-d H:i:s')) . '"
									)');

					}

				//--------------------------------------------------
				// Send

					if ($this->run_id > 0 && $this->halt_maintenance_run === false && $task_output_html != '') {

						if (isset($email_addresses[SERVER])) {
							$email_addresses = $email_addresses[SERVER];
						}

						$email = new email();
						$email->subject_set($email_title);
						$email->body_html_add($task_output_html);
						$email->send($email_addresses);

					}

				//--------------------------------------------------
				// Return

					return $task_output_html;

			}

	}

//--------------------------------------------------
// Tables exist

	if (config::get('debug.level') > 0) {

		debug_require_db_table('maintenance', '
				CREATE TABLE [TABLE] (
					id int(11) NOT NULL AUTO_INCREMENT,
					run_start datetime NOT NULL,
					run_end datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY run_end (run_end)
				);');

		debug_require_db_table('maintenance_task', '
				CREATE TABLE [TABLE] (
					id int(11) NOT NULL AUTO_INCREMENT,
					task varchar(20) NOT NULL,
					run_id int(11) NOT NULL,
					output text NOT NULL,
					created datetime NOT NULL,
					PRIMARY KEY (id),
					KEY task (task, created)
				);');

	}

?>
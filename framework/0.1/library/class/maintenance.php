<?php

// TODO: Handle switching from BST to GMT

//--------------------------------------------------
// http://www.phpprime.com/doc/setup/jobs/
//--------------------------------------------------

	class maintenance_base extends check {

		//--------------------------------------------------
		// Variables

			private $job_dir = NULL;
			private $job_paths = array();
			private $jobs_run = array();
			private $run_id = NULL;
			private $result_url = NULL;
			private $time_out = 1200; // 20 minutes

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Jobs

					$this->job_dir = APP_ROOT . '/job/';

					if ($handle = opendir($this->job_dir)) {
						while (false !== ($file = readdir($handle))) {
							if (is_file($this->job_dir . $file) && preg_match('/^([0-9]+\.)?([a-zA-Z0-9_\-]+)\.php$/', $file, $matches)) {

								$this->job_paths[$matches[2]] = $this->job_dir . $file;

							}
						}
					}

					asort($this->job_paths);

			}

			public function result_url_set($url) {
				$this->result_url = $url;
			}

			public function time_out_set($time) {
				$this->time_out = $time;
			}

		//--------------------------------------------------
		// Run

			public function run() {

				//--------------------------------------------------
				// Resources

					$db = db_get();

					$now = new timestamp();

				//--------------------------------------------------
				// Make sure we have plenty of memory

					ini_set('memory_limit', '1024M');

				//--------------------------------------------------
				// Run setup

					$include_path = APP_ROOT . '/library/setup/setup.php';
					if (is_file($include_path)) {
						script_run_once($include_path);
					}

				//--------------------------------------------------
				// Cleanup

					$archive_date = new timestamp('-2 months'); // Some jobs only run once a month, so needs some overlap

					$db->query('DELETE FROM
									' . DB_PREFIX . 'system_maintenance
								WHERE
									run_end != "0000-00-00 00:00:00" AND
									run_end < "' . $db->escape($archive_date) . '"');

					$db->query('DELETE FROM
									' . DB_PREFIX . 'system_maintenance_job
								WHERE
									created < "' . $db->escape($archive_date) . '"');

				//--------------------------------------------------
				// Clear old (but still open) run records

					$clear_date = new timestamp('-2 hours');

					$db->query('SELECT
									id,
									run_start
								FROM
									' . DB_PREFIX . 'system_maintenance
								WHERE
									run_end = "0000-00-00 00:00:00" AND
									run_start < "' . $db->escape($clear_date) . '"');

					if ($row = $db->fetch_row()) {

						$db->query('DELETE FROM
										' . DB_PREFIX . 'system_maintenance
									WHERE
										id = "' . $db->escape($row['id']) . '" AND
										run_end = "0000-00-00 00:00:00"');

						report_add('Deleted old maintenance run record (' . $row['id'] . ' / ' . $row['run_start'] . ')');

					}

				//--------------------------------------------------
				// Create proper lock

					$lock_type = 'maintenance';

					if ($this->result_url) {

						$loading = new loading(array(
								'time_out' => $this->time_out,
								'lock_type' => $lock_type,
							));

						$loading->check();

						if (!$loading->start('Starting')) {

							$this->result_url->param_set('state', 'locked');
							$this->result_url->param_set('test', 'file');
							$this->result_url->param_set('time', time());

							redirect($this->result_url);

						}

					} else {

						$lock = new lock($lock_type);
						$lock->time_out_set($this->time_out);

						if (!$lock->open()) {
							return 'lock file';
						}

					}

				//--------------------------------------------------
				// Quick db check, incase lock file has been deleted

					$db->query('SELECT 1 FROM ' . DB_PREFIX . 'system_maintenance WHERE run_end = "0000-00-00 00:00:00" OR run_end = "' . $db->escape($now) . '"');
					if ($db->num_rows() > 0) {
						if ($this->result_url) {

							$this->result_url->param_set('state', 'locked');
							$this->result_url->param_set('test', 'db');
							$this->result_url->param_set('time', time());

							$loading->done($this->result_url);

						} else {

							$lock->close();

						}
						return 'database record';
					}

				//--------------------------------------------------
				// Create maintenance run record

					$db->insert(DB_PREFIX . 'system_maintenance', array(
							'id'        => '',
							'run_start' => $now,
							'run_end'   => '0000-00-00 00:00:00',
						));

					$this->run_id = $db->insert_id();

					$db->query('SELECT 1 FROM ' . DB_PREFIX . 'system_maintenance WHERE run_end = "0000-00-00 00:00:00"');
					if ($db->num_rows() != 1) {
						exit_with_error('Maintenance script is already running, after lock file opening.');
					}

				//--------------------------------------------------
				// Jobs

					foreach ($this->job_paths as $job_name => $job_path) {
						if (!in_array($job_name, $this->jobs_run)) {

							//--------------------------------------------------
							// Check and update lock status

								if ($this->result_url) {
									$result = $loading->update('Running: ' . $job_name);
								} else {
									$result = $lock->open();
								}

								if (!$result) {
									report_add('Lost lock when attempting to run job "' . $job_name . '"', 'error');
									break;
								}

							//--------------------------------------------------
							// Action object

								$job = new job($job_name, $this, $this->run_id);

								$result = $job->run_wrapper();

								if ($result !== false) {
									$this->jobs_run[] = $job_name;
								}

							//--------------------------------------------------
							// If we should halt this maintenance run

								$halt_maintenance_run = $job->halt_maintenance_run();
								if ($halt_maintenance_run) {
									break;
								}

						}
					}

				//--------------------------------------------------
				// Mark as done

					$db->query('UPDATE
									' . DB_PREFIX . 'system_maintenance
								SET
									run_end = "' . $db->escape($now) . '"
								WHERE
									run_end = "0000-00-00 00:00:00"
								LIMIT
									1');

				//--------------------------------------------------
				// Close lock

					if ($this->result_url) {

						$this->result_url->param_set('state', 'complete');
						$this->result_url->param_set('time', time());
						$this->result_url->param_set('jobs', implode('|', $this->jobs_run));

						$loading->done($this->result_url);

					} else {

						$lock->close();

					}

				//--------------------------------------------------
				// Return list of ran jobs

					return $this->jobs_run;

			}

			public function require_job_run($job_name) {
				if (!in_array($job_name, $this->jobs_run)) {
					$this->execute($job_name);
				}
				return true;
			}

			public function execute($job_name) {
				$job = new job($job_name, $this, $this->run_id);
				return $job->run_wrapper(true); // Force the running of the job, bypassing should_run() check
			}

			public function job_paths_get() {
				return $this->job_paths;
			}

			public function job_path_get($job_name) {
				if (isset($this->job_paths[$job_name])) {
					return $this->job_paths[$job_name];
				} else {
					return false;
				}
			}

	}

//--------------------------------------------------
// Action class

	class job_base extends check {

		//--------------------------------------------------
		// Variables

			protected $job_name = NULL;
			protected $maintenance = NULL;
			protected $run_id = NULL;
			protected $mode = NULL;
			protected $last_run = NULL;
			protected $halt_maintenance_run = false;

		//--------------------------------------------------
		// Setup

			public function __construct($job_name, $maintenance, $run_id = NULL, $mode = 'wrapper') {

				//--------------------------------------------------
				// Resources

					$db = db_get();

				//--------------------------------------------------
				// Details

					$this->job_name = $job_name;
					$this->maintenance = $maintenance;
					$this->run_id = $run_id;
					$this->mode = $mode;

					if ($this->run_id !== NULL) {

						$db->query('SELECT
										created
									FROM
										' . DB_PREFIX . 'system_maintenance_job
									WHERE
										job = "' . $db->escape($this->job_name) . '"
									ORDER BY
										created DESC
									LIMIT
										1');

						if ($row = $db->fetch_row()) {
							$this->last_run = new timestamp($row['created'], 'db');
						}

					}

				//--------------------------------------------------
				// Init

					$this->init();

			}

		//--------------------------------------------------
		// Job setup functions

			public function email_addresses_get() {
				return array();
			}

			public function email_title_get() {
				$now = new timestamp();
				return ref_to_human($this->job_name) . ' @ ' . $now->format('Y-m-d H:i:s');
			}

			public function should_run() {
				return true; // This job should always run
			}

		//--------------------------------------------------
		// Job support functions

			public function halt_maintenance_run() {
				return $this->halt_maintenance_run;
			}

			protected function require_job_run($job_name) {
				return $this->maintenance->require_job_run($job_name);
			}

			protected function error_fatal($error = NULL) {
				$this->halt_maintenance_run = true;
				if ($error !== NULL) {

					report_add($error, 'error');

					if (REQUEST_MODE == 'cli' || config::get('debug.level') > 0) {
						echo ucfirst($this->job_name) . ' - Fatal Error:' . "\n";
						echo ' ' . $error . "\n\n";
					}

				}
				return false;
			}

			protected function error_harmless($error = NULL) {
				if ($error !== NULL) {

					report_add($error, 'error');

					if (REQUEST_MODE == 'cli' || config::get('debug.level') > 0) {
						echo ucfirst($this->job_name) . ' - Harmless Error:' . "\n";
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

					$job_object = str_replace('-', '_', $this->job_name) . '_job';

					$job_path = $this->maintenance->job_path_get($this->job_name);

					if (!is_file($job_path)) {
						return $this->error_fatal('Could not load job "' . $this->job_name . '"');
					}

					ob_start();

					if (!class_exists($job_object, false)) { // Don't use autoloader
						script_run_once($job_path);
					}

					$job_output_html = ob_get_clean();

				//--------------------------------------------------
				// Object mode support

					if (class_exists($job_object)) {

						//--------------------------------------------------
						// Setup

							$job = new $job_object($this->job_name, $this->maintenance, $this->run_id, 'run');

						//--------------------------------------------------
						// Should run

							if (!$force && !$job->should_run()) {
								return false;
							}

						//--------------------------------------------------
						// Prep and run

							ob_start();

							$prep_result = $job->prep();

							if (is_string($prep_result) && strlen($prep_result) > 0) {

								$job_output_html .= $prep_result;

							}

							if ($prep_result !== false && $job->halt_maintenance_run() === false) {

								$job_output_html .= trim($job->run());

							}

							$job_output_html = ob_get_clean() . $job_output_html;

						//--------------------------------------------------
						// Email

							$email_title = $job->email_title_get();
							$email_addresses = $job->email_addresses_get();

						//--------------------------------------------------
						// Halt of maintenance run

							if ($job->halt_maintenance_run() == true) {
								$this->halt_maintenance_run = true;
							}

					} else {

						//--------------------------------------------------
						// Email

							$email_title = $this->email_title_get();
							$email_addresses = array();

					}

				//--------------------------------------------------
				// Log

					if ($this->run_id !== NULL && $this->halt_maintenance_run === false) {

						$db = db_get();

						$now = new timestamp();

						$db->insert(DB_PREFIX . 'system_maintenance_job', array(
								'job'     => $this->job_name,
								'run_id'  => $this->run_id,
								'output'  => $job_output_html,
								'created' => $now,
							));

					}

				//--------------------------------------------------
				// Send

					if ($this->run_id !== NULL && $job_output_html != '') {
						if ($this->halt_maintenance_run === false) {

							if (isset($email_addresses[SERVER])) {
								$email_addresses = $email_addresses[SERVER];
							}

							$email = new email();
							$email->default_style_set(NULL);
							$email->subject_set($email_title);
							$email->body_html_add($job_output_html); // Assume HTML
							$email->send($email_addresses);

						} else if (REQUEST_MODE == 'cli' || config::get('debug.level') > 0) {

							echo $job_output_html;

						}
					}

				//--------------------------------------------------
				// Return

					return $job_output_html;

			}

	}

//--------------------------------------------------
// Tables exist

	if (config::get('debug.level') > 0) {

		debug_require_db_table(DB_PREFIX . 'system_maintenance', '
				CREATE TABLE [TABLE] (
					id int(11) NOT NULL AUTO_INCREMENT,
					run_start datetime NOT NULL,
					run_end datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY run_end (run_end)
				);');

		debug_require_db_table(DB_PREFIX . 'system_maintenance_job', '
				CREATE TABLE [TABLE] (
					id int(11) NOT NULL AUTO_INCREMENT,
					job varchar(20) NOT NULL,
					run_id int(11) NOT NULL,
					output text NOT NULL,
					created datetime NOT NULL,
					PRIMARY KEY (id),
					KEY job (job, created)
				);');

	}

?>
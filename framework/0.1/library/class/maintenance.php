<?php

// TODO: Handle switching from BST to GMT

//--------------------------------------------------
// http://www.phpprime.com/doc/setup/jobs/
//--------------------------------------------------

	class maintenance_base extends check {

		//--------------------------------------------------
		// Variables

			private $job_dir = NULL;
			private $job_paths = [];
			private $jobs_run = [];
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

					require(FRAMEWORK_ROOT . '/includes/08.setup.php'); // Not "require_once", as was already required by "/cli/run.php"

				//--------------------------------------------------
				// Cleanup

					$archive_date = new timestamp('-2 months'); // Some jobs only run once a month, so needs some overlap

					$sql = 'DELETE FROM
								' . DB_PREFIX . 'system_maintenance
							WHERE
								run_end != "0000-00-00 00:00:00" AND
								run_end < ?';

					$parameters = [];
					$parameters[] = $archive_date;

					$db->query($sql, $parameters);

					$sql = 'DELETE FROM
								' . DB_PREFIX . 'system_maintenance_job
							WHERE
								created < ?';

					$parameters = [];
					$parameters[] = $archive_date;

					$db->query($sql, $parameters);

				//--------------------------------------------------
				// Clear old (but still open) run records

					$sql = 'SELECT
								id,
								run_start
							FROM
								' . DB_PREFIX . 'system_maintenance
							WHERE
								run_end = "0000-00-00 00:00:00" AND
								run_start < ?';

					$parameters = [];
					$parameters[] = timestamp('-2 hours');

					foreach ($db->fetch_all($sql, $parameters) as $row) {

						$sql = 'UPDATE
									' . DB_PREFIX . 'system_maintenance
								SET
									run_end = ?
								WHERE
									id = ? AND
									run_end = "0000-00-00 00:00:00"';

						$parameters = [];
						$parameters[] = $now;
						$parameters[] = intval($row['id']);

						$db->query($sql, $parameters);

						report_add('Cleared old maintenance run record (' . $row['id'] . ' / ' . $row['run_start'] . ')');

					}

				//--------------------------------------------------
				// Create proper lock

					$lock_type = 'maintenance';

					if ($this->result_url) {

						$loading = new loading([
								'time_out'  => $this->time_out,
								'lock_type' => $lock_type,
							]);

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

					$sql = 'SELECT
								1
							FROM
								' . DB_PREFIX . 'system_maintenance
							WHERE
								run_end = "0000-00-00 00:00:00" OR
								run_end = ?';

					$parameters = [];
					$parameters[] = $now;

					if ($db->num_rows($sql, $parameters) > 0) {
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

					$db->insert(DB_PREFIX . 'system_maintenance', [
							'id'        => '',
							'run_start' => $now,
							'run_end'   => '0000-00-00 00:00:00',
						]);

					$this->run_id = $db->insert_id();

					$sql = 'SELECT 1 FROM ' . DB_PREFIX . 'system_maintenance WHERE run_end = "0000-00-00 00:00:00"';
					if ($db->num_rows($sql) != 1) {
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

					$end_timestamp = new timestamp();

					$sql = 'UPDATE
								' . DB_PREFIX . 'system_maintenance
							SET
								run_end = ?
							WHERE
								run_end = "0000-00-00 00:00:00"
							LIMIT
								1';

					$parameters = [];
					$parameters[] = $end_timestamp;

					$db->query($sql, $parameters);

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
			protected $error_type = NULL;
			protected $error_message = NULL;

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

						$sql = 'SELECT
									created
								FROM
									' . DB_PREFIX . 'system_maintenance_job
								WHERE
									job = ?
								ORDER BY
									created DESC
								LIMIT
									1';

						$parameters = [];
						$parameters[] = $this->job_name;

						if ($row = $db->fetch_row($sql, $parameters)) {
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
				return [];
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

					report_add($error, 'error', false); // Don't send the email, will be done later

					$this->error_type = 'Fatal';
					$this->error_message = $error;

				}
				return false;
			}

			protected function error_harmless($error = NULL) {
				if ($error !== NULL) {

					report_add($error, 'error', false); // Don't send the email, will be done later

					$this->error_type = 'Harmless';
					$this->error_message = $error;

				}
				return false;
			}

			public function error_get() {
				if ($this->error_type) {
					return [$this->error_type, $this->error_message];
				} else {
					return NULL;
				}
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

					$error = false;

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

								$job_output_html .= trim(strval($job->run()));

							}

							$job_output_html = ob_get_clean() . $job_output_html;

						//--------------------------------------------------
						// Email

							$email_title = $job->email_title_get();
							$email_addresses = $job->email_addresses_get();

						//--------------------------------------------------
						// Error

							$error = $job->error_get();
							if ($error) {

								$email_title .= ' - ' . $error[0];

								$error_type = $error[0] . ' Error [' . $this->job_name . ']:';

								$error_text = $error_type . "\n" . ' ' . $error[1] . "\n\n";
								$error_html = '<p class="error"><strong>' . html($error_type) . '</strong><br />' . nl2br(html($error[1])) . '</p>' . "\n\n";

								if (REQUEST_MODE == 'cli' && config::get('debug.level') > 0) { // Only run from the CLI, in Debug mode.
									if (config::get('output.mime') == 'text/plain') {
										echo $error_text;
									} else {
										echo $error_html;
									}
								}

								$job_output_html = rtrim($error_html . $job_output_html);

							}

						//--------------------------------------------------
						// Halt of maintenance run

							if ($job->halt_maintenance_run() == true) {
								$this->halt_maintenance_run = true;
							}

					} else {

						//--------------------------------------------------
						// Email

							$email_title = $this->email_title_get();
							$email_addresses = [];

					}

				//--------------------------------------------------
				// Send

					if ($this->run_id !== NULL && $job_output_html != '') {

						if (isset($email_addresses[SERVER])) {
							$email_addresses = $email_addresses[SERVER];
						}

						$email = new email();
						$email->default_style_set(NULL);
						$email->subject_set($email_title);
						$email->body_html_add($job_output_html); // Assume HTML
						$email->send($email_addresses);

					}

				//--------------------------------------------------
				// Log

					if ($this->run_id !== NULL && !$error) { // We only record successes, report_add() handles the errors.

						$db = db_get();

						$now = new timestamp();

						$db->insert(DB_PREFIX . 'system_maintenance_job', [
								'job'     => $this->job_name,
								'run_id'  => $this->run_id,
								'output'  => $job_output_html,
								'created' => $now,
							]);

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
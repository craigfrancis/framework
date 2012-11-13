<?php

/***************************************************

	//--------------------------------------------------
	// Site config



	//--------------------------------------------------
	// Example setup



***************************************************/

// TODO: Handle switching from BST to GMT

//--------------------------------------------------
// Maintenance class

	class maintenance_base extends base {

		//--------------------------------------------------
		// Variables

			private $job_dir;
			private $job_paths;
			private $jobs_run;
			private $run_id = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {

				//--------------------------------------------------
				// Jobs

					$this->job_dir = APP_ROOT . '/job/';
					$this->job_paths = array();
					$this->jobs_run = array();

					if ($handle = opendir($this->job_dir)) {
						while (false !== ($file = readdir($handle))) {
							if (is_file($this->job_dir . $file) && preg_match('/^([0-9]+\.)?([a-zA-Z0-9_\-]+)\.php$/', $file, $matches)) {

								$this->job_paths[$matches[2]] = $this->job_dir . $file;

							}
						}
					}

					asort($this->job_paths);

			}

		//--------------------------------------------------
		// Run

			public function run() {

				//--------------------------------------------------
				// Make sure we have plenty of memory

					ini_set('memory_limit', '1024M');

				//--------------------------------------------------
				// Include setup

					$setup = new setup();
					$setup->run();

					unset($setup);

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

					if ($row = $db->fetch_row()) {

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

					$db->insert(DB_PREFIX . 'maintenance', array(
							'id'        => '',
							'run_start' => date('Y-m-d H:i:s'),
							'run_end'   => '0000-00-00 00:00:00',
						));

					$this->run_id = $db->insert_id();

					$db->query('SELECT 1 FROM ' . DB_PREFIX . 'maintenance WHERE run_end = "0000-00-00 00:00:00"');
					if ($db->num_rows() != 1) {
						exit_with_error('Maintenance script is already running (B).');
					}

				//--------------------------------------------------
				// Cleanup

					$sql = 'SELECT
								id
							FROM
								' . DB_PREFIX . 'maintenance
							WHERE
								run_end != "0000-00-00 00:00:00" AND
								run_end < "' . $db->escape(date('Y-m-d H:i:s', strtotime('-1 month'))) . '"';

					foreach ($db->fetch_all($sql) as $row) {

						$db->query('DELETE FROM
										' . DB_PREFIX . 'maintenance_job
									WHERE
										run_id = "' . $db->escape($row['id']) . '"');

						$db->query('DELETE FROM
										' . DB_PREFIX . 'maintenance
									WHERE
										id = "' . $db->escape($row['id']) . '"');

					}

				//--------------------------------------------------
				// Jobs

					foreach ($this->job_paths as $job_name => $job_path) {
						if (!in_array($job_name, $this->jobs_run)) {

							//--------------------------------------------------
							// Action object

								$job = new job($job_name, $this, $this->run_id);

								$success = $job->run_wrapper();

								if ($success !== false) {
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
									' . DB_PREFIX . 'maintenance
								SET
									run_end = "' . $db->escape(date('Y-m-d H:i:s')) . '"
								WHERE
									run_end = "0000-00-00 00:00:00"
								LIMIT
									1');

				//--------------------------------------------------
				// Return list of ran jobs

					return $this->jobs_run;

			}

			public function require_job_run($job_name) {

				if (!in_array($job_name, $this->jobs_run)) {

					$job = new job($job_name, $this, $this->run_id);

					$success = $job->run_wrapper(true);

					if ($success) {
						$this->jobs_run[] = $job_name;
					}

					return $success;

				}

				return true;

			}

			public function execute($job_name) {

				$job = $this->job_get($job_name);

				$success = $job->run_wrapper(true);

				if ($success) {
					$this->jobs_run[] = $job_name;
				}

				return $success;

			}

		//--------------------------------------------------
		// Return job

			public function job_get($job_name) {
				return new job($job_name, $this);
			}

			public function job_path_get($job_name) {
				if (isset($this->job_paths[$job_name])) {
					return $this->job_paths[$job_name];
				} else {
					return false;
				}
			}

		//--------------------------------------------------
		// State support

			public function state() {

				//--------------------------------------------------
				// Create simple index of jobs

					$this->title_set('Maintenance State');

					$html = '
						<h2>State</h2>
						<p>TODO</p>';

					$view = new view();
					$view->render_html($html);

					// An admin interface which can show the jobs being run, can cancel them, or stack them up.

			}

		//--------------------------------------------------
		// Test support

			public function test() {

				//--------------------------------------------------
				// Execute job

					$job_name = request('execute');

					if (isset($this->job_paths[$job_name])) {

						$output = $this->execute($job_name);

						if ($output !== false && $output === '') {
							$output = '<p>No output.</p>';
						}

						exit($output);

					}

				//--------------------------------------------------
				// Create simple index of jobs

					$this->title_set('Maintenance Jobs');

					$html = '
						<h2>Jobs</h2>
						<ul>';

					foreach ($this->job_paths as $job_name => $job_path) {
						$html .= '
								<li><a href="' . html(url('./', array('execute' => $job_name))) . '">' . html(ref_to_human($job_name)) . '</a></li>';
					}

					$html .= '
						</ul>';

					$view = new view();
					$view->render_html($html);

			}

	}

//--------------------------------------------------
// Action class

	class job_base extends base {

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

			public function __construct($job_name, $maintenance, $run_id = 0, $mode = 'wrapper') {

				//--------------------------------------------------
				// Details

					$this->job_name = $job_name;
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
										' . DB_PREFIX . 'maintenance_job
									WHERE
										job = "' . $db->escape($this->job_name) . '"
									ORDER BY
										created DESC
									LIMIT
										1');

						if ($row = $db->fetch_row()) {
							$this->last_run = strtotime($row['created']);
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

					if (config::get('output.mime') == 'text/plain') {
						echo ucfirst($this->job_name) . ' - Fatal Error:' . "\n";
						echo ' ' . $error . "\n\n";
					}

				}
				return false;
			}

			protected function error_harmless($error = NULL) {
				if ($error !== NULL) {

					report_add($error, 'error');

					if (config::get('output.mime') == 'text/plain') {
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

					if (is_file($job_path)) {

						ob_start();

						if (!class_exists($job_object)) {
							require_once($job_path);
						}

						$job_output_html = ob_get_clean();

					} else {

						return $this->error_fatal('Could not load job "' . $this->job_name . '"');

					}

				//--------------------------------------------------
				// Email title

					$email_title = ref_to_human($this->job_name) . ' @ ' . date('Y-m-d H:i:s');

					$email_addresses = array();

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
						// Prep

							$prep_result = $job->prep();

						//--------------------------------------------------
						// Run

							if (is_string($prep_result) && strlen($prep_result) > 0) {

								$job_output_html = $prep_result;

							} else if ($prep_result !== false && $job->halt_maintenance_run() === false) {

								$job_output_html = trim($job->run());

							} else {

								$job_output_html = '';

							}

						//--------------------------------------------------
						// Email

							if (method_exists($job, 'email_title_get')) {
								$email_title = $job->email_title_get();
							}

							$email_addresses = $job->email_addresses_get();

						//--------------------------------------------------
						// Halt of maintenance run

							if ($job->halt_maintenance_run() == true) {
								$this->halt_maintenance_run = true;
							}

					}

				//--------------------------------------------------
				// Log

					$db = $this->db_get();

					if ($this->run_id > 0 && $this->halt_maintenance_run === false) {

						$db->insert(DB_PREFIX . 'maintenance_job', array(
								'job'     => $this->job_name,
								'run_id'  => $this->run_id,
								'output'  => $job_output_html,
								'created' => date('Y-m-d H:i:s'),
							));

					}

				//--------------------------------------------------
				// Send

					if ($this->run_id > 0 && $this->halt_maintenance_run === false && $job_output_html != '') {

						if (isset($email_addresses[SERVER])) {
							$email_addresses = $email_addresses[SERVER];
						}

						$email = new email();
						$email->subject_set($email_title);
						$email->body_html_add($job_output_html);
						$email->send($email_addresses);

					}

				//--------------------------------------------------
				// Return

					return $job_output_html;

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

		debug_require_db_table('maintenance_job', '
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
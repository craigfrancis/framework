<?php

//--------------------------------------------------
// Is enabled

	if (config::get('maintenance.active') !== true) {
		exit_with_error('Maintenance tasks disabled.');
	}

//--------------------------------------------------
// Maintenance class

	class maintenance {

		private $tasks_dir;
		private $tasks_available;
		private $tasks_already_run;
		private $run_id = NULL;

		public function __construct() {

			//--------------------------------------------------
			// Actions

				$this->tasks_dir = ROOT_APP . '/task/';
				$this->tasks_available = array();
				$this->tasks_already_run = array();

				if ($handle = opendir($this->tasks_dir)) {
					while (false !== ($file = readdir($handle))) {
						if (is_file($this->tasks_dir . $file) && preg_match('/^([0-9]+\.)?([a-zA-Z_]+)\.php$/', $file, $matches)) {

							$this->tasks_available[$file] = $matches[2];

						}
					}
				}

				ksort($this->tasks_available);

		}

		public function run() {

			//--------------------------------------------------
			// Hide debug output

				config::set('debug.show', false);

			//--------------------------------------------------
			// Make sure we have plenty of memory

				ini_set('memory_limit', '1024M');

			//--------------------------------------------------
			// Clear old locks

				$db = new db();

				$db->query('SELECT
								id,
								run_start
							FROM
								' . DB_T_PREFIX . 'maintenance
							WHERE
								run_end = "0000-00-00 00:00:00" AND
								run_start < "' . $db->escape(date('Y-m-d H:i:s', strtotime('-2 hours'))) . '"');

				if ($row = $db->fetch_assoc()) {

					$db->query('DELETE FROM
									' . DB_T_PREFIX . 'maintenance
								WHERE
									id = "' . $db->escape($row['id']) . '" AND
									run_end = "0000-00-00 00:00:00"');

					report_add('Deleted old maintenance lock (' . $row['id'] . ' / ' . $row['run_start'] . ')');

				}

			//--------------------------------------------------
			// Create maintenance record (lock).

				$db->query('SELECT 1 FROM ' . DB_T_PREFIX . 'maintenance WHERE run_end = "0000-00-00 00:00:00"');
				if ($db->num_rows() > 0) {
					exit_with_error('Maintenance script is already running (A).');
				}

				$db->query('INSERT INTO ' . DB_T_PREFIX . 'maintenance (
								run_start,
								run_end
							) VALUES (
								"' . $db->escape(date('Y-m-d H:i:s')) . '",
								"0000-00-00 00:00:00"
							)');

				$this->run_id = $db->insert_id();

				$db->query('SELECT 1 FROM ' . DB_T_PREFIX . 'maintenance WHERE run_end = "0000-00-00 00:00:00"');
				if ($db->num_rows() != 1) {
					exit_with_error('Maintenance script is already running (B).');
				}

			//--------------------------------------------------
			// Cleanup

				$rstM = $db->query('SELECT
										id
									FROM
										' . DB_T_PREFIX . 'maintenance
									WHERE
										run_end != "0000-00-00 00:00:00" AND
										run_end < "' . $db->escape(date('Y-m-d H:i:s', strtotime('-1 month'))) . '"');

				while ($row = $db->fetch_assoc($rstM)) {

					$db->query('DELETE FROM
									' . DB_T_PREFIX . 'maintenance_task
								WHERE
									run_id = "' . $db->escape($row['id']) . '"');

					$db->query('DELETE FROM
									' . DB_T_PREFIX . 'maintenance
								WHERE
									id = "' . $db->escape($row['id']) . '"');

				}

			//--------------------------------------------------
			// Actions

				$ran_tasks = array();

				foreach ($this->tasks_available as $task_file => $task_name) {
					if (!in_array($task_name, $this->tasks_already_run)) {

						//--------------------------------------------------
						// Action object

							$task_object = $task_name . '_task';

							require_once($this->tasks_dir . $task_file);

							$task = new $task_object($this, $this->run_id);

						//--------------------------------------------------
						// Run task

							if ($task->should_run()) {

								$task->run_wrapper();

								$ran_tasks[] = $task_name;

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
								' . DB_T_PREFIX . 'maintenance
							SET
								run_end = "' . $db->escape(date('Y-m-d H:i:s')) . '"
							WHERE
								run_end = "0000-00-00 00:00:00"
							LIMIT
								1');

			//--------------------------------------------------
			// Return list of ran tasks

				return $ran_tasks;

		}

		public function task_get($task_file) {

			//--------------------------------------------------
			// Action object

				$task_path = $this->tasks_dir . $task_file;
				$task_name = $this->tasks_available[$task_file];
				$task_object = $task_name . '_task';

				require_once($task_path);

				return new $task_object($this);

		}

		public function execute($task_file) {

			//--------------------------------------------------
			// Execute

				$task = $this->task_get($task_file);

				return $task->run_wrapper('<p>No output.</p>');

		}

		public function test() {

			//--------------------------------------------------
			// Only on stage

				if (SERVER != 'stage') {
					exit('Disabled');
				}

			//--------------------------------------------------
			// Execute task

				$task_file = data('execute');

				if (isset($this->tasks_available[$task_file])) {
					exit($this->execute($task_file));
				}

			//--------------------------------------------------
			// Create simple index of tasks

				config::set('output.title', 'Maintenance tasks');

				$html = '
					<h2>Actions</h2>
					<ul>';

				foreach ($this->tasks_available as $cFile => $cName) {
					$html .= '
							<li><a href="./?execute=' . html(urlencode($cFile)) . '">' . html(ucfirst(str_replace('_', ' ', $cName))) . '</a></li>';
				}

				$html .= '
					</ul>';

				$view = new view();
				$view->render_html($html);

				$layout = new layout();
				$layout->render();

		}

		public function require_task_run($task_name) {

			if (!in_array($task_name, $this->tasks_already_run)) {

				//--------------------------------------------------
				// Load class

					if (!class_exists($task_name)) {

						$task_path = array_search($task_name, $this->tasks_available);
						if ($task_path !== false) {
							require_once($this->tasks_dir . $task_path);
						} else {
							return $this->fatal_error('Could not load task "' . $task_name . '"');
						}

					}

				//--------------------------------------------------
				// Run task

					$task_object = $task_name . '_task';

					$task = new $task_object($this, $this->run_id);
					$task->run_wrapper();

			}

			return true;

		}

	}

//--------------------------------------------------
// Action class

	class task {

		protected $task_name = NULL;
		protected $last_run = NULL;
		protected $halt_maintenance_run = false;
		protected $maintenance = NULL;
		protected $run_id = NULL;

		public function __construct($maintenance, $run_id = NULL) {

			//--------------------------------------------------
			// Details

				$this->maintenance = $maintenance;
				$this->run_id = $run_id;

			//--------------------------------------------------
			// Name

				$this->task_name = get_class($this);
				$this->task_name = str_replace('_task', '', $this->task_name);

			//--------------------------------------------------
			// Last run

				$db = new db();

				if ($this->run_id > 0) {

					$db->query('SELECT
									created
								FROM
									' . DB_T_PREFIX . 'maintenance_task
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

		public function emails_get() {
			return array();
		}

		protected function init() {
		}

		public function halt_maintenance_run() {
			return $this->halt_maintenance_run;
		}

		protected function fatal_error($error = NULL) {
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

		protected function harmless_error($error = NULL) {
			if ($error !== NULL) {

				report_add($error, 'error');

				if (config::get('output.mime') == 'text/plain') {
					echo ucfirst($this->task_name) . ' - Harmless Error:' . "\n";
					echo ' ' . $error . "\n\n";
				}

			}
			return false;
		}

		public function should_run() {
			return true; // This task should always run
		}

		protected function require_task_run($task_name) {
			return $this->maintenance->require_task_run($task_name);
		}

		public function prep() {
			return true; // Success
		}

		public function run() {
			return '';
		}

		final public function run_wrapper($default_output_html = '') {

			//--------------------------------------------------
			// Title

				if (method_exists($this, 'get_email_title')) {
					$task_title = $this->get_email_title();
				} else {
					$task_title = config::get('output.site_name') . ': ' . ucfirst(str_replace('_', ' ', $this->task_name)) . ' @ ' . date('Y-m-d H:i:s');
				}

			//--------------------------------------------------
			// Prep

				$prep_result = $this->prep();

			//--------------------------------------------------
			// Run

				if (is_string($prep_result) && strlen($prep_result) > 0) {

					$task_output_html = $prep_result;

				} else if ($prep_result !== false && $this->halt_maintenance_run === false) {

					$task_output_html = trim($this->run());

				} else {

					$task_output_html = '';

				}

			//--------------------------------------------------
			// Default

				if ($task_output_html == '') {
					$task_output_html = $default_output_html;
				}

			//--------------------------------------------------
			// Log

				$db = new db();

				if ($this->run_id > 0 && $this->halt_maintenance_run === false) {

					$db->query('INSERT INTO ' . DB_T_PREFIX . 'maintenance_task (
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

					$emails = $this->emails_get();

					if (isset($emails[SERVER])) {
						$emails = $emails[SERVER];
					}

					$email = new email();
					$email->subject_set($task_title);
					$email->content_html_add($task_output_html);
					$email->send($emails);

				}

			//--------------------------------------------------
			// Has been run

				$this->tasks_already_run[] = $this->task_name;

			//--------------------------------------------------
			// Return

				return $task_output_html;

		}

	}

//--------------------------------------------------
// Tables exist

	if (SERVER == 'stage') {

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
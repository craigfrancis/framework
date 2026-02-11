<?php

//--------------------------------------------------
//
// TODO: Not complete yet
//
// 	$command = new command();
// 	$command->stdin_set('Stdin Text');
// 	// $command->stdout_file_set('/tmp/test-stdout', 'w');
// 	// $command->stderr_file_set('/tmp/test-stderr', 'w');
//
// 	$exit_code = $command->exec('/path/to/command.sh --arg=?', [
// 			'arg1',
// 			'extra1',
// 			'extra2',
// 			'extra3',
// 		]);
//
// 	debug($command->stderr_get());
// 	debug($command->stdout_get());
//
// Check pending process on a slow command:
//
// 	$success = $command->exec_start('/path/to/command.sh', []);
// 	if ($success) {
// 		usleep(200000);
// 		debug($command->exec_pending_get());
// 		usleep(200000);
// 		debug($command->exec_pending_get());
// 		usleep(200000);
// 		debug($command->exec_end());
// 	}
//
//--------------------------------------------------
//
// Review:
//   https://github.com/craigfrancis/php-is-literal-rfc#solution-cli-injection
//   http://symfony.com/doc/current/components/process.html
//   https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Process/Process.php
//
// Intention to remove:
//
//   command_run()
//   passthru()
//   shell_exec()
//   exec()
//   system()
//   popen()
//   proc_open() ... proc_get_status(), proc_terminate()
//   pcntl_exec()
//
//   escapeshellcmd()
//   escapeshellarg()
//
//--------------------------------------------------

	class command_base extends check {

		protected $stdin_value = NULL;
		protected $stdout_file = NULL;
		protected $stdout_value = NULL;
		protected $stderr_file = NULL;
		protected $stderr_value = NULL;
		protected $env_cwd = NULL;
		protected $env_variables = [];
		protected $paths = NULL;
		protected $time_start = NULL;
		protected $time_total = NULL;
		protected $pipes = [];
		protected $process = NULL;
		protected $blocking = NULL;
		protected $exit_status = NULL;
		protected $exit_code = NULL;

		public function __construct() {
		}

		public function stdin_set($stdin) {
			$this->stdin_value = $stdin;
		}

		public function stdin_pipe_get() {
			return $this->pipes[0];
		}

		public function stdout_file_set($file, $mode) { // 'w' for writing a new file, 'a' for appending, etc.
			$this->stdout_file = [$file, $mode];
		}

		public function stdout_pipe_get() {
			return $this->pipes[1];
		}

		public function stdout_get() {
			if ($this->stdout_value === NULL) {
				throw new error_exception('Cannot return stdout via this setup'); // Via exec_start/exec_pending_get, or using stdout_file_set
			}
			return $this->stdout_value;
		}

		public function stderr_file_set($file, $mode) {
			$this->stderr_file = [$file, $mode];
		}

		public function stderr_pipe_get() {
			return $this->pipes[2];
		}

		public function stderr_get() {
			if ($this->stderr_value === NULL) {
				throw new error_exception('Cannot return stderr via this setup'); // Via exec_start/exec_pending_get, or using stderr_file_set
			}
			return $this->stderr_value;
		}

		public function exit_status_get() {
			return $this->exit_status;
		}

		public function exit_code_get() {
			return $this->exit_code;
		}

		public function exec($command, $arguments = []) {
			if ($this->exec_start($command, $arguments)) {
				$this->exec_end();
			}
			return $this->exit_code;
		}

		static public function exec_compose($command, $parameters = []) {

			if (function_exists('is_literal') && is_literal($command) !== true) {
				exit_with_error('The command must be a literal', $command);
			}

			$offset = 0;
			$k = 0;

			while (($pos = strpos($command, '?', $offset)) !== false) {
				if (!array_key_exists($k, $parameters)) {
					throw new error_exception('Missing parameter "' . ($k + 1) . '"', $command . "\n\n" . debug_dump($parameters));
				}
				if (is_array($parameters[$k])) {
					$parameter = implode(' ', array_map('escapeshellarg', $parameters[$k]));
				} else {
					$parameter = escapeshellarg($parameters[$k]);
				}
				$command = substr($command, 0, $pos) . $parameter . substr($command, ($pos + 1));
				$offset = ($pos + strlen($parameter));
				$k++;
			}

			for ($l = count($parameters); $k < $l; $k++) {
				$command .= ' ' . escapeshellarg($parameters[$k]);
			}

			return $command;

		}

		public function exec_start($command, $parameters = []) {

			//--------------------------------------------------
			// Is Literal

				if (function_exists('is_literal') && is_literal($command) !== true) {
					exit_with_error('The command must be a literal', $command);
				}

			//--------------------------------------------------
			// Executable

				$run_direct = true;

				if (version_compare(PHP_VERSION, '7.4.0', '<')) {
					$run_direct = false;
				}

				$executable = $command;
				if (($pos = strpos($executable, ' ')) !== false) {
					$executable = substr($executable, 0, $pos);
					$run_direct = false; // Needs a shell
				}

				if (substr($executable, 0, 1) !== '/') {
					$paths = ($this->paths ?? explode(':', ($_SERVER['PATH'] ?? '')));
					foreach ($paths as $path) {
						$test = $path . '/' . $executable;
						if (is_file($test)) {
							$executable = $test;
						}
					}
				} else {
					$paths = 'N/A';
				}

				if (!is_executable($executable)) {
					if (is_file($executable)) {
						throw new error_exception('The command is not executable.', $executable . "\n\n" . debug_dump($paths) . "\n\n" . $command);
					} else {
						throw new error_exception('The command does not exist.', $executable . "\n\n" . debug_dump($paths) . "\n\n" . $command);
					}
				}

			//--------------------------------------------------
			// Command

				if ($run_direct) {
					$command = array_merge([$command], $parameters);
				} else {
					$command = command::exec_compose($command, $parameters);
				}

			//--------------------------------------------------
			// Descriptors

				$descriptors = [];
				$descriptors[0] = ['pipe', 'r'];

				if ($this->stdout_file) {
					$descriptors[1] = ['file', $this->stdout_file[0], $this->stdout_file[1]];
				} else {
					$descriptors[1] = ['pipe', 'w'];
				}

				if ($this->stderr_file) {
					$descriptors[2] = ['file', $this->stderr_file[0], $this->stderr_file[1]];
				} else {
					$descriptors[2] = ['pipe', 'w'];
				}

			//--------------------------------------------------
			// Reset

				$this->pipes = [];
				$this->exit_status = NULL;
				$this->exit_code = NULL;

			//--------------------------------------------------
			// Run

				$this->time_start = hrtime(true);

				$this->blocking = true;

				$this->process = proc_open($command, $descriptors, $this->pipes, $this->env_cwd, $this->env_variables);

				$success = (is_resource($this->process) === true);

			//--------------------------------------------------
			// Send stdin

				if ($success && $this->stdin_value !== NULL) {
					fwrite($this->pipes[0], $this->stdin_value);
				}

			//--------------------------------------------------
			// Return

				return $success;

		}

		public function exec_pending_get() { // Not really been tested/used properly yet (will probably change)

			$output = [
					'stdout' => ['id' => 1],
					'stderr' => ['id' => 2],
				];

			if ($this->blocking) {
				$this->blocking = false;
				foreach ($output as $stream => $info) {
					stream_set_blocking($this->pipes[$info['id']], false);
				}
			}

			foreach ($output as $stream => $info) {
				$output[$stream]['meta'] = stream_get_meta_data($this->pipes[$info['id']]);
				$output[$stream]['data'] = stream_get_contents($this->pipes[$info['id']]);
			}

			$output['status'] = proc_get_status($this->process);

			return $output;

		}

		public function exec_end() {

			//--------------------------------------------------
			// Close pipes

				if ($this->blocking) {
					if (!$this->stdout_file) $this->stdout_value = stream_get_contents($this->pipes[1]);
					if (!$this->stderr_file) $this->stderr_value = stream_get_contents($this->pipes[2]);
				}

				fclose($this->pipes[0]);
				fclose($this->pipes[1]);
				fclose($this->pipes[2]);

			//--------------------------------------------------
			// Exist

				$this->exit_status = proc_get_status($this->process);

				$this->exit_code = proc_close($this->process);

				$this->time_total = hrtime_diff($this->time_start);

				if (!$this->exit_status['running']) {
					$this->exit_code = $this->exit_status['exitcode']; // proc_close() will return -1 if... reading from a pipe (stream_get_contents), or using proc_get_status() at the wrong time.
				}

		}

	}

?>
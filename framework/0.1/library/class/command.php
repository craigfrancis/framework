<?php

//--------------------------------------------------
//
// TODO: Not complete yet
//
// 	$command = new command();
// 	$command->stdin_set('Stdin Text');
// 	$command->stdout_file_set('/tmp/test-stdout', 'w');
// 	$command->stderr_file_set('/tmp/test-stderr', 'w');
//
// 	$exit_code = $command->exec('/path/to/command.sh --arg=?', [
// 			'aaa',
// 			'bbb',
// 		]);
//
// 	debug($command->stdout_get());
//
// Review:
//   https://github.com/craigfrancis/php-is-literal-rfc#solution-cli-injection
//   http://symfony.com/doc/current/components/process.html
//   https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Process/Process.php
//
// Intention to remove:
//   command_run()
//   passthru()
//   shell_exec()
//   exec()
//   system()
//   popen()
//   proc_open() ... proc_get_status(), proc_terminate()
//   pcntl_exec()
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
		protected $time_start = NULL;
		protected $time_total = NULL;
		protected $pipes = [];
		protected $process = NULL;
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
			return $this->stdout_value;
		}

		public function stderr_file_set($file, $mode) {
			$this->stderr_file = [$file, $mode];
		}

		public function stderr_pipe_get() {
			return $this->pipes[2];
		}

		public function stderr_get() {
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

		public function exec_start($command, $parameters = []) {

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

				if (!is_executable($executable)) {
					if (is_file($executable)) {
						throw new error_exception('The command is not executable.', $executable . "\n\n" . $command);
					} else {
						throw new error_exception('The command does not exist.', $executable . "\n\n" . $command);
					}
				}

			//--------------------------------------------------
			// Command

				if ($run_direct) {

					$command = array_merge([$command], $parameters);

				} else {

					$offset = 0;
					$k = 0;

					while (($pos = strpos($command, '?', $offset)) !== false) {
						if (!array_key_exists($k, $parameters)) {
							throw new error_exception('Missing parameter "' . ($k + 1) . '"', $command . "\n\n" . debug_dump($parameters));
						}
						$parameter = escapeshellarg($parameters[$k]);
						$command = substr($command, 0, $pos) . $parameter . substr($command, ($pos + 1));
						$offset = ($pos + strlen($parameter));
						$k++;
					}

					for ($l = count($parameters); $k < $l; $k++) {
						$command .= ' ' . escapeshellarg($parameters[$k]);
					}

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

				$this->time_start = microtime(true);

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

		public function exec_end() {

			//--------------------------------------------------
			// Close pipes

				fclose($this->pipes[0]);

				if (!$this->stdout_file) {
					$this->stdout_value = stream_get_contents($this->pipes[1]);
					fclose($this->pipes[1]);
				}

				if (!$this->stderr_file) {
					$this->stderr_value = stream_get_contents($this->pipes[2]);
					fclose($this->pipes[2]);
				}

			//--------------------------------------------------
			// Exist

				$this->exit_status = proc_get_status($this->process);

				$this->exit_code = proc_close($this->process);

				$this->time_total = (microtime(true) - $this->time_start);

				if (!$this->exit_status['running']) {
					$this->exit_code = $this->exit_status['exitcode']; // proc_close() will return -1 if... reading from a pipe (stream_get_contents), or using proc_get_status() at the wrong time.
				}

		}

	}

?>
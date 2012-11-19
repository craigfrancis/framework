<?php

/***************************************************

	//--------------------------------------------------
	// Example setup



***************************************************/

	class lock_base extends check {

		//--------------------------------------------------
		// Variables

			private $lock_type;
			private $lock_ref;
			private $lock_data;
			private $lock_path;
			private $lock_file;

			private $time_out;

		//--------------------------------------------------
		// Setup

			public function __construct($type, $ref = NULL) {

				//--------------------------------------------------
				// Defaults

					$this->lock_type = $type;
					$this->lock_ref = $ref;
					$this->lock_data = array();
					$this->lock_path = NULL;
					$this->lock_file = NULL;

					$this->time_out = strtotime('+30 seconds');

				//--------------------------------------------------
				// Lock path

					$lock_folder = PRIVATE_ROOT . '/tmp/lock/';

					if (!is_dir($lock_folder)) {
						@mkdir($lock_folder, 0777);
						@chmod($lock_folder, 0777); // Probably created with web server user, but needs to be deleted with user account
					}

					if (!is_dir($lock_folder)) exit_with_error('Cannot create lock folder', $lock_folder);
					if (!is_writable($lock_folder)) exit_with_error('Cannot write to lock folder', $lock_folder);

					$this->lock_path = $lock_folder . safe_file_name($this->lock_type);

					if ($this->lock_ref) {
						$this->lock_path .= '.' . safe_file_name($this->lock_ref);
					}

			}

			public function time_out_set($time) {

				$this->time_out = $time;

				set_time_limit($this->time_out - time());

				if ($this->lock_file) {
					$this->lock_data['expires'] = $this->time_out;
					$this->_data_save();
				}

			}

			public function time_out_get() {
				return $this->time_out;
			}

		//--------------------------------------------------
		// Data

			public function data_get($field = NULL) {

				if ($this->lock_file) {

					$data = $this->lock_data['data'];

				} else if (is_file($this->lock_path)) {

					$data = json_decode(file_get_contents($this->lock_path), true); // as associative array

				} else {

					$data = array();

				}

				if ($field === NULL) {
					return $data;
				} else {
					return (isset($data[$field]) ? $data[$field] : NULL);
				}

			}

			public function data_set($field, $value = NULL) {

				if (!$this->lock_file) {
					exit_with_error('You do not have the lock, so cannot save data onto it.');
				}

				if (is_array($field)) {
					$this->lock_data['data'] = array_merge($this->lock_data['data'], $field);
				} else {
					$this->lock_data['data'][$field] = $value;
				}

				$this->_data_save();

			}

			private function _data_save() {

				rewind($this->lock_file);
				ftruncate($this->lock_file, 0);

				fwrite($this->lock_file, json_encode($this->lock_data));

			}

		//--------------------------------------------------
		// Process

			public function open() {

				//--------------------------------------------------
				// Lock already exists

					if (file_exists($this->lock_path)) {

						if ($this->lock_file) { // This object has the lock, and the file still exists

debug(fstat($this->lock_file));
debug(ftell($this->lock_file));

// TODO: fopen status


							return true;
						}

						if ($this->data_get('expires') > time()) {
							return false;
						}

						unlink($this->lock_path); // Has expired

					}

				//--------------------------------------------------
				// Lock file

					$fp = fopen($this->lock_path, 'x+b'); // Returns false if file already exists

					if ($fp && flock($fp, LOCK_EX)) {

						$this->lock_data = array(
								'expires' => $this->time_out,
								'data' => array(),
							);

						fwrite($fp, json_encode($this->lock_data));
						flock($fp, LOCK_UN);
						fclose($fp);

					} else {

						if (file_exists($this->lock_path)) {
							return false; // Race condition, where the other thread got the lock first
						}

						exit_with_error('Cannot create lock file', $this->lock_path);

					}

				//--------------------------------------------------
				// Success

					return true;

			}

			public function close() {

				unlink($this->lock_path);

				$this->lock_file = NULL;

			}

	}

?>
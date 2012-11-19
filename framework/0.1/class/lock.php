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
			private $lock_fp;

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
					$this->lock_fp = NULL;

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

				if ($this->lock_fp) {
					$this->data_set('expires', $this->time_out);
				}

			}

			public function time_out_get() {
				return $this->time_out;
			}

		//--------------------------------------------------
		// Data

			public function data_get($field = NULL) {

				if ($this->lock_fp) {

					$data = $this->lock_data; // Don't check lock file, it may have been lost (expired), but we still need the data.

				} else if (is_file($this->lock_path)) {

					$data = json_decode(file_get_contents($this->lock_path), true); // Read as an associative array

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

				if ($this->lock_fp) {

					$info = fstat($this->lock_fp);
					if ($info['nlink'] > 0 && flock($this->lock_fp, LOCK_EX)) {

						if (is_array($field)) {
							$this->lock_data = array_merge($this->lock_data, $field);
						} else {
							$this->lock_data[$field] = $value;
						}

						rewind($this->lock_fp); // truncate does not change the file pointer
						ftruncate($this->lock_fp, 0);
						fwrite($this->lock_fp, json_encode($this->lock_data));

						flock($this->lock_fp, LOCK_UN);

						return true;

					} else {

						$this->close(); // Has lost the lock

					}

				}

				exit_with_error('You do not have the lock, so cannot save data onto it.', 'Try checking $lock->open() before $lock->data_set()');

			}

		//--------------------------------------------------
		// Process

			public function open() {

				//--------------------------------------------------
				// If this object has the lock

					if ($this->lock_fp) {

						$info = fstat($this->lock_fp);
						if ($info['nlink'] > 0) {
							return true; // File still exists on filesystem (not unlinked by another process)
						} else {
							return false; // Lost the lock, don't try to re-create
						}

					}

				//--------------------------------------------------
				// Lock already exists

					if (file_exists($this->lock_path)) {

						if ($this->data_get('expires') > time()) {
							return false;
						}

						unlink($this->lock_path); // Has expired

					}

				//--------------------------------------------------
				// Lock file

					$this->lock_fp = fopen($this->lock_path, 'x+b'); // Returns false if file already exists

					if (!$this->lock_fp || !flock($this->lock_fp, LOCK_EX)) {
						if (file_exists($this->lock_path)) {
							return false; // Race condition, where the other thread got the lock first
						}
						exit_with_error('Cannot create lock file', $this->lock_path);
					}

					$this->lock_data = array(
							'expires' => $this->time_out,
						);

					fwrite($this->lock_fp, json_encode($this->lock_data));

					flock($this->lock_fp, LOCK_UN);

				//--------------------------------------------------
				// Success

					return true;

			}

			public function close() {

				if ($this->lock_fp) {

					$info = fstat($this->lock_fp);
					if ($info['nlink'] > 0 && flock($this->lock_fp, LOCK_EX)) {
						unlink($this->lock_path); // Don't delete if another process now has the lock
 						flock($this->lock_fp, LOCK_UN);
					}

					fclose($this->lock_fp);

					$this->lock_fp = NULL;

				}

			}

	}

?>
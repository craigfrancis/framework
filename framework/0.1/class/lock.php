<?php

/***************************************************

	//--------------------------------------------------
	// Example setup

		$lock = new lock('example');
		$lock->time_out_set(strtotime('+5 minutes'));

		if ($lock->open()) {

			$lock->data_set('name', 'Craig');

			$lock->data_set(array(
					'field_1' => 'AAA',
					'field_2' => 'BBB',
					'field_3' => 'CCC',
				));

			sleep(5);

			$lock->close();

		} else {

			$this->set('name', $lock->data_get('name'));

		}

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
					$this->lock_data = NULL;
					$this->lock_path = NULL;
					$this->lock_fp = NULL;

					$this->time_out = strtotime('+30 seconds');

				//--------------------------------------------------
				// Lock path

					$this->lock_path = tmp_folder('lock') . safe_file_name($this->lock_type);

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

				if (is_array($this->lock_data)) {

					$data = $this->lock_data; // Don't check lock file, it may have been closed or lost (expired), but we still need the data.

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

				if ($this->lock_fp && flock($this->lock_fp, LOCK_EX)) {

					$info = fstat($this->lock_fp);
					if ($info['nlink'] > 0) {

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

						flock($this->lock_fp, LOCK_UN);

						fclose($this->lock_fp);

						$this->lock_fp = NULL; // Has lost the lock, although the error below will follow this up.

					}

				}

				exit_with_error('You do not have the lock, so cannot save data onto it.', 'Try checking $lock->open() before $lock->data_set()');

			}

		//--------------------------------------------------
		// Process

			public function open() {

				//--------------------------------------------------
				// If this object has the a file pointer

					if ($this->lock_fp && flock($this->lock_fp, LOCK_EX)) {

						$info = fstat($this->lock_fp);

						flock($this->lock_fp, LOCK_UN);

						if ($info['nlink'] > 0) {
							return true; // File still exists on filesystem (not unlinked by another process)
						} else {
							return false; // Lost the lock, don't try to re-create
						}

					}

				//--------------------------------------------------
				// Lock already exists

					if (file_exists($this->lock_path)) {

						$fp = fopen($this->lock_path, 'r');

						if (flock($fp, LOCK_EX)) { // Waits for lock

							$expires = $this->data_get('expires');
							if ($expires !== NULL && $expires > time()) {
								flock($fp, LOCK_UN);
								return false; // Not expired yet
							}

							unlink($this->lock_path); // Has expired
							flock($fp, LOCK_UN);

						}

					}

				//--------------------------------------------------
				// Lock file

					$this->lock_fp = fopen($this->lock_path, 'x+b'); // Returns false if file already exists

					if ($this->lock_fp) {

						$valid = true;

						if (flock($this->lock_fp, LOCK_EX)) { // Waits for lock
							$info = fstat($this->lock_fp);
							if ($info['nlink'] == 0) {
								flock($this->lock_fp, LOCK_UN);
								$valid = false; // Another process just did an unlink() on this file
							}
						} else {
							$valid = false; // Cannot create lock
						}

						if (!$valid) {
							fclose($this->lock_fp);
							$this->lock_fp = NULL;
						}

					}

					if (!$this->lock_fp) {
						if (file_exists($this->lock_path)) {
							return false; // Race condition, where the other thread created the file first
						}
						exit_with_error('Cannot create lock file', $this->lock_path);
					}

					$this->lock_data = array('expires' => $this->time_out); // Resets data, could be re-opening a new lock

					fwrite($this->lock_fp, json_encode($this->lock_data));

					flock($this->lock_fp, LOCK_UN); // Must release lock so other processes can open (e.g. reading for expiry)

				//--------------------------------------------------
				// Success

					return true;

			}

			public function close() {

				if ($this->lock_fp) {

					if (flock($this->lock_fp, LOCK_EX)) {
						$info = fstat($this->lock_fp);
						if ($info['nlink'] > 0) {
							unlink($this->lock_path); // Don't delete if another process now has the lock
						}
						flock($this->lock_fp, LOCK_UN);
					}

					fclose($this->lock_fp);

					$this->lock_fp = NULL;

				}

			}

	}

?>
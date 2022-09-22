<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/lock/
//--------------------------------------------------

	class lock_base extends check {

		//--------------------------------------------------
		// Variables

			private $lock_type = NULL;
			private $lock_ref = NULL;
			private $lock_data = NULL;
			private $lock_path = NULL;
			private $lock_fp = NULL;

			private $time_out = 30;

		//--------------------------------------------------
		// Setup

			public function __construct($type, $ref = NULL) {

				//--------------------------------------------------
				// Defaults

					$this->lock_type = $type;
					$this->lock_ref = $ref;

				//--------------------------------------------------
				// Lock path

					$this->lock_path = tmp_folder('lock') . '/' . safe_file_name($this->lock_type);

					if ($this->lock_ref) {
						$this->lock_path .= '.' . safe_file_name($this->lock_ref);
					}

			}

			public function time_out_set($time) {

				$this->time_out = $time;

				if ($this->lock_fp) {

					set_time_limit($this->time_out);

					$this->data_set('expires', ($this->time_out + time()));

				}

			}

		//--------------------------------------------------
		// Data

			public function data_get($field = NULL, $source = NULL) {

				if (($source === NULL || $source == 'active') && (is_array($this->lock_data))) {

					$data = $this->lock_data; // Don't check lock file, it may have been closed or lost (expired), but we still need the data.

				} else if (($source === NULL || $source == 'file') && (is_file($this->lock_path))) {

					$data = json_decode(file_get_contents($this->lock_path), true); // Read as an associative array

				} else {

					$data = [];

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
					if ($info['nlink'] > 0) { // Number of hard links to the file

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

			public function debug_info_get() {

				$return = [
						'type'        => $this->lock_type,
						'ref'         => $this->lock_ref,
						'path'        => $this->lock_path,
						'exists'      => file_exists($this->lock_path),
						'data_active' => $this->data_get(NULL, 'active'),
						'data_file'   => $this->data_get(NULL, 'file'),
						'time_out'    => $this->time_out,
						'fstat'       => NULL,
					];

				if ($this->lock_fp) {
					$info = fstat($this->lock_fp);
					$return['fstat'] = [
							'uid' => $info['uid'],
							'gid' => $info['gid'],
							'size' => $info['size'],
							'mode' => $info['mode'],
							'nlink' => $info['nlink'],
							'mtime' => $info['mtime'],
							'ctime' => $info['ctime'],
						];
				}

				return $return;

			}

			public function _debug_dump() {
				return $this->debug_info_get();
			}

		//--------------------------------------------------
		// Process

			public function locked() {

				//--------------------------------------------------
				// Check to see if the lock exists

					$expires = $this->data_get('expires', 'file');

					return ($expires !== NULL && $expires > time());

			}

			public function check() {

				//--------------------------------------------------
				// If we have a file pointer

					if ($this->lock_fp && flock($this->lock_fp, LOCK_EX)) {

						$info = fstat($this->lock_fp);

						flock($this->lock_fp, LOCK_UN);

						if ($info['nlink'] > 0) { // Number of hard links to the file
							return true; // File still exists on filesystem (not unlinked by another process)
						} else {
							return false; // Lost the lock, someone else has unlinked it
						}

					}

				//--------------------------------------------------
				// Nope

					return false;

			}

			public function open() {

				//--------------------------------------------------
				// If this object still has the lock

					if ($this->check()) {
						return true;
					}

				//--------------------------------------------------
				// Lock already exists

					if (is_file($this->lock_path)) {

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
							if ($info['nlink'] == 0) { // Number of hard links to the file
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
						if (is_file($this->lock_path)) {
							return false; // Race condition, where the other thread created the file first
						}
						exit_with_error('Cannot create lock file', $this->lock_path);
					}

					$this->lock_data = array('expires' => ($this->time_out + time())); // Resets data, could be re-opening a new lock

					fwrite($this->lock_fp, json_encode($this->lock_data));

					flock($this->lock_fp, LOCK_UN); // Must release lock so other processes can open (e.g. reading for expiry)

				//--------------------------------------------------
				// Update time limit

					set_time_limit($this->time_out);

				//--------------------------------------------------
				// Success

					return true;

			}

			public function close() {

				if ($this->lock_fp) {

					if (flock($this->lock_fp, LOCK_EX)) {
						$info = fstat($this->lock_fp);
						if ($info['nlink'] > 0) { // Number of hard links to the file
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
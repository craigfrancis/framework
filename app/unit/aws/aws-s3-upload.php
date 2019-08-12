<?php

	class aws_s3_upload_unit extends unit {

		protected $config = array(
			);

		protected function authenticate($config) {
			return true;
		}

		protected function setup($config) {

			//--------------------------------------------------
			// Testing

				if (SERVER != 'stage') {
					exit('Disabled');
				}

			//--------------------------------------------------
			// File helper

				$file = new file_aws_s3('code-poets');

				// if (true) {
				// 	$this->_test($file);
				// 	exit();
				// }

				$file->cleanup();

			//--------------------------------------------------
			// Existing file

				$file_id = request('file_id');
				$file_info = request('file_info');

				if ($file_id) {

					$action = request('action');

					if ($action == 'info') {

						debug($file->file_path_get($file_info, $file_id));
						debug($file->file_exists($file_info, $file_id));
						exit();

					} else if ($action == 'download') {

						http_download([
								'path' => $file->file_path_get($file_info, $file_id),
								'name' => 'example.jpg',
								'mode' => 'inline',
							]);

						exit();

					} else if ($action == 'delete') {

						debug($file->file_delete($file_info, $file_id));
						exit('Done');

					}

					$this->set('links', [
							'info'     => url(['action' => 'info']),
							'download' => url(['action' => 'download']),
							'delete'   => url(['action' => 'delete']),
						]);

					return;

				}

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Upload');

				$field_file = new form_field_file($form, 'JPG File');
				$field_file->max_size_set('The file cannot be bigger than XXX.', 1024*1024*1);
				$field_file->allowed_file_types_ext_set('The file has an unrecognised file type.', ['jpg']);
				$field_file->required_error_set('The file is required.');

			//--------------------------------------------------
			// Form processing

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Save

								$file_id = rand(10000, 99999);

								$file_info = $file->file_save($field_file->file_path_get(), $file_id);

							//--------------------------------------------------
							// Next page

								$form->dest_redirect(url(['file_id' => $file_id, 'file_info' => $file_info]));

						}

				}

			//--------------------------------------------------
			// Form defaults

				if ($form->initial()) {
				}

			//--------------------------------------------------
			// Variables

				$this->set('form', $form);

		}

		private function _test($file) {

			//--------------------------------------------------
			// Config

				$test_path = dirname(__FILE__) . '/aws-s3-upload.txt';
				$test_content = file_get_contents($test_path);

				$folders = [
						'local'     => $file->folder_path_get(),
						'deleted'   => $file->folder_path_get('ed'),
						'encrypted' => $file->folder_path_get('ef'),
						'plain'     => $file->folder_path_get('pf'),
						'backup'    => PRIVATE_ROOT . '/files/backup',
					];

				if (!is_dir($folders['local'])) {
					exit_with_error('Cannot find local folder', $folders['local']);
				}

				if (!is_dir($folders['backup'])) {
					exit_with_error('Cannot find backup folder', $folders['backup']);
				}

			//--------------------------------------------------
			// Reset

				rrmdir($folders['local'], false);
				rrmdir($folders['backup'], false);

			//--------------------------------------------------
			// Upload

				$file->config_set('aws_backup_folder', NULL);

				$files = [
						1 => $file->file_save($test_path),
						2 => $file->file_save_contents($test_content, 2),
						3 => $file->file_import($test_path, 3),
					];

				if ($this->_test_count_files($folders['encrypted']) != 2) {
					exit_with_error('There should only be 2 files in the encrypted folder, as the 3rd one was done via "file_import".');
				}

			//--------------------------------------------------
			// Clear local, and get files back from AWS

				rrmdir($folders['local'], false);

				if ($file->file_exists($files[1]) !== true) {
					exit_with_error('The file should still appear to exist.');
				}

				debug($file->file_path_get($files[1]));
				debug($file->file_path_get($files[2], 2));
				debug($file->file_path_get($files[3], 3));

				$correct_error = false;
				try {
					debug($file->file_path_get($files[2]));
				} catch (Exception $e) {
					$correct_error = ($e->getMessage() == 'Invalid encrypted message');
				}
				if ($correct_error !== true) {
					exit_with_error('Did not complain that the $file_id is wrong.');
				}

			//--------------------------------------------------
			// Make a backup (i.e. `aws sync`), and delete local.

				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folders['encrypted'], RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
				foreach ($iterator as $item) {
					$dest = $folders['backup'] . '/' . $iterator->getSubPathName();
					if ($item->isDir()) {
						mkdir($dest);
					} else {
						copy($item, $dest);
					}
				}

				rrmdir($folders['local'], false);

			//--------------------------------------------------
			// Intentionally fail to get from backup

				$file->config_set('aws_backup_folder', $folders['backup'] . '/error');

				$correct_error = false;
				try {
					debug($file->file_path_get($files[1]));
				} catch (Exception $e) {
					$correct_error = ($e->getMessage() == 'Could not return file.');
				}
				if ($correct_error !== true) {
					exit_with_error('Was successful when trying to return a file from a bad backup.');
				}

			//--------------------------------------------------
			// Get from backup

				$file->config_set('aws_backup_folder', $folders['backup']);

				if ($file->file_exists($files[1]) !== true) {
					exit_with_error('The file should still appear to exist.');
				}

				if ($this->_test_count_files($folders['encrypted']) != 0) {
					exit_with_error('There should be 0 files in the encrypted folder, as the paths have not been requested yet.');
				}

				debug($file->file_path_get($files[1]));
				debug($file->file_path_get($files[2], 2));
				debug($file->file_path_get($files[3], 3));

			//--------------------------------------------------
			// Check content

				$content = file_get_contents($file->file_path_get($files[1]));

				if ($content != $test_content) {
					exit_with_error('The test content is wrong.', $content);
				}

			//--------------------------------------------------
			// Cannot delete files on backup server

				$file->config_set('aws_backup_folder', $folders['backup']);

				$correct_error = false;
				try {
					$file->file_delete($files[2], 2);
				} catch (Exception $e) {
					$correct_error = ($e->getMessage() == 'On the backup server, an AWS file cannot be deleted.');
				}
				if ($correct_error !== true) {
					exit_with_error('Was successful when deleting a file, when in backup mode.');
				}

			//--------------------------------------------------
			// Delete files

				$file->config_set('aws_backup_folder', NULL);

				$file->file_delete($files[1]);

				if ($file->file_exists($files[1]) !== false) {
					exit_with_error('File 1 still exists after a delete.');
				}

				if ($file->file_exists($files[2], 2) !== true) {
					exit_with_error('File 2 does not exist after deleting 1.');
				}

				$correct_error = false;
				try {
					debug($file->file_path_get($files[1]));
				} catch (Exception $e) {
					$correct_error = ($e->getMessage() == 'Could not return file.');
				}
				if ($correct_error !== true) {
					exit_with_error('Was successful when trying to return a deleted file.');
				}

			//--------------------------------------------------
			// Remove from backup

				$file->config_set('aws_backup_folder', $folders['backup']);

				if ($this->_test_count_files($folders['backup']) != 3) {
					exit_with_error('The backup folder should still have 3 files.');
				}

				$file->cleanup();

				if ($this->_test_count_files($folders['backup']) != 2) {
					exit_with_error('The deleted file was not removed from the backup.');
				}

			//--------------------------------------------------
			// Cleanup

				$file->config_set('aws_backup_folder', NULL);

				$file->file_delete($files[2], 2);
				$file->file_delete($files[3], 3);

				$file->config_set('aws_backup_folder', $folders['backup']);

				$file->cleanup();

				if ($this->_test_count_files($folders['deleted'])   != 3) exit_with_error('The deleted folder should contain 3 files.');
				if ($this->_test_count_files($folders['encrypted']) != 0) exit_with_error('The encrypted folder should be empty.');
				if ($this->_test_count_files($folders['plain'])     != 0) exit_with_error('The plain folder should be empty.');
				if ($this->_test_count_files($folders['backup'])    != 0) exit_with_error('The backup folder should be empty.');

				rrmdir($folders['local'], false);
				rrmdir($folders['backup'], false);

		}

		private function _test_count_files($folder) {
			$file_count = 0;
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($iterator as $item) {
				if ($item->isFile()) {
					$file_count++;
				}
			}
			return $file_count;
		}

	}

?>
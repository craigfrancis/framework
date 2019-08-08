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

				$file->cleanup();

			//--------------------------------------------------
			// Existing file

				$file_id = request('file_id');
				$file_info = request('file_info');

				if ($file_id) {

					// debug($file->file_exists($file_info, $file_id));

					// debug($file->file_path_get($file_info, $file_id));

					// debug($file->file_delete($file_info, $file_id));

					exit();

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

	}

?>
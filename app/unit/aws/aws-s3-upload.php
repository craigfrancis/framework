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

								$file = new file_aws_s3('aws-s3-example');

								$info = $file->file_save($field_file->file_path_get());

debug($info);

debug($file->file_path_get($info));

exit('Done');

							//--------------------------------------------------
							// Next page

								$form->dest_redirect(url());

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
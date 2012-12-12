<?php

	class form_field_file_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $max_size;

			protected $empty_file_error_set;
			protected $partial_file_error_set;
			protected $blank_name_error_set;

			protected $uploaded;

			protected $value_ext;
			protected $value_name;
			protected $value_size;
			protected $value_mime;
			protected $value_path;
			protected $value_saved;

			public function __construct($form, $label, $name = NULL) {
				$this->setup_file($form, $label, $name);
			}

		//--------------------------------------------------
		// Setup

			protected function setup_file($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup($form, $label, $name);

				//--------------------------------------------------
				// Form encoding

					$this->form->form_attribute_set('enctype', 'multipart/form-data');

				//--------------------------------------------------
				// Additional field configuration

					$this->type = 'file';

				//--------------------------------------------------
				// Default validation configuration

					$this->max_size = 0;

					$this->empty_file_error_set = false;
					$this->partial_file_error_set = false;
					$this->blank_name_error_set = false;

				//--------------------------------------------------
				// If uploaded

					$this->uploaded = ($this->form_submitted && isset($_FILES[$this->name]) && $_FILES[$this->name]['error'] != 4); // 4 = No file was uploaded (UPLOAD_ERR_NO_FILE)

				//--------------------------------------------------
				// File values

					$this->value_ext = NULL;
					$this->value_name = NULL;
					$this->value_size = NULL;
					$this->value_mime = NULL;
					$this->value_path = NULL;
					$this->value_saved = false;

					if ($this->uploaded) {

						$ext = pathinfo($_FILES[$this->name]['name'], PATHINFO_EXTENSION);
						if ($ext) {
							$this->value_ext = strtolower($ext);
						} else {
							$this->value_ext = ''; // File name missing value, which is not the same as NULL (useful distinction when sending to DB)
						}

						$this->value_name = $_FILES[$this->name]['name'];
						$this->value_size = $_FILES[$this->name]['size'];
						$this->value_mime = $_FILES[$this->name]['type'];
						$this->value_path = $_FILES[$this->name]['tmp_name'];

					}

			}

			public function placeholder_set($placeholder) {
				$this->placeholder = $placeholder;
			}

		//--------------------------------------------------
		// Errors

			public function required_error_set($error) {
				$this->required_error_set_html(html($error));
			}

			public function required_error_set_html($error_html) {

				if ($this->form_submitted && !$this->uploaded) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->required = ($error_html !== NULL);

			}

			public function max_size_set($error, $size = NULL) {
				$this->max_size_set_html(html($error), $size);
			}

			public function max_size_set_html($error_html, $size = NULL) {

				//--------------------------------------------------
				// Max size server will allow

					$this->max_size = 0;

					$server_max = $this->max_size_get();

				//--------------------------------------------------
				// Set

					if ($size === NULL) {
						if ($server_max > 0) {
							$this->max_size = intval($server_max);
						} else {
							exit_with_error('Cannot determine max file upload size for this server.');
						}
					} else {
						if ($server_max == 0 || $size <= $server_max) {
							$this->max_size = intval($size);
						} else {
							exit_with_error('The maximum file size the server accepts is "' . file_size_to_human($server_max) . '" (' . $server_max . ')', 'upload_max_filesize = ' . ini_get('upload_max_filesize') . "\n" . 'post_max_size = ' . ini_get('post_max_size'));
						}
					}

				//--------------------------------------------------
				// Validation on upload

					if ($this->uploaded) {

						$error_html = str_replace('XXX', file_size_to_human($this->max_size), $error_html);

						if ($_FILES[$this->name]['error'] == 1) $this->form->_field_error_set_html($this->form_field_uid, $error_html, 'ERROR: Exceeds "upload_max_filesize" ' . ini_get('upload_max_filesize'));
						if ($_FILES[$this->name]['error'] == 2) $this->form->_field_error_set_html($this->form_field_uid, $error_html, 'ERROR: Exceeds "MAX_FILE_SIZE" specified in the html form');

						if ($_FILES[$this->name]['size'] >= $this->max_size) {
							$this->form->_field_error_set_html($this->form_field_uid, $error_html);
						}

					}

			}

			public function max_size_get() {

				$size = $this->max_size;

				if ($size == 0) {

					foreach (array('upload_max_filesize', 'post_max_size') as $ini) {

						if ($limit = ini_get($ini)) {

							$limit = file_size_to_bytes($limit);

							if (($limit > 0) && ($size == 0 || $size > $limit)) {
								$size = $limit;
							}

						}

					}

				}

				return $size;

			}

			public function partial_file_error_set($error) {
				$this->partial_file_error_set_html(html($error));
			}

			public function partial_file_error_set_html($error_html) {

				if ($this->uploaded) {
					if ($_FILES[$this->name]['error'] == 3) $this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->partial_file_error_set = true;

			}

			public function allowed_file_types_mime_set($error, $types) {
				$this->allowed_file_types_mime_set_html(html($error), $types);
			}

			public function allowed_file_types_mime_set_html($error_html, $types) {

				if ($this->uploaded && !in_array($this->value_mime, $types)) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $this->value_mime, $error_html), 'MIME: ' . $this->value_mime);
				}

			}

			public function allowed_file_types_ext_set($error, $types) {
				$this->allowed_file_types_ext_set_html(html($error), $types);
			}

			public function allowed_file_types_ext_set_html($error_html, $types) {

				if ($this->uploaded && !in_array($this->value_ext, $types)) {
					$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $this->value_ext, $error_html), 'EXT: ' . $this->value_ext);
				}

			}

			public function empty_file_error_set($error) {
				$this->empty_file_error_set_html(html($error));
			}

			public function empty_file_error_set_html($error_html) {

				if ($this->uploaded && $_FILES[$this->name]['size'] == 0) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->empty_file_error_set = true;

			}

			public function blank_name_error_set($error) {
				$this->blank_name_error_set_html(html($error));
			}

			public function blank_name_error_set_html($error_html) {

				if ($this->uploaded && $_FILES[$this->name]['name'] == '') {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->blank_name_error_set = true;

			}

		//--------------------------------------------------
		// Value

			public function value_get() {
				exit('<p>Do you mean file_path_get?</p>');
			}

			public function file_path_get() {
				return (!$this->uploaded ? NULL: $this->value_path);
			}

			public function file_ext_get() {
				return (!$this->uploaded ? NULL: $this->value_ext);
			}

			public function file_name_get() {
				return (!$this->uploaded ? NULL: $this->value_name);
			}

			public function file_size_get() {
				return (!$this->uploaded ? NULL: $this->value_size);
			}

			public function file_mime_get() {
				return (!$this->uploaded ? NULL: $this->value_mime);
			}

			public function file_save_to($path) {
				if ($this->uploaded) {

					$folder = dirname($path);
					if (!is_dir($folder)) {
						@mkdir($folder, 0777, true);
					}

					if (is_file($path) && !is_writable($path)) {
						exit_with_error('Cannot save file "' . $this->label_html . '", check destination file permissions.', $path);
					} else if (!is_writable(dirname($path))) {
						exit_with_error('Cannot save file "' . $this->label_html . '", check destination folder permissions.', dirname($path));
					}

					if ($this->value_saved) {

						$return = copy($this->value_path, $path);

					} else {

						$return = move_uploaded_file($this->value_path, $path);

						$this->value_saved = true;
						$this->value_path = $path;

					}

					@chmod($path, 0666); // Most websites use a generic apache user.

					return $return;

				}
				return false;
			}

		//--------------------------------------------------
		// Status

			public function uploaded() {
				return $this->uploaded;
			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->max_size == 0) {
					exit('<p>You need to call "max_size_set", on the field "' . $this->label_html . '"</p>');
				}

				if ($this->form_submitted && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') { // If not set, assume its correct
					exit('<p>The form needs the attribute: <strong>enctype="multipart/form-data"</strong></p>');
				}

				if ($this->empty_file_error_set == false) { // Provide default
					$this->empty_file_error_set('The uploaded file for "' . strtolower($this->label_html) . '" is empty');
				}

				if ($this->partial_file_error_set == false) { // Provide default
					$this->partial_file_error_set('The uploaded file for "' . strtolower($this->label_html) . '" was only partially uploaded');
				}

				if ($this->blank_name_error_set == false) { // Provide default
					$this->blank_name_error_set('The uploaded file for "' . strtolower($this->label_html) . '" does not have a filename');
				}

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				return $this->_html_input(array(
						'type' => 'file',
					));
			}

	}

?>
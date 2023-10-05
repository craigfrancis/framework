<?php

	class form_field_file_base extends form_field {

		//--------------------------------------------------
		// Variables

			protected $multiple = false;
			protected $max_size = 0;

			protected $empty_file_error_set = false;
			protected $partial_file_error_set = false;
			protected $blank_name_error_set = false;
			protected $long_name_error_set = false;

			protected $files = [];
			protected $file_current = 0;
			protected $file_mime_accept = NULL;
			protected $uploaded = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {
				$this->setup_file($form, $label, $name, 'file');
			}

			protected function setup_file($form, $label, $name, $type) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup($form, $label, $name, $type);

				//--------------------------------------------------
				// First file field

					$this->form->_field_setup_file();

				//--------------------------------------------------
				// Newly uploaded files

					$this->files = [];

					if ($this->form_submitted && isset($_FILES[$this->name]['error']) && is_array($_FILES[$this->name]['error'])) {
						foreach ($_FILES[$this->name]['error'] as $key => $error) {
							$file_info = $this->file_store($this->name, $key);
							if ($file_info) {

								$file_info['preserve'] = ($file_info['hash'] !== NULL);

								$this->files[] = $file_info;

							}
						}
					}

				//--------------------------------------------------
				// Hidden files

					if (count($this->files) == 0) { // If user uploaded one or more files, assume it is to replace.

						if ($this->form_submitted) {
							$hidden_files = $this->form->hidden_value_get('h-' . $this->name);
						} else if ($this->form->saved_values_available()) {
							$hidden_files = $this->form->saved_value_get('h-' . $this->name); // Looks for hidden field, saved_values does not include $_FILES
							if ($hidden_files !== NULL) {
								$hidden_files = urldecode($hidden_files);
							}
						} else {
							$hidden_files = NULL;
						}

						if ($hidden_files !== NULL) {
							foreach (explode('-', $hidden_files) as $file_hash) {
								$file_info = $this->file_info($file_hash);
								if ($file_info) {

									$file_info['preserve'] = true;

									$this->files[] = $file_info;

								}
							}
						}

					}

				//--------------------------------------------------
				// Config

					$this->uploaded = (count($this->files) > 0); // Shortcut
					$this->file_current = 0;

			}

			public function multiple_set($multiple) {
				$this->multiple = $multiple;
			}

			public function multiple_get() {
				return $this->multiple;
			}

			public function placeholder_set($placeholder) {
				$this->placeholder = $placeholder;
			}

		//--------------------------------------------------
		// Storing files support

			public static function file_store($file_name, $file_offset = NULL) {

				//--------------------------------------------------
				// Return details

					if (!isset($_FILES[$file_name]['error'])) {

						return NULL;

					} else if (!is_array($_FILES[$file_name]['error'])) {

						$file_info = $_FILES[$file_name];
						$file_offset = NULL;

					} else {

						if ($file_offset === NULL) {

							$file_offset = key($_FILES[$file_name]['error']);

						} else if (!isset($_FILES[$file_name]['error'][$file_offset])) {

							return NULL;

						}

						$file_info = [];
						foreach ($_FILES[$file_name] as $key => $value) {
							$file_info[$key] = $value[$file_offset];
						}

					}

				//--------------------------------------------------
				// File not uploaded

					if ($file_info['error'] == 4) { // 4 = No file was uploaded (UPLOAD_ERR_NO_FILE)
						return NULL;
					}

				//--------------------------------------------------
				// Extension

					$file_ext = pathinfo($file_info['name'], PATHINFO_EXTENSION);
					if ($file_ext) {
						$file_info['ext'] = strtolower($file_ext);
					} else {
						$file_info['ext'] = ''; // File name missing value, which is not the same as NULL (useful distinction when sending to DB)
					}

				//--------------------------------------------------
				// Mime - backwards computability

					$file_info['mime'] = $file_info['type'];

					unset($file_info['type']);

				//--------------------------------------------------
				// Store

					if ($file_info['error'] == 0) {

						if (!is_uploaded_file($file_info['tmp_name'])) {
							exit_with_error('Only "uploaded" files can be processed with form_field_file', 'Path: ' . $file_info['tmp_name']);
						}

						$file_hash = hash('sha256', $file_info['tmp_name']); // Temp name should be unique, and faster than hasing the contents of the whole file.
						$file_path = form_field_file::_file_tmp_folder() . '/' . $file_hash;

						move_uploaded_file($file_info['tmp_name'], $file_path);

						unset($file_info['tmp_name']);

						$file_info['hash'] = $file_hash;
						$file_info['path'] = $file_path;

						file_put_contents($file_path . '.json', json_encode($file_info));

						$config_name = $file_name . ($file_offset !== NULL ? '[' . $file_offset . ']' : '');

						config::array_set('request.file_paths', $config_name, $file_path);

					} else {

						$file_info['hash'] = NULL;
						$file_info['path'] = NULL;

					}

				//--------------------------------------------------
				// Return

					return $file_info;

			}

			public static function file_info($file_hash) {

				$info_path = form_field_file::_file_tmp_folder() . '/' . $file_hash . '.json';

				if (is_file($info_path)) {
					return json_decode(file_get_contents($info_path), true); // As an array
				} else {
					return NULL;
				}

			}

			public static function _file_tmp_folder() {

				$tmp_folder = config::get('form.file_tmp_folder'); // Cached value, so old file check is only done once.

				if ($tmp_folder === NULL) {

					$tmp_folder = tmp_folder('form-file');

					unlink_old_files($tmp_folder, strtotime('-1 hour'));

					config::set('form.file_tmp_folder', $tmp_folder);

				}

				return $tmp_folder;

			}

		//--------------------------------------------------
		// Errors

			public function required_error_set($error) {
				$this->required_error_set_html(to_safe_html($error));
			}

			public function required_error_set_html($error_html) {

				if ($this->form_submitted && !$this->uploaded) {
					$this->form->_field_error_set_html($this->form_field_uid, $error_html);
				}

				$this->required = ($error_html !== NULL);

			}

			public function max_size_set($error, $size = NULL) {
				$this->max_size_set_html(to_safe_html($error), $size);
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
							exit_with_error('The maximum file size the server accepts is "' . format_bytes($server_max) . '" (' . $server_max . '), not "' . format_bytes($size) . '" (' . $size . ')', 'upload_max_filesize = ' . ini_get('upload_max_filesize') . "\n" . 'post_max_size = ' . ini_get('post_max_size'));
						}
					}

				//--------------------------------------------------
				// Validation on upload

					if ($this->uploaded) {

						$error_html = str_replace('XXX', format_bytes($this->max_size), $error_html);

						foreach ($this->files as $id => $file) {

							if ($file['error'] == 1) {
								$this->form->_field_error_set_html($this->form_field_uid, $error_html, 'ERROR: Exceeds "upload_max_filesize" ' . ini_get('upload_max_filesize'));
								$this->files[$id]['preserve'] = false;
							}

							if ($file['error'] == 2) {
								$this->form->_field_error_set_html($this->form_field_uid, $error_html, 'ERROR: Exceeds "MAX_FILE_SIZE" specified in the html form');
								$this->files[$id]['preserve'] = false;
							}

							if ($file['size'] >= $this->max_size) {
								$this->form->_field_error_set_html($this->form_field_uid, $error_html);
								$this->files[$id]['preserve'] = false;
							}

						}

					}

			}

			public function max_size_get() {

				$size = $this->max_size;

				if ($size == 0) {

					foreach (array('upload_max_filesize', 'post_max_size') as $ini) {

						if ($limit = ini_get($ini)) {

							$limit = parse_bytes($limit);

							if (($limit > 0) && ($size == 0 || $size > $limit)) {
								$size = $limit;
							}

						}

					}

				}

				return $size;

			}

			public function partial_file_error_set($error) {
				$this->partial_file_error_set_html(to_safe_html($error));
			}

			public function partial_file_error_set_html($error_html) {

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if ($file['error'] == 3) {
							$this->form->_field_error_set_html($this->form_field_uid, $error_html);
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->partial_file_error_set = true;

			}

			public function allowed_file_types_mime_set($error, $types) {
				$this->allowed_file_types_mime_set_html(to_safe_html($error), $types);
			}

			public function allowed_file_types_mime_set_html($error_html, $types) {

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if (!in_array($file['mime'], $types)) {
							$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $file['mime'], $error_html), 'MIME: ' . $file['mime']);
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->file_mime_accept = $types;

			}

			public function allowed_file_types_ext_set($error, $types) {
				$this->allowed_file_types_ext_set_html(to_safe_html($error), $types);
			}

			public function allowed_file_types_ext_set_html($error_html, $types) {

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if (!in_array($file['ext'], $types)) {
							$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $file['ext'], $error_html), 'EXT: ' . $file['ext']);
							$this->files[$id]['preserve'] = false;
						}
					}
				}

			}

			public function empty_file_error_set($error) {
				$this->empty_file_error_set_html(to_safe_html($error));
			}

			public function empty_file_error_set_html($error_html) {

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if ($file['size'] == 0) {
							$this->form->_field_error_set_html($this->form_field_uid, $error_html);
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->empty_file_error_set = true;

			}

			public function blank_name_error_set($error) {
				$this->blank_name_error_set_html(to_safe_html($error));
			}

			public function blank_name_error_set_html($error_html) {

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						$name = pathinfo($file['name'], PATHINFO_FILENAME); // Exclude extension - Don't want ".jpg" passing PHP "jpg" check, but being seen as a hidden file with no extension by the web server.
						if ($name == '') {
							$this->form->_field_error_set_html($this->form_field_uid, $error_html);
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->blank_name_error_set = true;

			}

			public function long_name_error_set($error, $length = 100) {
				$this->long_name_error_set_html(to_safe_html($error), $length);
			}

			public function long_name_error_set_html($error_html, $length = 100) {

				$error_html = str_replace('XXX', $length, $error_html);

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if (strlen($file['name']) > $length) {
							$html = str_replace('[FILE_NAME]', $file['name'], $error_html);
							$this->form->_field_error_set_html($this->form_field_uid, $html);
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->long_name_error_set = true;

			}

		//--------------------------------------------------
		// Status

			public function uploaded() {
				return (isset($this->files[$this->file_current]));
			}

			public function upload_next() { // For multi-file uploads
				$this->file_current++;
			}

			public function upload_reset() {
				$this->file_current = 0;
			}

		//--------------------------------------------------
		// Errors

			public function error_file_add($id, $error, $hidden_info = NULL) {

				if (isset($this->files[$id])) {
					$this->files[$id]['preserve'] = false;
				} else {
					exit_with_error('Unknown file id "' . $id . '"');
				}

				$this->error_add($error, $hidden_info);

			}

		//--------------------------------------------------
		// Value

			public function value_get() {
				exit('<p>Do you mean file_path_get?</p>');
			}

			public function value_file_names_get() {
				$file_names = [];
				foreach ($this->files as $file) {
					if ($file['preserve']) {
						$file_names[] = $file['name'];
					}
				}
				return $file_names;
			}

			public function value_hashes_get() {
				$hashes = [];
				foreach ($this->files as $file) {
					if ($file['preserve']) {
						$hashes[] = $file['hash'];
					}
				}
				return $hashes;
			}

			public function value_hidden_get() {
				$hashes = $this->value_hashes_get();
				if (count($hashes) > 0) {
					return implode('-', $hashes);
				} else {
					return NULL;
				}
			}

			public function files_get() {
				$return = [];
				foreach ($this->files as $file) {
					if ($file['error'] == 0) {
						$return[] = array(
								'path' => $file['path'],
								'ext' => $file['ext'],
								'name' => $file['name'],
								'size' => $file['size'],
								'mime' => $file['mime'],
							); // Don't expose preserve/error/hash keys
					}
				}
				return $return;
			}

			public function file_id_get() {
				return $this->file_current;
			}

			public function file_path_get() {
				return $this->_file_info_get('path');
			}

			public function file_ext_get() {
				return $this->_file_info_get('ext');
			}

			public function file_name_get() {
				return $this->_file_info_get('name');
			}

			public function file_size_get() {
				return $this->_file_info_get('size');
			}

			public function file_mime_get() {
				return $this->_file_info_get('mime');
			}

			public function file_save_to($path_dst) {
				$path_src = $this->file_path_get();
				if ($path_src) {

					$folder = dirname($path_dst);
					if (!is_dir($folder)) {
						@mkdir($folder, 0777, true);
					}

					if (is_file($path_dst) && !is_writable($path_dst)) {
						exit_with_error('Cannot save file "' . $this->label_html . '", check destination file permissions.', $path_dst);
					} else if (!is_writable(dirname($path_dst))) {
						exit_with_error('Cannot save file "' . $this->label_html . '", check destination folder permissions.', dirname($path_dst));
					}

					$return = copy($path_src, $path_dst); // Don't unlink/rename, as the same file may have been uploaded multiple times.

					@chmod($path_dst, octdec(config::get('file.default_permission', 666)));

					return $return;

				}
				return false;
			}

			protected function _file_info_get($field) {
				if (isset($this->files[$this->file_current][$field])) {
					return $this->files[$this->file_current][$field];
				} else {
					return NULL;
				}
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
					$this->empty_file_error_set('The uploaded file for "' . strtolower($this->label_html) . '" is empty.');
				}

				if ($this->partial_file_error_set == false) { // Provide default
					$this->partial_file_error_set('The uploaded file for "' . strtolower($this->label_html) . '" was only partially uploaded.');
				}

				if ($this->blank_name_error_set == false) { // Provide default
					$this->blank_name_error_set('The uploaded file for "' . strtolower($this->label_html) . '" does not have a filename.');
				}

				if ($this->long_name_error_set == false) { // Provide default
					$this->long_name_error_set('The uploaded file "[FILE_NAME]", has a filename that is too long (max XXX characters).'); // for "' . strtolower($this->label_html) . '"
				}

			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {

				$attributes = parent::_input_attributes();

				if (isset($attributes['required']) && count($this->value_hashes_get()) > 0) {
					unset($attributes['required']); // Preserved files though hidden field
				}

				$attributes['type'] = 'file';
				$attributes['name'] .= '[]'; // Treat all uploads as supporting multiple files, we will still just use the first one.

				if ($this->multiple) {
					$attributes['multiple'] = 'multiple';
				}

				if ($this->file_mime_accept !== NULL && count($this->file_mime_accept) == 1) {
					$attributes['accept'] = reset($this->file_mime_accept);
				}

				return $attributes;

			}

		//--------------------------------------------------
		// HTML

			public function html_input() {
				return $this->_html_input();
			}

			public function html_info($indent = 0) {

				$file_names = $this->value_file_names_get();
				if (count($file_names) > 0) {
					if ($this->multiple) {
						$file_names = implode(', ', $file_names);
					} else {
						$file_names = array_shift($file_names);
					}
					$this->info_html = html($file_names) . ($this->info_html ? ' | ' . $this->info_html : '');
				}

				return parent::html_info($indent);

			}

	}

?>
<?php

	class form_field_image_base extends form_field_file {

		//--------------------------------------------------
		// Variables

			protected $min_width_size = 0;
			protected $max_width_size = 0;
			protected $min_height_size = 0;
			protected $max_height_size = 0;

			protected $file_type_error_set = false;

		//--------------------------------------------------
		// Setup

			public function __construct($form, $label, $name = NULL) {

				//--------------------------------------------------
				// Perform the standard field setup

					$this->setup_file($form, $label, $name, 'image');

				//--------------------------------------------------
				// File values

					foreach ($this->files as $id => $file) {

						$dimensions = ($file['path'] == '' ? false : getimagesize($file['path']));
						if ($dimensions !== false) {

							if ($dimensions[2] == IMAGETYPE_JPEG) {
								$image_type = 'jpg';
							} else if ($dimensions[2] == IMAGETYPE_GIF) {
								$image_type = 'gif';
							} else if ($dimensions[2] == IMAGETYPE_PNG) {
								$image_type = 'png';
							} else {
								$image_type = NULL;
							}

							$this->files[$id]['image_width'] = $dimensions[0];
							$this->files[$id]['image_height'] = $dimensions[1];
							$this->files[$id]['image_type'] = $image_type;

						} else {

							$this->files[$id]['image_width'] = NULL;
							$this->files[$id]['image_height'] = NULL;
							$this->files[$id]['image_type'] = NULL;

						}

					}

			}

		//--------------------------------------------------
		// Errors

			public function min_width_set($error, $size) {
				$this->min_width_set_html(html($error), $size);
			}

			public function min_width_set_html($error_html, $size) {

				$size = intval($size);

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if ($file['image_width'] !== NULL && $file['image_width'] < $size) {
							$this->form->_field_error_add_html($this->form_field_uid, str_replace('XXX', $size . 'px', $error_html), $file['image_width'] . 'px');
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->min_width_size = $size;

			}

			public function max_width_set($error, $size) {
				$this->max_width_set_html(html($error), $size);
			}

			public function max_width_set_html($error_html, $size) {

				$size = intval($size);

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if ($file['image_width'] !== NULL && $file['image_width'] > $size) {
							$this->form->_field_error_add_html($this->form_field_uid, str_replace('XXX', $size . 'px', $error_html), $file['image_width'] . 'px');
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->max_width_size = $size;

			}

			public function required_width_set($error, $size) {
				$this->required_width_set_html(html($error), $size);
			}

			public function required_width_set_html($error_html, $size) {

				$size = intval($size);

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if ($file['image_width'] !== NULL && $file['image_width'] != $size) {
							$this->form->_field_error_add_html($this->form_field_uid, str_replace('XXX', $size . 'px', $error_html), $file['image_width'] . 'px');
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->min_width_size = $size;
				$this->max_width_size = $size;

			}

			public function required_width_min_get() {
				return $this->min_width_size;
			}

			public function required_width_max_get() {
				return $this->max_width_size;
			}

			public function required_width_get() {
				return $this->min_width_size;
			}

			public function min_height_set($error, $size) {
				$this->min_height_set_html(html($error), $size);
			}

			public function min_height_set_html($error_html, $size) {

				$size = intval($size);

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if ($file['image_height'] !== NULL && $file['image_height'] < $size) {
							$this->form->_field_error_add_html($this->form_field_uid, str_replace('XXX', $size . 'px', $error_html), $file['image_height'] . 'px');
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->min_height_size = $size;

			}

			public function max_height_set($error, $size) {
				$this->max_height_set_html(html($error), $size);
			}

			public function max_height_set_html($error_html, $size) {

				$size = intval($size);

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if ($file['image_height'] !== NULL && $file['image_height'] > $size) {
							$this->form->_field_error_add_html($this->form_field_uid, str_replace('XXX', $size . 'px', $error_html), $file['image_height'] . 'px');
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->max_height_size = $size;

			}

			public function required_height_set($error, $size) {
				$this->required_height_set_html(html($error), $size);
			}

			public function required_height_set_html($error_html, $size) {

				$size = intval($size);

				if ($this->uploaded) {
					foreach ($this->files as $id => $file) {
						if ($file['image_height'] !== NULL && $file['image_height'] != $size) {
							$this->form->_field_error_add_html($this->form_field_uid, str_replace('XXX', $size . 'px', $error_html), $file['image_height'] . 'px');
							$this->files[$id]['preserve'] = false;
						}
					}
				}

				$this->min_height_size = $size;
				$this->max_height_size = $size;

			}

			public function required_height_min_get() {
				return $this->min_height_size;
			}

			public function required_height_max_get() {
				return $this->max_height_size;
			}

			public function required_height_get() {
				return $this->min_height_size;
			}

			public function file_type_error_set($error, $types = NULL) {
				$this->file_type_error_set_html(html($error), $types);
			}

			public function file_type_error_set_html($error_html, $types = NULL) {

				//--------------------------------------------------
				// Types

					if ($types === NULL) {
						$types = array('gif', 'jpg', 'png');
					}

				//--------------------------------------------------
				// Validate the mime type

					$mime_types = array();

					if (in_array('gif', $types)) {
						$mime_types[] = 'image/gif';
					}

					if (in_array('jpg', $types)) {
						$mime_types[] = 'image/jpeg';
						$mime_types[] = 'image/pjpeg'; // The wonderful world of IE
					}

					if (in_array('png', $types)) {
						$mime_types[] = 'image/png';
						$mime_types[] = 'image/x-png'; // The wonderful world of IE
					}

					parent::allowed_file_types_mime_set_html($error_html, $mime_types);

				//--------------------------------------------------
				// Could not use getimagesize

					if ($this->uploaded) {
						foreach ($this->files as $id => $file) {

							if ($file['image_type'] == NULL) {

								$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', 'invalid image', $error_html), 'ERROR: Failed getimagesize');

							} else {

								if (!in_array($file['image_type'], $types)) {
									$this->form->_field_error_set_html($this->form_field_uid, str_replace('XXX', $file['image_type'], $error_html), 'ERROR: Non valid type (' . implode(', ', $types) . ')');
								}

							}

						}
					}

				//--------------------------------------------------
				// Done

					$this->file_type_error_set = true;

			}

		//--------------------------------------------------
		// Value

			public function image_width_get() {
				return $this->_file_info_get('image_width');
			}

			public function image_height_get() {
				return $this->_file_info_get('image_height');
			}

			public function image_type_get() {
				return $this->_file_info_get('image_type');
			}

		//--------------------------------------------------
		// Validation

			public function _post_validation() {

				parent::_post_validation();

				if ($this->file_type_error_set == false) {
					exit('<p>You need to call "file_type_error_set", on the field "' . $this->label_html . '"</p>');
				}

			}

		//--------------------------------------------------
		// Attributes

			protected function _input_attributes() {
				$attributes = parent::_input_attributes();
				$attributes['accept'] = 'image/*';
				return $attributes;
			}

	}

?>
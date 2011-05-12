<?php

	class form_field_image extends form_field_file {

		protected $min_width_size;
		protected $max_width_size;
		protected $min_height_size;
		protected $max_height_size;

		protected $file_type_error_set;

		protected $value_image_width;
		protected $value_image_height;
		protected $value_image_type;

		function form_field_image(&$form, $label, $name = NULL) {

			//--------------------------------------------------
			// Perform the standard field setup

				$this->_setup_file($form, $label, $name);

			//--------------------------------------------------
			// Additional field configuration

				$this->quick_print_type = 'image';

			//--------------------------------------------------
			// Default validation configuration

				$this->min_width_size = 0;
				$this->max_width_size = 0;
				$this->min_height_size = 0;
				$this->max_height_size = 0;

				$this->file_type_error_set = false;

			//--------------------------------------------------
			// File values

				$this->value_image_width = NULL;
				$this->value_image_height = NULL;
				$this->value_image_type = NULL;

				if ($this->has_uploaded) {

					$dimensions = getimagesize($this->get_file_path());
					if ($dimensions !== false) {
						$this->value_image_width = $dimensions[0];
						$this->value_image_height = $dimensions[1];
						$this->value_image_type = $dimensions[2];
					}

				}

		}

		function set_min_width($error, $size) {

			$size = intval($size);

			if ($this->value_image_width !== NULL && $this->value_image_width < $size) {
				$this->form->_field_error_add($this->form_field_uid, str_replace('XXX', $size . 'px', $error));
			}

			$this->min_width_size = $size;

		}

		function set_max_width($error, $size) {

			$size = intval($size);

			if ($this->value_image_width !== NULL && $this->value_image_width > $size) {
				$this->form->_field_error_add($this->form_field_uid, str_replace('XXX', $size . 'px', $error));
			}

			$this->max_width_size = $size;

		}

		function set_required_width($error, $size) {

			$size = intval($size);

			if ($this->value_image_width !== NULL && $this->value_image_width != $size) {
				$this->form->_field_error_add($this->form_field_uid, str_replace('XXX', $size . 'px', $error));
			}

			$this->min_width_size = $size;
			$this->max_width_size = $size;

		}

		function get_required_width_min() {
			return $this->min_width_size;
		}

		function get_required_width_max() {
			return $this->max_width_size;
		}

		function get_required_width() {
			return $this->min_width_size;
		}

		function set_min_height($error, $size) {

			$size = intval($size);

			if ($this->value_image_height !== NULL && $this->value_image_height < $size) {
				$this->form->_field_error_add($this->form_field_uid, str_replace('XXX', $size . 'px', $error));
			}

			$this->min_height_size = $size;

		}

		function set_max_height($error, $size) {

			$size = intval($size);

			if ($this->value_image_height !== NULL && $this->value_image_height > $size) {
				$this->form->_field_error_add($this->form_field_uid, str_replace('XXX', $size . 'px', $error));
			}

			$this->max_height_size = $size;

		}

		function set_required_height($error, $size) {

			$size = intval($size);

			if ($this->value_image_height !== NULL && $this->value_image_height != $size) {
				$this->form->_field_error_add($this->form_field_uid, str_replace('XXX', $size . 'px', $error));
			}

			$this->min_height_size = $size;
			$this->max_height_size = $size;

		}

		function get_required_height_min() {
			return $this->min_height_size;
		}

		function get_required_height_max() {
			return $this->max_height_size;
		}

		function get_required_height() {
			return $this->min_height_size;
		}

		function set_file_type_error($error, $types = NULL) {

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

				parent::set_allowed_file_types_mime($error, $mime_types);

			//--------------------------------------------------
			// Could not use getimagesize

				if ($this->has_uploaded) {

					if ($this->value_image_type == NULL) {

						$this->form->_field_error_set_html($this->form_field_uid, $error, 'ERROR: Failed getimagesize');

					} else {

						$valid = false;
						if (in_array('gif', $types) && $this->value_image_type == IMAGETYPE_GIF) $valid = true;
						if (in_array('jpg', $types) && $this->value_image_type == IMAGETYPE_JPEG) $valid = true;
						if (in_array('png', $types) && $this->value_image_type == IMAGETYPE_PNG) $valid = true;

						if (!$valid) {
							$this->form->_field_error_set_html($this->form_field_uid, $error, 'ERROR: Non valid type (' . implode(', ', $types) . ')');
						}

					}

				}

			//--------------------------------------------------
			// Done

				$this->file_type_error_set = true;

		}

		function get_image_width() {
			return $this->value_image_width;
		}

		function get_image_height() {
			return $this->value_image_height;
		}

		function get_image_type() {
			return $this->value_image_type;
		}

		function _error_check() {

			parent::_error_check();

			if ($this->file_type_error_set == false) {
				exit('<p>You need to call "set_file_type_error", on the field "' . $this->label_html . '"</p>');
			}

		}

	}

?>
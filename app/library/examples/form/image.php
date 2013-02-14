<?php

	$field = new form_field_image($form, 'Image');
	$field->max_size_set('The image file cannot be bigger than XXX.', 1024*1024*1);
	$field->min_width_set('The image must be more than XXX wide.', 300);
	$field->max_width_set('The image must not be more than XXX wide.', 500);
	$field->min_height_set('The image must be more than XXX high.', 300);
	$field->max_height_set('The image must not be more than XXX high.', 500);
	$field->required_error_set('The image is required.');
	$field->file_type_error_set('The image file has an unrecognised file type (XXX).');
	// $field->multiple_set(true);

?>
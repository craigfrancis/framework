<?php

	$field = new form_field_file($form, 'File');
	$field->max_size_set('The file cannot be bigger than XXX.', 1024*1024*1);
	$field->allowed_file_types_mime_set('The file has an unrecognised file type (XXX).', array('text/plain', 'application/pdf'));
	$field->allowed_file_types_ext_set('The file has an unrecognised file type (XXX).', array('txt', 'pdf'));
	$field->required_error_set('The file is required.');

?>
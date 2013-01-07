<?php

	$version = request('version');

	if ($version == 2) {
		config::set('form.date_format_input_html', array('Y', 'M', 'D'));
		config::set('form.date_format_label_html', array('separator' => '-'));
	}

	$field = new form_field_date($form, 'Date');
	if ($database) $field->db_field_set('date');
	$field->invalid_error_set('The date does not appear to be correct.');

	if ($version == 3) {

		$field->format_input_set(array('Y', 'M', 'D'));
		$field->format_label_set(array('separator' => '-'));

	} else if ($version == 4) {

		$field->format_label_set(array('separator' => '-', 'D' => 'Day', 'M' => 'Month', 'Y' => 'Year'));

	} else if ($version == 5) {

		$field->format_label_set(array('D' => 'DD', 'X', 'M' => 'MM', 'Y' => 'YyyY'));

	} else if ($version == 6) {

		$field->format_label_set('Year/Month/Day'); // But this looses the <label> tags.

	} else if ($version != 2) {

		$field->format_input_set(array('Y', '/', 'M', '/', 'D'));

	}

	//
	// OR
	//
	//
	// OR
	//
	// OR
	//

?>
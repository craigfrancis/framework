<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=time-select'));

//--------------------------------------------------
// Time

	$this->element_attribute_check('css', '.row', 'class', 'row time first_child odd time');

	$this->element_attribute_check('css', '.row label', 'for', 'fld_time_H');

	$this->element_name_check('id', 'fld_time_H', 'select');
	$this->element_name_check('id', 'fld_time_I', 'select');
	$this->element_name_check('id', 'fld_time_S', 'input');

	$this->element_text_check('css', '.format', 'HH:MM:SS');

//--------------------------------------------------
// Not specified

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"00:00:00"'); // Matches date field '0000-00-00' behaviour, rather than NULL

	$this->url_load(http_url('/examples/form/example/?type=time-select'));

//--------------------------------------------------
// Half specified

	$this->select_value_set('id', 'fld_time_H', '3');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"03:00:00"');

	$this->url_load(http_url('/examples/form/example/?type=time-select'));

//--------------------------------------------------
// Invalid option

	$this->session->execute(array('script' => 'document.getElementById("fld_time_I").outerHTML = "<input type=\"text\" id=\"fld_time_I\" name=\"time[I]\" />";', 'args' => array()));

	$this->select_value_set('id', 'fld_time_H', '22');
	$this->element_send_keys('id', 'fld_time_I', '5'); // Not 0, 15, 30, or 45
	$this->element_send_keys('id', 'fld_time_S', '59', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_value_check('id', 'fld_time_H', '22');
	$this->element_value_check('id', 'fld_time_I', ''); // Was an invalid selection
	$this->element_value_check('id', 'fld_time_S', '59');

	$this->element_text_check('css', 'ul.error_list li', 'Your time does not appear to be correct.');

//--------------------------------------------------
// Simple value, with hide/preserve tests

	$this->select_value_set('id', 'fld_time_H', '6');
	$this->select_value_set('id', 'fld_time_I', '15');
	$this->element_send_keys('id', 'fld_time_S', '23', array('clear' => true));

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"06:15:23"');

?>
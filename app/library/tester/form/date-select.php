<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=date-select'));

//--------------------------------------------------
// Date

	$this->element_attribute_check('css', '.row', 'class', 'row date first_child odd date');

	$this->element_attribute_check('css', '.row label', 'for', 'fld_date_D');

	$this->element_name_check('id', 'fld_date_D', 'select');
	$this->element_name_check('id', 'fld_date_M', 'select');
	$this->element_name_check('id', 'fld_date_Y', 'input');

	$this->element_text_check('css', '.format', 'DD/MM/YYYY');

//--------------------------------------------------
// Not specified

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The date is required.');

//--------------------------------------------------
// Half specified

	$this->element_send_keys('id', 'fld_date_D', '3');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The date does not appear to be correct.');

//--------------------------------------------------
// Invalid option

	$this->session->execute(array('script' => 'document.getElementById("fld_date_M").outerHTML = "<input type=\"text\" id=\"fld_date_M\" name=\"date_I\" />";', 'args' => array()));

	$this->element_send_keys('id', 'fld_date_M', '13');
	$this->element_send_keys('id', 'fld_date_Y', '2002', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The date does not appear to be correct.');

//--------------------------------------------------
// Simple value, with hide/preserve tests

	$this->element_send_keys('id', 'fld_date_M', 'April');

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"2002-04-03"');

?>
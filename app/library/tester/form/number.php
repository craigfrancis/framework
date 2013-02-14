<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=number'));

//--------------------------------------------------
// Missing value

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your number is required.');

//--------------------------------------------------
// Odd number

	$this->element_send_keys('id', 'fld_number', '10.1', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your number must be odd.');

//--------------------------------------------------
// Min value

	$this->element_send_keys('id', 'fld_number', '9', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your number must be more than or equal to 11.');

//--------------------------------------------------
// Max value

	$this->element_send_keys('id', 'fld_number', '10,001', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your number must be less than or equal to 9999.');

//--------------------------------------------------
// Full number example

	$this->element_send_keys('id', 'fld_number', '5,003', array('clear' => true));

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '5003');

?>
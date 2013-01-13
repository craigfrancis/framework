<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=time'));

//--------------------------------------------------
// Time

	$this->element_attribute_check('css', '.row', 'class', 'row time first_child odd time');

	$this->element_attribute_check('css', '.row label', 'for', 'fld_time_H');

	$this->element_attribute_check('id', 'fld_time_H', 'required', 'true');
	$this->element_attribute_check('id', 'fld_time_I', 'required', 'true');

	$this->element_attribute_check('id', 'fld_time_H', 'maxlength', '2');
	$this->element_attribute_check('id', 'fld_time_I', 'maxlength', '2');

	$this->element_attribute_check('id', 'fld_time_H', 'size', '2');
	$this->element_attribute_check('id', 'fld_time_I', 'size', '2');

	$this->element_name_check('id', 'fld_time_H', 'input');
	$this->element_name_check('id', 'fld_time_I', 'input');

	$this->element_text_check('css', '.format', 'HH:MM');

	$this->element_text_check('css', '.format label[for="fld_time_H"]', 'HH');
	$this->element_text_check('css', '.format label[for="fld_time_I"]', 'MM');

//--------------------------------------------------
// Not specified

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your time is required.');

//--------------------------------------------------
// Half specified

	$this->element_send_keys('id', 'fld_time_H', '3', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"03:00:00"');

	$this->url_load(http_url('/examples/form/example/?type=time'));

//--------------------------------------------------
// Invalid

	$this->element_send_keys('id', 'fld_time_H', '25', array('clear' => true));
	$this->element_send_keys('id', 'fld_time_I', '65', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your time does not appear to be correct.');

//--------------------------------------------------
// Too early (before 3am)

	$this->element_send_keys('id', 'fld_time_H', '2', array('clear' => true));
	$this->element_send_keys('id', 'fld_time_I', '0', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your time has to be after 3am.');

	$this->element_attribute_check('id', 'fld_time_H', 'value', '02'); // Gains 0 prefix, prompting user for 24 hour clock format
	$this->element_attribute_check('id', 'fld_time_I', 'value', '00');

//--------------------------------------------------
// Too late (in the future)

	$time = date('H:i', strtotime('+2 minutes'));

	$this->element_send_keys('id', 'fld_time_H', substr($time, 0, 2), array('clear' => true));
	$this->element_send_keys('id', 'fld_time_I', substr($time, 3, 2), array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your time cannot be set in the future.');

//--------------------------------------------------
// Simple value, with hide/preserve tests

	$this->element_send_keys('id', 'fld_time_H', '5', array('clear' => true));
	$this->element_send_keys('id', 'fld_time_I', '6', array('clear' => true));

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"05:06:00"');

?>
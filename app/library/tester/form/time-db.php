<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=time&database=true'));

//--------------------------------------------------
// Initial value

	$this->element_send_keys('id', 'fld_time_H', '4', array('clear' => true));
	$this->element_send_keys('id', 'fld_time_I', '7', array('clear' => true));

	$this->element_get('css', 'form')->submit();

//--------------------------------------------------
// Check initial value

	$this->url_load(http_url('/examples/form/example/?type=time&database=true'));

	$this->element_value_check('id', 'fld_time_H', '04'); // Gains 0 prefix, prompting user for 24 hour clock format
	$this->element_value_check('id', 'fld_time_I', '07');

//--------------------------------------------------
// New value

	$time = date('H:i:00');

	$this->element_send_keys('id', 'fld_time_H', substr($time, 0, 2), array('clear' => true));
	$this->element_send_keys('id', 'fld_time_I', substr($time, 3, 2), array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"' . $time . '"');

//--------------------------------------------------
// Check new value

	$this->url_load(http_url('/examples/form/example/?type=time&database=true'));

	$this->element_value_check('id', 'fld_time_H', substr($time, 0, 2));
	$this->element_value_check('id', 'fld_time_I', substr($time, 3, 2));

?>
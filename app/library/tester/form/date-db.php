<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=date&database=true'));

//--------------------------------------------------
// Initial value

	$this->element_send_keys('id', 'fld_date_D', '01', array('clear' => true));
	$this->element_send_keys('id', 'fld_date_M', '02', array('clear' => true));
	$this->element_send_keys('id', 'fld_date_Y', '2003', array('clear' => true));

	$this->element_get('css', 'form')->submit();

//--------------------------------------------------
// Check initial value

	$this->url_load(http_url('/examples/form/example/?type=date&database=true'));

	$this->element_attribute_check('id', 'fld_date_D', 'value', '1'); // Drops 0 prefix
	$this->element_attribute_check('id', 'fld_date_M', 'value', '2');
	$this->element_attribute_check('id', 'fld_date_Y', 'value', '2003');

//--------------------------------------------------
// New value

	$date = date('Y-m-d');

	$this->element_send_keys('id', 'fld_date_D', substr($date, 8, 2), array('clear' => true));
	$this->element_send_keys('id', 'fld_date_M', substr($date, 5, 2), array('clear' => true));
	$this->element_send_keys('id', 'fld_date_Y', substr($date, 0, 4), array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"' . $date . '"');

//--------------------------------------------------
// Check new value

	$this->url_load(http_url('/examples/form/example/?type=date&database=true'));

	$this->element_attribute_check('id', 'fld_date_D', 'value', strval(intval(substr($date, 8, 2)))); // Drops 0 prefix
	$this->element_attribute_check('id', 'fld_date_M', 'value', strval(intval(substr($date, 5, 2))));
	$this->element_attribute_check('id', 'fld_date_Y', 'value', strval(intval(substr($date, 0, 4))));

?>
<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=date'));

//--------------------------------------------------
// Date

	$this->element_attribute_check('css', '.row', 'class', 'row date first_child odd date');

	$this->element_attribute_check('css', '.row label', 'for', 'fld_date_D');

	$this->element_attribute_check('id', 'fld_date_D', 'required', 'true');
	$this->element_attribute_check('id', 'fld_date_M', 'required', 'true');
	$this->element_attribute_check('id', 'fld_date_Y', 'required', 'true');

	// $this->element_attribute_check('id', 'fld_date_D', 'autocomplete', 'bday-day');
	// $this->element_attribute_check('id', 'fld_date_M', 'autocomplete', 'bday-month');
	// $this->element_attribute_check('id', 'fld_date_Y', 'autocomplete', 'bday-year');

	$this->element_attribute_check('id', 'fld_date_D', 'maxlength', '2');
	$this->element_attribute_check('id', 'fld_date_M', 'maxlength', '2');
	$this->element_attribute_check('id', 'fld_date_Y', 'maxlength', '4');

	$this->element_attribute_check('id', 'fld_date_D', 'size', '2');
	$this->element_attribute_check('id', 'fld_date_M', 'size', '2');
	$this->element_attribute_check('id', 'fld_date_Y', 'size', '4');

	$this->element_name_check('id', 'fld_date_D', 'input');
	$this->element_name_check('id', 'fld_date_M', 'input');
	$this->element_name_check('id', 'fld_date_Y', 'input');

	$this->element_text_check('css', '.format', 'DD/MM/YYYY');

	$this->element_text_check('css', '.format label[for="fld_date_D"]', 'DD');
	$this->element_text_check('css', '.format label[for="fld_date_M"]', 'MM');
	$this->element_text_check('css', '.format label[for="fld_date_Y"]', 'YYYY');

//--------------------------------------------------
// Not specified

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your date is required.');

//--------------------------------------------------
// Half specified

	$this->element_send_keys('id', 'fld_date_D', '30', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your date does not appear to be correct.');

//--------------------------------------------------
// Invalid (30th February)

	$this->element_send_keys('id', 'fld_date_M', '2', array('clear' => true));
	$this->element_send_keys('id', 'fld_date_Y', '1999', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your date does not appear to be correct.');

//--------------------------------------------------
// Too early (before 2000)

	$this->element_send_keys('id', 'fld_date_D', '1', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your date has to be after 2000.');

//--------------------------------------------------
// Too late (in the future)

	$this->element_send_keys('id', 'fld_date_Y', (date('Y') + 1), array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your date cannot be set in the future.');

//--------------------------------------------------
// Simple value, with hide/preserve tests

	$year = (date('Y') - 1);
	$date = $year . '-02-01';

	$this->element_send_keys('id', 'fld_date_Y', $year, array('clear' => true));

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"' . $date . '"');

?>
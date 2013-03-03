<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=fields'));

//--------------------------------------------------
// Date

	$this->element_attribute_check('css', '.row', 'class', 'row fields first_child odd estimate');

	$this->element_attribute_check('css', '.row label', 'for', 'fld_estimate_V');

	$this->element_attribute_check('id', 'fld_estimate_V', 'required', 'true');
	$this->element_attribute_check('id', 'fld_estimate_U', 'required', 'true');

	// $this->element_attribute_check('id', 'fld_estimate_V', 'autocomplete', 'bday-day');
	// $this->element_attribute_check('id', 'fld_estimate_U', 'autocomplete', 'bday-month');

	$this->element_attribute_check('id', 'fld_estimate_V', 'maxlength', '3');

	$this->element_attribute_check('id', 'fld_estimate_V', 'size', '3');

	$this->element_name_check('id', 'fld_estimate_V', 'input');
	$this->element_name_check('id', 'fld_estimate_U', 'select');

//--------------------------------------------------
// Not specified

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The estimate is required.');

//--------------------------------------------------
// Half specified

	$this->element_send_keys('id', 'fld_estimate_V', '30', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The estimate is required.');

//--------------------------------------------------
// Invalid

	$this->session->execute(array('script' => 'document.getElementById("fld_estimate_U").outerHTML = "<input type=\"text\" id=\"fld_estimate_U\" name=\"estimate[U]\" />";', 'args' => array()));

	$this->element_send_keys('id', 'fld_estimate_U', 'XXX', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'An invalid estimate value has been set.');

//--------------------------------------------------
// Simple value, with hide/preserve tests

	$this->element_send_keys('id', 'fld_estimate_V', '2.5', array('clear' => true));
	$this->select_value_set('id', 'fld_estimate_U', 'days');

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '(' . "\n" . '    ["V"] => "2.5"' . "\n" . '    ["U"] => "days"' . "\n" . ')');

?>
<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=currency'));

//--------------------------------------------------
// Missing value

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your amount is required.');

//--------------------------------------------------
// Min value

	$this->element_send_keys('id', 'fld_amount', '-£5,001.23', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your amount must be more than or equal to £10.00.');

//--------------------------------------------------
// Max value

	$this->element_send_keys('id', 'fld_amount', '10,000', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your amount must be less than or equal to £9,999.00.');

//--------------------------------------------------
// Odd values

	//--------------------------------------------------

		$this->element_send_keys('id', 'fld_amount', '50,00', array('clear' => true)); // Must have at least 3 digits after the last comma

		$this->element_get('css', 'form')->submit();

		$this->element_text_check('css', 'ul.error_list li', 'Your amount does not appear to be a number.');

	//--------------------------------------------------

		$this->element_send_keys('id', 'fld_amount', '5A000', array('clear' => true));

		$this->element_get('css', 'form')->submit();

		$this->element_text_check('css', 'ul.error_list li', 'Your amount does not appear to be a number.');

	//--------------------------------------------------

		$this->element_send_keys('id', 'fld_amount', 'GBP - £ 010.12X', array('clear' => true));

		$this->element_get('css', 'form')->submit();

		$this->element_text_check('css', 'ul.error_list li', 'Your amount must be more than or equal to £10.00.');

//--------------------------------------------------
// Full number example

	$this->element_send_keys('id', 'fld_amount', '£5,001.23', array('clear' => true));

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '5001.23');

?>
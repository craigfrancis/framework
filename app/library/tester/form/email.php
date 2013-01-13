<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=email'));

//--------------------------------------------------
// Email

	$this->element_attribute_check('css', '.row', 'class', 'row email first_child odd email');

//--------------------------------------------------
// Invalid format

	$this->session->execute(array('script' => 'document.getElementById("fld_email").setAttribute("type", "text");', 'args' => array()));

	$this->element_send_keys('id', 'fld_email', 'abc', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your email does not appear to be correct.');

//--------------------------------------------------
// Invalid domain

	$this->element_send_keys('id', 'fld_email', 'user@invalid-domain-' . time() . '.com', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your email does not appear to be correct.');

//--------------------------------------------------
// Simple submit

	$this->element_send_keys('id', 'fld_email', 'user@example.com', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"user@example.com"');

?>
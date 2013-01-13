<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=text'));

//--------------------------------------------------
// No cookies

	$this->session->deleteAllCookies();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', '#page_content h1', 'Your browser does not accept cookies!');

	$this->url_load(http_url('/examples/form/example/?type=text'));

//--------------------------------------------------
// CSRF

	$this->session->deleteCookie(config::get('cookie.prefix') . 'session');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The request did not appear to come from a trusted source, please try again.');

//--------------------------------------------------
// Min length

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your name is required.');

//--------------------------------------------------
// Max length (server side)

	$this->session->execute(array('script' => 'document.getElementById("fld_name").removeAttribute("maxlength");', 'args' => array()));

	$this->element_send_keys('id', 'fld_name', '123456789a123456789b123456789c', array('clear' => true));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'Your name cannot be longer than 15 characters.');

//--------------------------------------------------
// Simple value, with hide/preserve tests

	$this->element_send_keys('id', 'fld_name', 'Craig', array('clear' => true));

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"Craig"');

?>
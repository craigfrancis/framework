<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=file'));

//--------------------------------------------------
// Missing enctype attribute

	$this->session->execute(array('script' => 'document.getElementById("form_1").removeAttribute("enctype");', 'args' => array()));

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', 'The form needs the attribute: enctype="multipart/form-data"');

	$this->url_load(http_url('/examples/form/example/?type=file'));

//--------------------------------------------------
// Missing file

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The file is required.');

//--------------------------------------------------
// Empty file

	$this->element_send_keys('id', 'fld_file', '/Users/craig/Dropbox/Documents/Files/UntitledEmpty.txt');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The uploaded file for "file" is empty.');

//--------------------------------------------------
// Invalid file type

	$this->element_send_keys('id', 'fld_file', '/Users/craig/Dropbox/Documents/Files/Untitled.doc');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The file has an unrecognised file type (doc).');

//--------------------------------------------------
// A file, with hide/preserve tests

	$this->element_send_keys('id', 'fld_file', '/Users/craig/Dropbox/Documents/Files/Untitled.txt');

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"Untitled.txt"');

?>
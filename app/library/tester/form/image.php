<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=image'));

//--------------------------------------------------
// Invalid file type

	$this->element_send_keys('id', 'fld_image', '/Users/craig/Dropbox/Documents/Files/Untitled.txt');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li', 'The image file has an unrecognised file type (invalid image).');

//--------------------------------------------------
// Image too small

	$this->element_send_keys('id', 'fld_image', '/Users/craig/Dropbox/Documents/Files/Images/100x100.jpg');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li:nth-child(1)', 'The image must be more than 300px wide.');
	$this->element_text_check('css', 'ul.error_list li:nth-child(2)', 'The image must be more than 300px high.');

//--------------------------------------------------
// Image too big

	$this->element_send_keys('id', 'fld_image', '/Users/craig/Dropbox/Documents/Files/Images/2560x1600.jpg');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'ul.error_list li:nth-child(1)', 'The image file cannot be bigger than 1MB.');
	$this->element_text_check('css', 'ul.error_list li:nth-child(2)', 'The image must not be more than 500px wide.');
	$this->element_text_check('css', 'ul.error_list li:nth-child(3)', 'The image must not be more than 500px high.');

//--------------------------------------------------
// Image just right

	$this->element_send_keys('id', 'fld_image', '/Users/craig/Dropbox/Documents/Files/Images/300x300.jpg');

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"300x300.jpg"');

?>
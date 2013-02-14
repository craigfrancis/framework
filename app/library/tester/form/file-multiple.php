<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=file-multiple'));

//--------------------------------------------------
// Add files

	$this->element_send_keys('id', 'fld_file', '/Users/craig/Dropbox/Documents/Files/Untitled.pdf');

	$this->element_get('id', 'fld_block')->click(); // Click on

	$this->element_get('css', 'form')->submit();

	$this->element_send_keys('id', 'fld_file', '/Users/craig/Dropbox/Documents/Files/Untitled.txt');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', '.row.file .info', 'Untitled.pdf, Untitled.txt');

	$this->element_get('id', 'fld_block')->click(); // Click off

//--------------------------------------------------
// Submit with preserve tests

	$this->run_hide_preserve_tests();

	$this->element_get('css', 'form')->submit();

	$text = $this->element_text_get('css', 'body', '"Untitled.txt"');

	foreach (array('Untitled.pdf', 'Untitled.txt') as $file_name) {
		if (strpos($text, $file_name) === false) {
			$this->test_output_add('Cannot find "' . $file_name . '" in the output');
		}
	}

?>
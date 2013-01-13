<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=date-order'));

//--------------------------------------------------
// Date

	$this->element_attribute_check('css', '.row', 'class', 'row date first_child odd date');

	$this->element_attribute_check('css', '.row label', 'for', 'fld_date_Y');

	$this->element_text_check('css', '.input', '/ /'); // Input tags are removed, but we see the input separators
	$this->element_text_check('css', '.format', 'YYYY-MM-DD');

?>
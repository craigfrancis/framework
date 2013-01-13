<?php

//--------------------------------------------------
// Start

	$this->url_load(http_url('/examples/form/example/?type=text-full'));

//--------------------------------------------------
// Initial loading

	$this->element_name_check('id', 'custom-wrapper-id', 'div');
	$this->element_name_check('css', '.custom-label-wrapper-class', 'span');
	$this->element_name_check('css', '.custom-input-wrapper-class', 'span');
	$this->element_name_check('css', '.custom-info-class', 'em');
	$this->element_name_check('css', '.custom-label-class', 'label');
	$this->element_name_check('css', '.custom-input-class', 'input');

	$this->element_attribute_check('id', 'custom-id', 'name', 'name');
	$this->element_attribute_check('id', 'custom-id', 'data-my-custom', 'value');
	$this->element_attribute_check('id', 'custom-id', 'required', 'true'); // Browser changes to true
	$this->element_attribute_check('id', 'custom-id', 'autofocus', 'true');
	$this->element_attribute_check('id', 'custom-id', 'autocorrect', 'on');
	$this->element_attribute_check('id', 'custom-id', 'autocomplete', 'on');
	$this->element_attribute_check('id', 'custom-id', 'type', 'text');
	$this->element_attribute_check('id', 'custom-id', 'maxlength', '200');
	$this->element_attribute_check('id', 'custom-id', 'value', '');

	$this->element_attribute_check('id', 'custom-wrapper-id', 'class', 'row text custom-wrapper-class add-wrapper-class first_child odd name');

	$this->element_text_check('css', '.custom-label-wrapper-class', 'NameX::');
	$this->element_text_check('css', '.custom-info-class', 'Info text');

//--------------------------------------------------
// Simple submit

	$this->element_send_keys('id', 'custom-id', 'Craig');

	$this->element_get('css', 'form')->submit();

	$this->element_text_check('css', 'body', '"Craig"');

?>
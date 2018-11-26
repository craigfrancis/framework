<?php

set_include_path(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . PATH_SEPARATOR . get_include_path());
require_once 'library/HTMLPurifier/Bootstrap.php';
require_once 'library/HTMLPurifier.php';

HTMLPurifier_Bootstrap::registerAutoload();

	//--------------------------------------------------
	//
	// $purifier = new HTMLPurifier([
	// 		'Core.Encoding'        => config::get('output.charset'),
	// 		'HTML.Doctype'         => 'XHTML 1.0 Strict',
	// 		'Cache.SerializerPath' => tmp_folder('htmlpurifier'),
	// 	]);
	//
	// $output = $purifier->purify('Hello < <strong>Name</b>');
	//
	//--------------------------------------------------

?>
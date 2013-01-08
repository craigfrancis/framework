<?php

//--------------------------------------------------
// Main script

	$output_php = file_get_contents(ROOT . '/framework/0.1/library/class/image.php');

	$output_php = str_replace('class image_base extends check', 'class image', $output_php);

//--------------------------------------------------
// Save

	config::set('debug.show', false);

	mime_set('text/plain');

	echo $output_php;

?>